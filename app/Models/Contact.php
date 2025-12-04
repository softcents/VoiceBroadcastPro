<?php

namespace App\Models;

use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    protected $fillable = [
        'phonebook_id',
        'first_name',
        'last_name',
        'phone_number',
    ];

    public function phonebook(): BelongsTo
    {
        return $this->belongsTo(Phonebook::class);
    }
}
