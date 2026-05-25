<?php

declare(strict_types=1);

namespace App\Models\Asterisk;

use App\Asterisk\UsingAsteriskConnection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $calldate
 * @property string $clid
 * @property string $src
 * @property string $dst
 * @property string $dcontext
 * @property string $channel
 * @property string $dstchannel
 * @property string $lastapp
 * @property string $lastdata
 * @property int $duration
 * @property int $billsec
 * @property string $disposition
 * @property string $amaflags
 * @property string $accountcode
 * @property string $uniqueid
 * @property string $userfield
 * @property string $did
 * @property string $recordfile
 * @property int $cnum
 * @property string $cnam
 * @property string $outbound_cnum
 * @property string $outbound_cnam
 * @property string $dst_cnam
 * @property string $linkedid
 * @property string $peeraccount
 * @property string $sequence
 */
final class Cdr extends Model
{
    use UsingAsteriskConnection;

    public $timestamps = false;

    protected $connection = 'asterisk';

    protected $table = 'cdr';

    protected $primaryKey = 'sequence';

    protected function casts(): array
    {
        return [
            'billsec' => 'int',
        ];
    }
}
