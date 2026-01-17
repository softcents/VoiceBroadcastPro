<?php

declare(strict_types=1);

namespace App\Console\Commands\Asterisk;

use App\Enums\CallStatus;
use App\Models\Call;
use App\Models\Server;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Ratchet\RFC6455\Messaging\Message;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use Throwable;

use function Ratchet\Client\connect;

final class Start extends Command
{
    protected $signature = 'asterisk:start';

    protected $description = 'Start the Asterisk ARI Listener Manager';

    private array $connections = [];

    public function handle(): int
    {
        $this->components->info('Starting Asterisk ARI Manager');

        // Write PID to state file
        $this->writePidFile();

        $loop = Loop::get();

        if (! $loop) {
            $this->components->error('Failed to get event loop instance');

            return 1;
        }

        // 1. Signal Handling
        $loop->addSignal(SIGINT, fn () => $this->shutdown($loop));
        $loop->addSignal(SIGTERM, fn () => $this->shutdown($loop));

        // Handle reload signal (SIGUSR1) - like Octane
        $loop->addSignal(SIGUSR1, function () use ($loop) {
            $this->components->warn('Reload signal received');
            $this->reloadConnections($loop);
        });

        // 2. Periodic Server Check (Every 5 seconds)
        $loop->addPeriodicTimer(5.0, function () use ($loop) {
            $this->checkServers($loop);
        });

        // 3. Initial Check
        $this->checkServers($loop);

        $this->components->info('Event loop running');
        $this->line('<fg=gray>Press Ctrl+C to stop</>');
        $loop->run();

        return 0;
    }

    private function shutdown(LoopInterface $loop): void
    {
        $this->newLine();
        $this->components->warn('Shutting down');
        foreach ($this->connections as $conn) {
            $conn->close();
        }
        $this->removePidFile();
        $loop->stop();
        $this->components->info('Shutdown complete');
    }

    private function checkServers(LoopInterface $loop): void
    {
        // Reconnect DB to prevent timeout issues in long-running script
        try {
            DB::connection()->reconnect();
        } catch (Exception $e) {
            $this->components->error('Database connection failed: '.$e->getMessage());

            return;
        }

        $servers = Server::where('enabled', true)->get();
        $activeServerIds = [];

        foreach ($servers as $server) {
            $activeServerIds[] = $server->id;

            if (! isset($this->connections[$server->id])) {
                $this->connectToServer($server, $loop);
            }
        }

        // Cleanup removed servers
        foreach ($this->connections as $serverId => $conn) {
            if (!in_array($serverId, $activeServerIds, true)) {
                $this->components->task("Closing connection to server $serverId", fn () => true);
                $conn->close();
                unset($this->connections[$serverId]);
            }
        }
    }

    private function connectToServer(Server $server, LoopInterface $loop): void
    {
        $this->line("  <fg=yellow>→</> Connecting to <fg=cyan>$server->host</>");

        // Placeholder to prevent multiple connection attempts while one is pending
        // We set it to true initially, then replace with actual connection object on success
        $this->connections[$server->id] = true;

        $appName = 'originate';
        $base = $server->host .($server->port ? ":$server->port" : '');

        $url = "ws://$base/ari/events?api_key=$server->username:$server->password&app=$appName";

        connect($url, [], [], $loop)->then(function ($conn) use ($server) {
            $this->components->task("Connected to $server->host", fn () => true);
            $this->connections[$server->id] = $conn;

            // Update database connection status
            $this->updateServerConnection($server->id, 'connected');

            $conn->on('message', function ($msg) use ($server) {
                $this->handleMessage($msg, $server);
            });

            $conn->on('close', function ($code = null) use ($server) {
                $this->components->warn("Connection closed: $server->host (code: $code)");
                unset($this->connections[$server->id]);

                // Update database connection status
                $this->updateServerConnection($server->id, 'disconnected');
            });

        }, function ($e) use ($server) {
            $this->components->error("Failed to connect to $server->host: {$e->getMessage()}");
            unset($this->connections[$server->id]);

            // Update database connection status
            $this->updateServerConnection($server->id, 'error');
        });
    }

