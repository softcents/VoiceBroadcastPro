<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Phonebook;
use App\Models\User;

final class PhonebookPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Phonebook $phonebook): bool
    {
        return $user->isAdmin() || $user->id === $phonebook->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Phonebook $phonebook): bool
    {
        return $user->isAdmin() || $user->id === $phonebook->user_id;
    }

    public function delete(User $user, Phonebook $phonebook): bool
    {
        return $user->isAdmin() || $user->id === $phonebook->user_id;
    }
}
