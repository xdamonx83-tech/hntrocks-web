<?php

namespace App\Services\Feed;

use App\Models\FeedComment;
use App\Models\FeedPost;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FeedCommentTreeService
{
    public function deleteTree(FeedComment $comment): int
    {
        return DB::transaction(function () use ($comment): int {
            FeedPost::withTrashed()
                ->whereKey($comment->feed_post_id)
                ->lockForUpdate()
                ->first();

            $lockedComment = FeedComment::query()
                ->whereKey($comment->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedComment) {
                return 0;
            }

            $ids = $this->expandTreeIds(
                (int) $lockedComment->feed_post_id,
                [(int) $lockedComment->id]
            );

            if ($ids === []) {
                return 0;
            }

            return FeedComment::query()
                ->whereIn('id', $ids)
                ->delete();
        }, 3);
    }

    public function invalidActiveCommentIds(?int $postId = null): Collection
    {
        return FeedComment::query()
            ->when($postId !== null, fn (Builder $query) => $query->where('feed_post_id', $postId))
            ->whereNotNull('parent_id')
            ->whereDoesntHave('parent', function (Builder $parent): void {
                $parent->whereNull('parent_id');
            })
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();
    }

    public function repairInvalidTrees(?int $postId = null): int
    {
        return DB::transaction(function () use ($postId): int {
            $seedIds = $this->invalidActiveCommentIds($postId);

            if ($seedIds->isEmpty()) {
                return 0;
            }

            $seedsByPost = FeedComment::query()
                ->whereIn('id', $seedIds->all())
                ->get(['id', 'feed_post_id'])
                ->groupBy('feed_post_id')
                ->sortKeys();

            $deleteIds = [];

            foreach ($seedsByPost as $feedPostId => $comments) {
                $feedPostId = (int) $feedPostId;

                FeedPost::withTrashed()
                    ->whereKey($feedPostId)
                    ->lockForUpdate()
                    ->first();

                $treeIds = $this->expandTreeIds(
                    $feedPostId,
                    $comments->pluck('id')->map(fn ($id): int => (int) $id)->all()
                );

                foreach ($treeIds as $treeId) {
                    $deleteIds[$treeId] = true;
                }
            }

            if ($deleteIds === []) {
                return 0;
            }

            return FeedComment::query()
                ->whereIn('id', array_keys($deleteIds))
                ->delete();
        }, 3);
    }

    private function expandTreeIds(int $feedPostId, array $seedIds): array
    {
        $seen = [];

        foreach ($seedIds as $seedId) {
            $seedId = (int) $seedId;
            if ($seedId > 0) {
                $seen[$seedId] = true;
            }
        }

        $frontier = array_keys($seen);

        while ($frontier !== []) {
            $children = FeedComment::withTrashed()
                ->where('feed_post_id', $feedPostId)
                ->whereIn('parent_id', $frontier)
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $frontier = [];

            foreach ($children as $childId) {
                if (isset($seen[$childId])) {
                    continue;
                }

                $seen[$childId] = true;
                $frontier[] = $childId;
            }
        }

        return array_keys($seen);
    }
}
