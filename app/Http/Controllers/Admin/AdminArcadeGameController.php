<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArcadeGameRequest;
use App\Models\Arcade\ArcadeGame;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminArcadeGameController extends Controller
{
    public function index(): View
    {
        return view('admin.arcade-games.index', ['games' => ArcadeGame::query()->orderBy('sort_order')->get()]);
    }

    public function edit(ArcadeGame $game): View
    {
        return view('admin.arcade-games.edit', compact('game'));
    }

    public function update(ArcadeGameRequest $request, ArcadeGame $game): RedirectResponse
    {
        $data = $request->safe()->except('cover');
        $data['casual_enabled'] = $request->boolean('casual_enabled');
        $data['ranked_enabled'] = $request->boolean('ranked_enabled');
        $data['updated_by'] = $request->user()->id;
        if ($request->hasFile('cover')) $data['cover_path'] = $request->file('cover')->store('arcade/covers', 'public');
        $game->update($data);

        return redirect()->route('admin.arcade-games.index')->with('status', 'Arcade-Spiel aktualisiert.');
    }
}
