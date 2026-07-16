<?php

namespace App\Services\Teams;

use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\FeedReaction;
use Illuminate\Support\Str;

class TeamActivityRecorder
{
    public function __construct(
        private readonly TeamParticipationService $participation,
        private readonly TeamContractService $contracts,
    ) {}

    public function recordPost(FeedPost $post): void
    {
        if (! $post->team_id || $post->status !== 'published') {
            return;
        }

        $post->loadMissing(['team', 'user']);
        if (! $post->team || ! $post->user) {
            return;
        }

        $event = $this->participation->award($post->team, $post->user, 'team_post_created', "team-post:{$post->id}");
        if ($event) {
            $this->contracts->recordActivity($post->team, 'team_post_created', $post->user, "team-post:{$post->id}");
        }
    }

    public function recordComment(FeedComment $comment): void
    {
        $comment->loadMissing(['post.team', 'user']);
        $post = $comment->post;
        $minimum = max(1, (int) config('team_progression.participation.activities.team_comment_created.minimum_characters', 20));

        if (! $post?->team || ! $comment->user || Str::length(trim(strip_tags((string) $comment->body))) < $minimum) {
            return;
        }

        $event = $this->participation->award($post->team, $comment->user, 'team_comment_created', "team-comment:{$comment->id}");
        if ($event) {
            $this->contracts->recordActivity($post->team, 'team_comment_created', $comment->user, "team-comment:{$comment->id}");
        }
    }

    public function recordReaction(FeedReaction $reaction): void
    {
        $reaction->loadMissing(['post.team', 'post.user']);
        $post = $reaction->post;

        if (! $post?->team || ! $post->user || (int) $post->user_id === (int) $reaction->user_id) {
            return;
        }

        $sourceKey = "team-reaction:{$post->id}:{$reaction->user_id}";
        $event = $this->participation->award($post->team, $post->user, 'team_post_reaction_received', $sourceKey, metadata: ['reactor_id' => $reaction->user_id]);
        if ($event) {
            $this->contracts->recordActivity($post->team, 'team_post_reaction_received', $post->user, $sourceKey, metadata: ['reactor_id' => $reaction->user_id]);
        }
    }
}
