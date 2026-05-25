<?php

declare(strict_types=1);

namespace App\Console\Commands\Asterisk;

use App\Asterisk\MyStasisApp;
use App\Asterisk\RatchetConnector;
use App\Models\Server;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use OpiyOrg\AriClient\Client\Rest\Resource\Channels as AriChannelsRestResourceClient;
use OpiyOrg\AriClient\Client\Rest\Resource\Events as AriEventsRestResourceClient;
use OpiyOrg\AriClient\Client\Rest\Settings as AriRestClientSettings;
use OpiyOrg\AriClient\Client\WebSocket\Factory as AriWebSocketClientFactory;
use OpiyOrg\AriClient\Client\WebSocket\Ratchet\Settings as AriRatchetSettings;
use OpiyOrg\AriClient\Client\WebSocket\Ratchet\WebSocketClient;
use OpiyOrg\AriClient\Client\WebSocket\Settings as AriWebSocketClientSettings;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Throwable;

#[Signature('asterisk:start')]
#[Description('Command description')]
final class Start extends Command
{
    /** @var array<int, WebSocketClient> */
    private array $clients = [];

    public function handle(): int
    {
        $this->components->info('Starting Asterisk ARI client...');

        $this->ensureDaemonIsNotRunning();
        $this->writePidFile();

        $loop = Loop::get();

        if (! $loop) {
            $this->components->error('Failed to get event loop.');

            return SymfonyCommand::FAILURE;
        }

        // Stop the loop gracefully on SIGINT and SIGTERM signals
        $loop->addSignal(SIGINT, fn () => $this->stop($loop));
        $loop->addSignal(SIGTERM, fn () => $this->stop($loop));

        // Restart the loop on SIGUSR1 signal
        $loop->addSignal(SIGUSR1, fn () => $this->restart($loop));

        // Periodically check for server updates every 30 seconds
        $loop->addPeriodicTimer(30, function () use ($loop) {
            $this->components->info('Checking for server updates...');
            $this->initializeServers($loop);
        });

        // Initialize the connection to the Asterisk ARI server
        $this->initializeServers($loop);

        $this->components->info('Asterisk ARI client started successfully.');
        $this->line('<fg=gray;options=bold>Press Ctrl+C to stop the client.</>');
        $loop->run();

        return SymfonyCommand::SUCCESS;
    }

    public function initializeServers(LoopInterface $loop): void
    {
        $this->reconnectToDatabase();

        $servers = Server::enabled()->get();
        $activeServerIds = [];

        // Connect to new servers and keep track of active server IDs
        foreach ($servers as $server) {
            $activeServerIds[] = $server->id;

            if (! isset($this->clients[$server->id])) {
                $this->connectToServer($server, $loop);
            }
        }

        // Cleanup removed servers
        foreach (array_keys($this->clients) as $serverId) {
            if (! in_array($serverId, $activeServerIds, true)) {
                unset($this->clients[$serverId]);
                $this->components->warn("Disconnected from removed server with ID: {$serverId}");
            }
        }
    }

    public function connectToServer(Server $server, LoopInterface $loop): void
    {
        $this->components->info('Connecting to Asterisk ARI server...');

        $restSettings = new AriRestClientSettings(
            user: $server->ari_username,
            password: $server->ari_password,
            host: $server->ari_host,
            port: $server->ari_port ?? 8088,
            appName: 'MyStasisApp'
        );

        // REST
        $restChannelsClient = new AriChannelsRestResourceClient($restSettings);
        $restEventsClient = new AriEventsRestResourceClient($restSettings);

        $myStasisApp = new MyStasisApp($restChannelsClient, $restEventsClient, $this->components);

        // Websocket
        $wsSettings = new AriWebSocketClientSettings(
            user: $restSettings->getUser(),
            password: $restSettings->getPassword(),
            host: $restSettings->getHost(),
            port: $restSettings->getPort(),
            appName: $restSettings->getAppName()
        );

        // Ratchet
        $ratchetSettings = new AriRatchetSettings();
        $ratchetSettings->setLoop($loop);

        $ratchetConnector = new RatchetConnector($loop, $server);
        $ratchetSettings->setRatchetConnector($ratchetConnector);

        $wsClient = AriWebSocketClientFactory::createRatchet(
            $wsSettings,
            $myStasisApp,
            $ratchetSettings
        );

        $this->clients[$server->id] = $wsClient;

        $this->components->info('Connected to Asterisk ARI server successfully.');
    }

    private function stop(LoopInterface $loop): void
    {
        $this->newLine();
        $this->components->warn('Stopping Asterisk ARI client...');

        $this->removePidFile();
        $loop->stop();

        $this->components->info('Asterisk ARI client stopped successfully.');
    }

    private function restart(LoopInterface $loop)
    {
        $this->components->warn('Reloading is not implemented yet. Please stop and start the client to apply changes.');

        //        $this->newLine();
        //        $this->components->info('Restarting Asterisk ARI client...');
        //
        // //        // Stop all clients
        // //        foreach ($this->clients as $client) {
        // //            $client->getLoop()->stop();
        // //        }
        //
        //        $this->clients = [];
        //
        //        // Re-initialize servers
        //        $this->initializeServers($loop);
        //
        //        $this->components->info('Asterisk ARI client restarted successfully.');
    }

    private function getPidFilePath(): string
    {
        return storage_path('app/private/asterisk-ari.pid');
    }

    private function writePidFile(): void
    {
        $pid = getmypid();
        file_put_contents($this->getPidFilePath(), $pid);
        $this->components->twoColumnDetail('Process ID', (string) $pid);
    }

    private function removePidFile(): void
    {
        $pidFilePath = $this->getPidFilePath();
        if (file_exists($pidFilePath)) {
            unlink($pidFilePath);
        }
    }

    private function ensureDaemonIsNotRunning(): void
    {
        $pidFile = $this->getPidFilePath();

        if (! file_exists($pidFile)) {
            return;
        }

        $existingPid = (int) mb_trim(file_get_contents($pidFile));

        if ($existingPid <= 0) {
            unlink($pidFile);

            return;
        }

        // Check if process is alive
        if (function_exists('posix_kill') && posix_kill($existingPid, 0)) {
            $this->components->error(
                "Asterisk ARI is already running. PID: {$existingPid}"
            );

            exit(SymfonyCommand::FAILURE);
        }

        // Stale PID file
        unlink($pidFile);
    }

    private function reconnectToDatabase(): void
    {
        try {
            DB::connection()->reconnect();
        } catch (Throwable $e) {
            $this->components->error('Database connection failed: '.$e->getMessage());

            return;
        }
    }
}
