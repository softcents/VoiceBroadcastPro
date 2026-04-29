<?php

declare(strict_types=1);

namespace App\Support;

trait UsingAsteriskConnection
{
    public static function using(string $host, string $username, string $password): static
    {
        config(['database.connections.asterisk' => [
            ...config('database.connections.asterisk'),
            'host' => $host,
            'username' => $username,
            'password' => $password,
        ]]);

        app('db')->purge('asterisk');
        app('db')->reconnect('asterisk');

        return new static;
    }
}
