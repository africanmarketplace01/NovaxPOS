<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Stub ModelPolicy — prevents ReflectionException from the AuthServiceProvider entry.
 * Extend with real gates as needed.
 */
class ModelPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool  { return true; }
    public function view(User $user): bool     { return true; }
    public function create(User $user): bool   { return $user->role_id <= 2; }
    public function update(User $user): bool   { return $user->role_id <= 2; }
    public function delete(User $user): bool   { return $user->role_id === 1; }
}
