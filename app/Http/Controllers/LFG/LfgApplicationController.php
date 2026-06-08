<?php

namespace App\Http\Controllers\LFG;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\LfgApplication;
use App\Models\LfgPost;
use App\Models\User;
use App\Services\GamificationService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LfgApplicationController extends Controller
{
    public function store(Request $request, LfgPost $post, NotificationService $notifications, GamificationService $gamification): RedirectResponse
    {
        $post->loadMissing(['applications', 'user']);

        if (! $post->isOpen()) {
            return back()->withErrors(['application' => __('ui.lfg_error_not_open')]);
        }

        if ($post->isOwner($request->user())) {
            return back()->withErrors(['application' => __('ui.lfg_error_own_lfg')]);
        }

        if ($post->applicationFor($request->user())) {
            return back()->withErrors(['application' => __('ui.lfg_error_already_applied')]);
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:800'],
        ]);

        $application = $post->applications()->create([
            'user_id' => $request->user()->id,
            'message' => $validated['message'] ?? null,
            'status' => 'pending',
        ]);

        $gamification->award($request->user(), 'lfg_application_sent', source: $application);
        $notifications->send(
            $post->user,
            $request->user(),
            'lfg_application',
            __('ui.lfg_notification_application_title'),
            __('ui.lfg_notification_application_body', ['user' => $request->user()->name]),
            $this->lfgApplicationsUrl($post)
        );

        return redirect()->route('lfg.show', $post)->with('status', __('ui.lfg_application_sent'));
    }

    public function accept(Request $request, LfgPost $post, LfgApplication $application, NotificationService $notifications, GamificationService $gamification): RedirectResponse
    {
        abort_unless($post->canManage($request->user()), 403);
        abort_unless($application->lfg_post_id === $post->id, 404);

        if (! $post->isOpen()) {
            return back()->withErrors(['application' => __('ui.lfg_error_no_slots')]);
        }

        $application->update([
            'status' => 'accepted',
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        $post->slots_filled = min((int) $post->slots_total, (int) $post->slots_filled + 1);
        if ($post->slots_filled >= $post->slots_total) {
            $post->status = 'full';
        }
        $post->save();

        $application->loadMissing('user');
        $conversation = $this->lfgConversationFor($post, $application->user, $request->user());
        $this->appendLfgSystemMessage($conversation, $request->user(), $this->applicationCreatedMessage($post, $application));
        $this->appendLfgSystemMessage($conversation, $request->user(), __('ui.lfg_chat_application_accepted_system', ['user' => $request->user()->name]));

        $gamification->award($application->user, 'lfg_application_accepted', source: $application);
        $notifications->send(
            $application->user,
            $request->user(),
            'lfg_application_accepted',
            __('ui.lfg_notification_accepted_title'),
            __('ui.lfg_notification_accepted_body'),
            route('messages.show', $conversation)
        );

        return back()->with('status', __('ui.lfg_application_accepted_status'));
    }

    public function reject(Request $request, LfgPost $post, LfgApplication $application, NotificationService $notifications): RedirectResponse
    {
        abort_unless($post->canManage($request->user()), 403);
        abort_unless($application->lfg_post_id === $post->id, 404);

        $application->update([
            'status' => 'rejected',
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        $application->loadMissing('user');

        $notifications->send(
            $application->user,
            $request->user(),
            'lfg_application_rejected',
            __('ui.lfg_notification_rejected_title'),
            __('ui.lfg_notification_rejected_body'),
            route('lfg.show', $post)
        );

        return back()->with('status', __('ui.lfg_application_rejected_status'));
    }


    private function lfgApplicationsUrl(LfgPost $post): string
    {
        return route('lfg.show', $post) . '#lfg-applications';
    }

    private function lfgConversationFor(LfgPost $post, User $applicant, User $actor): Conversation
    {
        $post->loadMissing('user');

        $conversation = Conversation::query()
            ->where('type', 'lfg')
            ->where('context_type', 'lfg_post')
            ->where('context_id', $post->id)
            ->whereHas('users', fn ($query) => $query->where('users.id', $post->user_id))
            ->whereHas('users', fn ($query) => $query->where('users.id', $applicant->id))
            ->withCount('users')
            ->get()
            ->firstWhere('users_count', 2);

        if (! $conversation) {
            $conversation = Conversation::create([
                'type' => 'lfg',
                'title' => __('ui.lfg_conversation_title', ['title' => $post->title]),
                'context_type' => 'lfg_post',
                'context_id' => $post->id,
                'context_label' => __('ui.lfg_conversation_title', ['title' => $post->title]),
                'context_url' => route('lfg.show', $post),
                'created_by' => $actor->id,
            ]);
        }

        $conversation->users()->syncWithoutDetaching([
            $post->user_id => ['last_read_at' => (int) $actor->id === (int) $post->user_id ? now() : null],
            $applicant->id => ['last_read_at' => (int) $actor->id === (int) $applicant->id ? now() : null],
        ]);

        return $conversation;
    }

    private function appendLfgSystemMessage(Conversation $conversation, User $actor, string $body): void
    {
        $conversation->messages()->create([
            'user_id' => $actor->id,
            'type' => 'system',
            'body' => $body,
        ]);

        $conversation->touch();
        $conversation->markReadFor($actor);
    }

    private function applicationCreatedMessage(LfgPost $post, LfgApplication $application): string
    {
        $application->loadMissing('user');

        $body = __('ui.lfg_chat_application_created_system', [
            'user' => $application->user?->name ?: __('ui.player'),
            'title' => $post->title,
        ]);

        $message = trim((string) $application->message);
        if ($message !== '') {
            $body .= "\n\n" . __('ui.lfg_chat_application_message', ['message' => $message]);
        }

        return $body;
    }
}