    private function handleMessage(Message $message, Server $server): void
    {
        try {
            $event = json_decode($message->getPayload(), true, 512, JSON_THROW_ON_ERROR);
            if (! $event) {
                return;
            }

            $this->processEvent($event, $server);

        } catch (Throwable $e) {
            $this->components->error('Message processing error: '.$e->getMessage());
        }
    }

    private function processEvent(array $event, Server $server): void
    {
        $type = $event['type'] ?? 'Unknown';

        switch ($type) {
            case 'StasisStart':
                $this->handleStasisStart($event, $server);
                break;
            case 'PlaybackFinished':
                $this->handlePlaybackFinished($event, $server);
                break;
            case 'Dial':
                $this->handleDial($event);
                break;
            case 'ChannelDestroyed':
                $this->handleChannelDestroyed($event);
                break;
        }
    }

    // --- Logic Handlers ---

    private function handleStasisStart($event, Server $server): void
    {
        $channelId = $event['channel']['id'];
        $callType = $event['args'][0] ?? 'marketing';
        $audioOrOtp = $event['args'][1] ?? 'hello-world';

        $this->line("  <fg=green>✓</> StasisStart: <fg=gray>$channelId</>");

        // Using Http Facade here is blocking, but for quick API calls it's "okay" in low volume.
        // For high volume, would need an Async HTTP client too.
        // For this refactor, we focus on the WebSocket loop first.
        // We'll keep the synchronous HTTP for consistency with previous logic,
        // but ideally this should also be async.

        $this->ariPost($server, "channels/$channelId/answer");

        if ($callType === 'otp') {
            $this->ariPost($server, "channels/$channelId/play", ['media' => 'sound:'.url('sounds/pre-otp.wav')]);
            $this->ariPost($server, "channels/$channelId/play", ['media' => "digits:$audioOrOtp"]);
            $this->ariPost($server, "channels/$channelId/play", ['media' => 'sound:'.url('sounds/post-otp.wav')]);
            $this->ariPost($server, "channels/$channelId/play", [
                'media' => "digits:$audioOrOtp",
                'playbackId' => Str::random().'_eof',
            ]);

            $this->line("  <fg=green>✓</> OTP played on channel: <fg=gray>$channelId</>");
        } else {
            $this->ariPost($server, "channels/$channelId/play", [
                'media' => "sound:$audioOrOtp",
                'playbackId' => Str::random().'_eof',
            ]);

            $this->line('  <fg=green>✓</> Marketing audio played on channel: <fg=gray>'.$channelId.'</>');
        }
    }

    private function handlePlaybackFinished($event, Server $server): void
    {
        $this->line('  <fg=green>✓</> PlaybackFinished event received');

        $playbackId = $event['playback']['id'] ?? '';

        if (! str_ends_with($playbackId, '_eof')) {
            return;
        }

        $targetUri = $event['playback']['target_uri'] ?? '';
        if (str_starts_with($targetUri, 'channel:')) {
            $channelId = str_replace('channel:', '', $targetUri);
            $this->ariPost($server, "channels/$channelId", [], 'DELETE', [404]);
        }
    }

    private function handleDial($event): void
    {
        $peerId = $event['peer']['id'] ?? null;
        $status = $event['dialstatus'] ?? '';
        $timestamp = $event['timestamp'];

        $this->line("  <fg=green>✓</> Dial event: Peer ID: <fg=gray>$peerId</>, Status: <fg=gray>$status</>");

        if (! $peerId || empty($status)) {
            return;
        }

        $webhookEvent = match ($status) {
            'RINGING' => 'ringing',
            'ANSWER' => 'answered',
            'BUSY' => 'busy',
            'CHANUNAVAIL' => 'unreachable',
            'NOANSWER' => 'not_answered',
            'CONGESTION' => 'failed',
            'CANCEL' => 'canceled',
            'PROGRESS' => 'progress',
            // default => 'failed'
        };

        $this->updateStatus($webhookEvent, $peerId, $timestamp);
    }

