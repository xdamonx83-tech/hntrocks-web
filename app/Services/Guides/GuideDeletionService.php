<?php

namespace App\Services\Guides;

use App\Models\Guide;
use App\Models\GuideComment;
use App\Models\GuideMedia;
use App\Models\GuideRevision;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class GuideDeletionService
{
    public function __construct(private readonly MediaService $mediaService)
    {
    }

    public function deleteDraft(Guide $guide, User $author): void
    {
        $assetIds = DB::transaction(function () use ($guide, $author): array {
            $locked = Guide::query()->lockForUpdate()->findOrFail($guide->id);

            if (! $locked->isOwnedBy($author) || ! $locked->canBeDeletedByAuthor()) {
                throw ValidationException::withMessages([
                    'guide' => __('guides_mine.delete_forbidden'),
                ]);
            }

            $assetIds = GuideMedia::query()
                ->where('guide_id', $locked->id)
                ->whereNotNull('media_asset_id')
                ->pluck('media_asset_id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            // Break the two intentional guide/revision references before removing
            // the draft graph. This keeps the deletion independent of FK order.
            $locked->forceFill([
                'working_revision_id' => null,
                'current_published_revision_id' => null,
            ])->save();

            GuideRevision::query()
                ->where('guide_id', $locked->id)
                ->update(['cover_media_id' => null]);

            GuideComment::withTrashed()
                ->where('guide_id', $locked->id)
                ->whereNotNull('parent_id')
                ->forceDelete();
            GuideComment::withTrashed()
                ->where('guide_id', $locked->id)
                ->whereNull('parent_id')
                ->forceDelete();

            $locked->helpfulVotes()->delete();
            $locked->bookmarks()->delete();
            $locked->moderationEvents()->delete();
            $locked->reputationEntries()->delete();

            GuideMedia::query()->where('guide_id', $locked->id)->delete();
            GuideRevision::query()->where('guide_id', $locked->id)->delete();
            $locked->delete();

            return $assetIds;
        });

        MediaAsset::query()->whereIn('id', $assetIds)->get()->each(function (MediaAsset $asset): void {
            try {
                $this->mediaService->delete($asset);
            } catch (Throwable $exception) {
                report($exception);
            }
        });
    }
}
