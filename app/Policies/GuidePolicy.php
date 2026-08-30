<?php

namespace App\Policies;

use App\Models\Guide;
use App\Models\User;

class GuidePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Guide $guide): bool
    {
        return $guide->isPublished()
            || ($user !== null && ($guide->isOwnedBy($user) || $user->isAdmin()));
    }

    public function create(User $user): bool
    {
        return $user->status === 'active';
    }

    public function update(User $user, Guide $guide): bool
    {
        return $guide->isOwnedBy($user)
            && $guide->archived_at === null
            && $guide->status !== 'pending_review';
    }

    public function delete(User $user, Guide $guide): bool
    {
        return $guide->isOwnedBy($user) && $guide->canBeDeletedByAuthor();
    }

    public function preview(User $user, Guide $guide): bool
    {
        return $guide->isOwnedBy($user) || $user->isAdmin();
    }

    public function submit(User $user, Guide $guide): bool
    {
        return $this->update($user, $guide);
    }

    public function withdraw(User $user, Guide $guide): bool
    {
        return $guide->isOwnedBy($user) && $guide->status === 'pending_review';
    }

    public function moderate(User $user): bool
    {
        return $user->isAdmin();
    }
}