    private function handleChannelDestroyed($event): void
    {
        if (($event['cause'] ?? 0) === 16) {
            $this->line("  <fg=green>✓</> ChannelDestroyed: Call completed for <fg=gray>{$event['channel']['id']}</>");

            $this->updateStatus('completed', $event['channel']['id'], $event['timestamp']);
        }
    }

    // --- Helpers ---

    private function ariPost(Server $server, $endpoint, $data = [], $method = 'POST', $ignoreCodes = []): void
    {
        // NOTE: This is still synchronous HTTP.
        // In a perfect ReactPHP world, we'd use react/http-client here.
        // But for this specific request "Refactor to ReactPHP" primarily targeting the loop/sockets,
        // this is acceptable if load isn't massive.

        $base = "$server->scheme://$server->host".($server->port ? ":$server->port" : '');
        $url = "$base/ari/$endpoint";

        try {
            $response = Http::withBasicAuth($server->username, $server->password)
                ->timeout(5)
                ->send($method, $url, ['json' => $data]);

            if ($response->failed() && !in_array($response->status(), $ignoreCodes, true)) {
                $this->components->error("ARI API error [$server->host]: ".$response->body());
            }
        } catch (Exception $e) {
            $this->components->error("HTTP error [$server->host]: ".$e->getMessage());
        }
    }

    private function updateStatus($event, $channelId, $timestamp): void
    {
        $timestamp = CarbonImmutable::createFromTimeString($timestamp);

        $call = Call::whereUniqueId($channelId)->first();

        if (! $call) {
            $this->components->warn("Call record not found: $channelId");

            return;
        }

        switch ($event) {
            case 'ringing':
                $call->update([
                    'status' => CallStatus::Ringing,
                    'ringing_at' => $timestamp,
                ]);
                break;
            case 'answered':
                $call->update([
                    'status' => CallStatus::Answered,
                    'answered_at' => $timestamp,
                ]);
                break;
            case 'completed':
                if ($call->status !== CallStatus::Answered) {
                    // Ignore if not answered
                    break;
                }

                $call->update([
                    'status' => CallStatus::Completed,
                    'ended_at' => $timestamp,
                    'duration' => $call->answered_at->diffInSeconds($timestamp),
                ]);

                break;
            case 'busy':
                $call->update([
                    'status' => CallStatus::Busy,
                    'ended_at' => $timestamp,
                ]);
                break;
            case 'not_answered':
                $call->update([
                    'status' => CallStatus::NotAnswered,
                    'ended_at' => $timestamp,
                ]);
                break;
            case 'failed':
                $call->update([
                    'status' => CallStatus::Failed,
                    'ended_at' => $timestamp,
                ]);
                break;
        }
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
        $pidFile = $this->getPidFilePath();
        if (file_exists($pidFile)) {
            unlink($pidFile);
        }
    }

    private function reloadConnections(LoopInterface $loop): void
    {
        // Close all existing connections
        foreach ($this->connections as $conn) {
            if (is_object($conn)) {
                $conn->close();
            }
        }

        // Clear connections array
        $this->connections = [];

        // Reconnect to all servers
        $this->checkServers($loop);

        $this->components->info('Reload complete');
    }

    private function updateServerConnection(int $serverId, string $status): void
    {
        try {
            $data = ['connection_status' => $status];

            if ($status === 'connected') {
                $data['connected_at'] = now();
            } elseif (in_array($status, ['disconnected', 'error'])) {
                $data['disconnected_at'] = now();
            }

            DB::table('servers')
                ->where('id', $serverId)
                ->update($data);
        } catch (Exception) {
            // Silently fail to avoid disrupting daemon
        }
    }
}
