<?php

namespace App\Http\Controllers\Api\V1\Arcade;

use App\Enums\Arcade\ArcadeMatchMode;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ArcadeInvitationResource;
use App\Http\Resources\Api\ArcadeMatchResource;
use App\Models\Arcade\ArcadeGame;
use App\Models\Arcade\ArcadeInvitation;
use App\Models\User;
use App\Services\Arcade\ArcadeInvitationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArcadeInvitationController extends Controller
{
    public function __construct(private readonly ArcadeInvitationService $invitations) {}
    public function index(Request $request) { return ArcadeInvitationResource::collection(ArcadeInvitation::query()->where(fn ($q) => $q->where('inviter_id', $request->user()->id)->orWhere('invitee_id', $request->user()->id))->with(['game', 'inviter', 'invitee'])->latest()->paginate(30)); }
    public function store(Request $request, ArcadeGame $game): ArcadeInvitationResource
    {
        $data = $request->validate(['invitee_id' => ['required', 'integer', 'exists:users,id'], 'mode' => ['required', Rule::enum(ArcadeMatchMode::class)]]);
        return new ArcadeInvitationResource($this->invitations->create($request->user(), User::findOrFail($data['invitee_id']), $game, ArcadeMatchMode::from($data['mode'])));
    }
    public function accept(Request $request, ArcadeInvitation $invitation): ArcadeMatchResource { return new ArcadeMatchResource($this->invitations->accept($invitation, $request->user())->match); }
    public function decline(Request $request, ArcadeInvitation $invitation): ArcadeInvitationResource { return new ArcadeInvitationResource($this->invitations->decline($invitation, $request->user())); }
    public function cancel(Request $request, ArcadeInvitation $invitation): ArcadeInvitationResource { return new ArcadeInvitationResource($this->invitations->cancel($invitation, $request->user())); }
}
