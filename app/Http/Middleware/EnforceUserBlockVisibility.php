<?php

namespace App\Http\Middleware;

use App\Models\Conversation;
use App\Models\CupChatMessage;
use App\Models\CupIdea;
use App\Models\CupIdeaVote;
use App\Models\CupTeamChatMessage;
use App\Models\FeedComment;
use App\Models\FeedCommentReaction;
use App\Models\FeedBookmark;
use App\Models\FeedPost;
use App\Models\FeedPostPollVote;
use App\Models\FeedReaction;
use App\Models\Friendship;
use App\Models\HntMapMarkerComment;
use App\Models\HntMapMarkerVote;
use App\Models\LfgApplication;
use App\Models\LfgPost;
use App\Models\Message;
use App\Models\Moment;
use App\Models\MomentBookmark;
use App\Models\MomentComment;
use App\Models\MomentReaction;
use App\Models\TeamLfgApplication;
use App\Models\TeamLfgPost;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\UserBlockService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceUserBlockVisibility
{
    public function __construct(private readonly UserBlockService $blocks)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $viewer = $request->user();

        if (! $viewer instanceof User || $viewer->isAdmin() || $this->isBlockManagementRoute($request)) {
            return $next($request);
        }

        $blockedIds = $this->blocks->blockedUserIds($viewer);

        if ($blockedIds === []) {
            return $next($request);
        }

        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if ($this->parameterTouchesBlockedUser($parameter, $blockedIds, $viewer)) {
                abort(404);
            }
        }

        return $next($request);
    }

    /**
     * @param  array<int, int>  $blockedIds
     */
    private function parameterTouchesBlockedUser(mixed $parameter, array $blockedIds, User $viewer): bool
    {
        if ($parameter instanceof User) {
            return (int) $parameter->id !== (int) $viewer->id
                && in_array((int) $parameter->id, $blockedIds, true);
        }

        if ($parameter instanceof Conversation) {
            return $parameter->users()
                ->whereIn('users.id', $blockedIds)
                ->exists();
        }

        if ($parameter instanceof Friendship) {
            return in_array((int) $parameter->user_one_id, $blockedIds, true)
                || in_array((int) $parameter->user_two_id, $blockedIds, true);
        }

        $authorId = match (true) {
            $parameter instanceof UserNotification => $parameter->actor_id,
            $parameter instanceof FeedPost,
            $parameter instanceof FeedComment,
            $parameter instanceof FeedReaction,
            $parameter instanceof FeedCommentReaction,
            $parameter instanceof FeedBookmark,
            $parameter instanceof FeedPostPollVote,
            $parameter instanceof Moment,
            $parameter instanceof MomentComment,
            $parameter instanceof MomentReaction,
            $parameter instanceof MomentBookmark,
            $parameter instanceof LfgPost,
            $parameter instanceof LfgApplication,
            $parameter instanceof TeamLfgPost,
            $parameter instanceof TeamLfgApplication,
            $parameter instanceof Message,
            $parameter instanceof CupChatMessage,
            $parameter instanceof CupTeamChatMessage,
            $parameter instanceof HntMapMarkerComment,
            $parameter instanceof HntMapMarkerVote,
            $parameter instanceof CupIdea,
            $parameter instanceof CupIdeaVote => $parameter->user_id,
            default => null,
        };

        return $authorId !== null && in_array((int) $authorId, $blockedIds, true);
    }

    private function isBlockManagementRoute(Request $request): bool
    {
        $name = (string) $request->route()?->getName();

        return str_ends_with($name, 'users.block')
            || str_ends_with($name, 'users.unblock')
            || str_contains($name, 'settings.privacy.blocks');
    }
}
