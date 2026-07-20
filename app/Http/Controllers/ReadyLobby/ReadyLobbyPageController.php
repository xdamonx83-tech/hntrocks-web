<?php

namespace App\Http\Controllers\ReadyLobby;

use App\Http\Controllers\Controller;
use App\Models\LiveLobby;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadyLobbyPageController extends Controller
{
    public function index(Request $request): View
    {
        return $this->render($request);
    }

    public function show(Request $request, LiveLobby $lobby): View
    {
        $lobby->expireIfNeeded();

        return $this->render($request, $lobby->public_id);
    }

    private function render(Request $request, ?string $initialLobbyId = null): View
    {
        return view('themes.hnt_preview.ready-lobbies.index', [
            'initialLobbyId' => $initialLobbyId,
            'viewer' => $request->user(),
        ]);
    }
}
