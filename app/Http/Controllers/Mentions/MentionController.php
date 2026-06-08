<?php

namespace App\Http\Controllers\Mentions;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\MentionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MentionController extends Controller
{
    public function search(Request $request, MentionService $mentions): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:32'],
            'context' => ['nullable', 'string', 'in:feed,feed_comment,team_feed,team_feed_comment,lfg,team_lfg'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ]);

        $context = (string) ($validated['context'] ?? 'feed');
        $team = null;

        if (in_array($context, ['team_feed', 'team_feed_comment', 'team_lfg'], true) && ! empty($validated['team_id'])) {
            $team = Team::query()->with('members')->find((int) $validated['team_id']);
        }

        $users = $mentions->searchableUsers(
            $request->user(),
            (string) ($validated['q'] ?? ''),
            $context,
            8,
            $team
        );

        return response()->json([
            'ok' => true,
            'users' => $users->map(fn ($user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'avatar_url' => $user->avatarUrl(),
                'level' => $user->level ?? 1,
                'profile_url' => route('profile.public', $user),
            ])->values(),
        ]);
    }
}
