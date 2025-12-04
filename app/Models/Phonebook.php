<?php

namespace App\Models;

use Database\Factories\ContactGroupFactory; // This line will be removed or changed based on the factory name
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Added based on the return type change

class Phonebook extends Model // Renamed from ContactGroup
{
    /** @use HasFactory<\Database\Factories\PhonebookFactory> */ // Updated factory type hint
    use HasFactory;

    protected $fillable = [
        'name', // 'user_id' removed
        'description',
    ];

    public function user(): BelongsTo // Return type simplified, requires 'use BelongsTo;'
    {
        return $this->belongsTo(User::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class); // Removed 'contact_group_id'
    }
}
