<?php

declare(strict_types=1);

namespace App\Asterisk;

use App\Models\Server;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use React\Socket\Connector as ReactConnector;

final class RatchetConnector extends Connector
{
    public function __construct(LoopInterface $loop, private readonly Server $server)
    {
        parent::__construct($loop, new ReactConnector($loop));
    }

    public function __invoke($url, array $subProtocols = [], array $headers = []): PromiseInterface
    {
        return parent::__invoke($url, $subProtocols, $headers)
            ->then(function (WebSocket $ws) {
                // Do something
                return $ws;
            });
    }
}
