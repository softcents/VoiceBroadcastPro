<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CallStatus;
use App\Models\Call;
use App\Models\Server;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Ratchet\RFC6455\Messaging\Message;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use Throwable;

use function Ratchet\Client\connect;

final class AsteriskAriDaemon extends Command
{
    protected $signature = 'asterisk:ari-listen';

    protected $description = 'Multi-server Asterisk ARI Listener Manager (Async)';
    private array $connections = [];

    public function handle(): int
    {
        $this->info('Starting Async Asterisk Manager...');

        $loop = Loop::get();

        // 1. Signal Handling (Graceful Shutdown)
        $loop->addSignal(SIGINT, fn () => $this->shutdown($loop));
        $loop->addSignal(SIGTERM, fn () => $this->shutdown($loop));

        // 2. Periodic Server Check (Every 5 seconds)
        $loop->addPeriodicTimer(5.0, function () use ($loop) {
            $this->checkServers($loop);
        });

        // 3. Initial Check
        $this->checkServers($loop);

        $this->info('Event loop running. Press Ctrl+C to stop.');
        $loop->run();

        return 0;
    }

    private function shutdown(LoopInterface $loop): void
    {
        $this->info('Shutting down...');
        foreach ($this->connections as $serverId => $conn) {
            $conn->close();
        }
        $loop->stop();
        $this->info('Goodbye.');
    }

    private function checkServers(LoopInterface $loop): void
    {
        // Reconnect DB to prevent timeout issues in long-running script
        try {
            DB::connection()->reconnect();
        } catch (Exception $e) {
            $this->error('DB Connection failed: '.$e->getMessage());

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
            if (! in_array($serverId, $activeServerIds)) {
                $this->info("Server {$serverId} removed/disabled. Closing connection.");
                $conn->close();
                unset($this->connections[$serverId]);
            }
        }
    }

    private function connectToServer(Server $server, LoopInterface $loop): void
    {
        $this->info("--> [{$server->host}] Connecting...");

        // Placeholder to prevent multiple connection attempts while one is pending
        // We set it to true initially, then replace with actual connection object on success
        $this->connections[$server->id] = true;

        $appName = 'originate';
        $base = "{$server->host}".($server->port ? ":{$server->port}" : '');

        $url = "ws://{$base}/ari/events?api_key={$server->username}:{$server->password}&app={$appName}";

        connect($url, [], [], $loop)->then(function ($conn) use ($server) {
            $this->info("--> [{$server->host}] Connected!");
            $this->connections[$server->id] = $conn;

            $conn->on('message', function ($msg) use ($server) {
                $this->handleMessage($msg, $server);
            });

            $conn->on('close', function ($code = null, $reason = null) use ($server) {
                $this->warn("--> [{$server->host}] Connection closed ({$code} - {$reason})");
                unset($this->connections[$server->id]);
            });

        }, function ($e) use ($server) {
            $this->error("--> [{$server->host}] Could not connect: {$e->getMessage()}");
            unset($this->connections[$server->id]);
        });
    }

    private function handleMessage(Message $message, Server $server): void
    {
        try {
            $event = json_decode($message->getPayload(), true);
            if (! $event) {
                return;
            }

            $this->processEvent($event, $server);

        } catch (Throwable $e) {
            $this->error('Error processing message: '.$e->getMessage());
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
        $soundFile = $event['args'][0] ?? 'hello-world';
        $this->info("[{$server->host}] StasisStart: {$channelId}");

        // Using Http Facade here is blocking, but for quick API calls it's "okay" in low volume.
        // For high volume, would need an Async HTTP client too.
        // For this refactor, we focus on the WebSocket loop first.
        // We'll keep the synchronous HTTP for consistency with previous logic,
        // but ideally this should also be async.

        $this->ariPost($server, "channels/{$channelId}/answer");
        $this->ariPost($server, "channels/{$channelId}/play", ['media' => "sound:{$soundFile}"]);
    }

    private function handlePlaybackFinished($event, Server $server): void
    {
        $targetUri = $event['playback']['target_uri'] ?? '';
        if (str_starts_with($targetUri, 'channel:')) {
            $channelId = str_replace('channel:', '', $targetUri);
            $this->ariPost($server, "channels/{$channelId}", [], 'DELETE', [404]);
        }
    }

    private function handleDial($event): void
    {
        $peerId = $event['peer']['id'] ?? null;
        $status = $event['dialstatus'] ?? '';
        $timestamp = $event['timestamp'];

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

        $base = "{$server->scheme}://{$server->host}".($server->port ? ":{$server->port}" : '');
        $url = "{$base}/ari/{$endpoint}";

        try {
            $response = Http::withBasicAuth($server->username, $server->password)
                ->timeout(5)
                ->send($method, $url, ['json' => $data]);

            if ($response->failed() && ! in_array($response->status(), $ignoreCodes)) {
                $this->error("[{$server->host}] API Error: ".$response->body());
            }
        } catch (Exception $e) {
            $this->error("[{$server->host}] HTTP Error: ".$e->getMessage());
        }
    }

    private function updateStatus($event, $channelId, $timestamp): void
    {
        $timestamp = CarbonImmutable::createFromTimeString($timestamp);

        $call = Call::whereUniqueId($channelId)->first();

        if (! $call) {
            $this->warn("Call not found for channel: {$channelId}");

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
}
