<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArcadeGameRequest;
use App\Models\Arcade\ArcadeGame;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminArcadeGameController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        return view('admin.arcade-games.index', ['games' => ArcadeGame::query()->orderBy('sort_order')->get()]);
    }

    public function edit(Request $request, ArcadeGame $game): View
    {
        $this->guardAdmin($request);

        return view('admin.arcade-games.edit', compact('game'));
    }

    public function update(ArcadeGameRequest $request, ArcadeGame $game): RedirectResponse
    {
        $this->guardAdmin($request);

        $data = $request->safe()->except('cover');
        $data['casual_enabled'] = $request->boolean('casual_enabled');
        $data['ranked_enabled'] = $request->boolean('ranked_enabled');
        if (array_key_exists('reward_settings', $data)) {
            $data['reward_settings'] = array_merge((array) ($game->reward_settings ?? []), (array) $data['reward_settings']);
        }
        $data['updated_by'] = $request->user()->id;
        if ($request->hasFile('cover')) $data['cover_path'] = $request->file('cover')->store('arcade/covers', 'public');
        $game->update($data);

        return redirect()->route('admin.arcade-games.index')->with('status', 'Arcade-Spiel aktualisiert.');
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
