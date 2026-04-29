<?php

declare(strict_types=1);

namespace App\Models\Asterisk;

use Illuminate\Database\Eloquent\Model;

final class CEL extends Model
{
    protected $connection = 'asterisk';

    protected $table = 'cel';

    protected $casts = [
        'extra' => 'array',
    ];

    public static function using(string $host, string $username, string $password)
    {
        config(['database.connections.asterisk' => [
            ...config('database.connections.asterisk'),
            'host' => $host,
            'username' => $username,
            'password' => $password,
        ]]);

        app('db')->purge('asterisk');
        app('db')->reconnect('asterisk');

        return new self;
    }

    public static function findByUniqueId($uniqueId, $eventTypes = ['HANGUP'])
    {
        return self::whereIn('eventtype', $eventTypes)
            ->where('uniqueid', $uniqueId)
            ->first();
    }
}
