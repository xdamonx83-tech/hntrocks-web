<?php

namespace App\Services\Search;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PlayerSearchQuery
{
    public function build(User $viewer, string $search = '', string $platform = '', string $playstyle = ''): Builder
    {
        return User::query()
            ->with(['profile', 'privacySettings'])
            ->where('status', 'active')
            ->where(function (Builder $query) use ($viewer): void {
                $query->where('id', $viewer->id)
                    ->orWhereDoesntHave('profile')
                    ->orWhereHas('profile', function (Builder $profileQuery): void {
                        $profileQuery->whereNull('profile_visibility')
                            ->orWhere('profile_visibility', '!=', 'private');
                    });
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = '%'.$search.'%';
                $query->where(function (Builder $subQuery) use ($term): void {
                    $subQuery->where('name', 'like', $term)
                        ->orWhere('username', 'like', $term)
                        ->orWhereHas('profile', fn (Builder $profileQuery) => $profileQuery
                            ->where('headline', 'like', $term)
                            ->orWhere('bio', 'like', $term)
                            ->orWhere('platform', 'like', $term)
                            ->orWhere('playstyle', 'like', $term)
                            ->orWhere('region', 'like', $term));
                });
            })
            ->when($platform !== '', fn (Builder $query) => $query->whereHas(
                'profile',
                fn (Builder $profile) => $profile->where('platform', 'like', '%'.$platform.'%')
            ))
            ->when($playstyle !== '', fn (Builder $query) => $query->whereHas(
                'profile',
                fn (Builder $profile) => $profile->where('playstyle', $playstyle)
            ));
    }
}
