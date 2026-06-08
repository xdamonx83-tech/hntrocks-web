<?php

namespace App\Http\Controllers\Friends;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FriendshipController extends Controller
{
    public function store(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();
        abort_if((int) $viewer->id === (int) $user->id, 422);

        if ($viewer->hasBlocked($user) || $user->hasBlocked($viewer)) {
            abort(403);
        }

        [$one, $two] = Friendship::pairIds($viewer, $user);
        $friendship = Friendship::query()->between($viewer, $user)->first();

        if ($friendship && $friendship->isAccepted()) {
            return $this->respond($request, __('ui.friend_already_connected'), $friendship);
        }

        if ($friendship && $friendship->isPending()) {
            return $this->respond($request, $friendship->isRequester($viewer) ? __('ui.friend_request_already_sent') : __('ui.friend_request_waiting_answer'), $friendship);
        }

        $friendship = Friendship::updateOrCreate(
            ['user_one_id' => $one, 'user_two_id' => $two],
            [
                'requester_id' => $viewer->id,
                'recipient_id' => $user->id,
                'status' => Friendship::STATUS_PENDING,
                'accepted_at' => null,
                'declined_at' => null,
            ]
        );

        return $this->respond($request, __('ui.friend_request_sent_status'), $friendship);
    }

    public function accept(Request $request, Friendship $friendship, NotificationService $notifications): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();
        abort_unless($friendship->isPending() && $friendship->isRecipient($viewer), 403);

        $friendship->update([
            'status' => Friendship::STATUS_ACCEPTED,
            'accepted_at' => now(),
            'declined_at' => null,
        ]);

        $notifications->send(
            $friendship->requester,
            $viewer,
            'friend_request_accepted',
            __('ui.friend_request_accepted_title'),
            __('ui.friend_request_accepted_body', ['name' => $viewer->name]),
            route('profile.public', $viewer)
        );

        return $this->respond($request, __('ui.friend_request_accepted_status'), $friendship->fresh());
    }

    public function decline(Request $request, Friendship $friendship): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();
        abort_unless($friendship->isPending() && $friendship->isRecipient($viewer), 403);

        $friendship->update([
            'status' => Friendship::STATUS_DECLINED,
            'accepted_at' => null,
            'declined_at' => now(),
        ]);

        return $this->respond($request, __('ui.friend_request_declined_status'), $friendship->fresh());
    }

    public function destroy(Request $request, Friendship $friendship): RedirectResponse|JsonResponse
    {
        $viewer = $request->user();
        abort_unless($friendship->isParticipant($viewer), 403);

        if ($friendship->isPending() && ! $friendship->isRequester($viewer)) {
            abort(403);
        }

        $message = $friendship->isAccepted()
            ? __('ui.friend_removed_status')
            : __('ui.friend_request_withdrawn_status');

        $friendship->delete();

        return $this->respond($request, $message);
    }

    private function respond(Request $request, string $message, ?Friendship $friendship = null): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'friendship_status' => $friendship?->status,
                'friend_request_count' => Friendship::query()
                    ->where('recipient_id', $request->user()->id)
                    ->where('status', Friendship::STATUS_PENDING)
                    ->count(),
            ]);
        }

        return back()->with('status', $message);
    }
}
