<?php

use App\Models\Conversation;
use App\Models\Arcade\ArcadeMatchPlayer;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    return Conversation::query()
        ->whereKey($conversationId)
        ->whereHas('users', fn ($users) => $users->where('users.id', $user->id))
        ->exists();
});

Broadcast::channel('arcade.match.{matchId}', fn ($user, $matchId) => ArcadeMatchPlayer::query()
    ->where('match_id', $matchId)->where('user_id', $user->id)->exists());
