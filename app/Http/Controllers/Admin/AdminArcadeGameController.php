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

        return view('admin.arcade-games.index', ['games' => ArcadeGame::query()->with('publishedRelease')->orderBy('sort_order')->get()]);
    }

    public function create(Request $request): View
    {
        $this->guardAdmin($request);

        return view('admin.arcade-games.create');
    }

    public function store(ArcadeGameRequest $request): RedirectResponse
    {
        $this->guardAdmin($request);

        $data = $request->safe()->except('cover');
        $data['status'] = 'draft';
        $data['casual_enabled'] = $request->boolean('casual_enabled');
        $data['ranked_enabled'] = $request->boolean('ranked_enabled');
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        if ($request->hasFile('cover')) $data['cover_path'] = $request->file('cover')->store('arcade/covers', 'public');

        $game = ArcadeGame::query()->create($data);

        return redirect()->route('admin.arcade-games.edit', $game)->with('status', 'Arcade-Spiel als Draft angelegt.');
    }

    public function edit(Request $request, ArcadeGame $game): View
    {
        $this->guardAdmin($request);
        $game->load(['releases' => fn ($query) => $query->latest('id')]);

        return view('admin.arcade-games.edit', compact('game'));
    }

    public function update(ArcadeGameRequest $request, ArcadeGame $game): RedirectResponse
    {
        $this->guardAdmin($request);

        $data = $request->safe()->except(['cover', 'key']);
        $data['casual_enabled'] = $request->boolean('casual_enabled');
        $data['ranked_enabled'] = $request->boolean('ranked_enabled');
        $data['updated_by'] = $request->user()->id;
        if ($request->hasFile('cover')) $data['cover_path'] = $request->file('cover')->store('arcade/covers', 'public');
        $game->update($data);

        return redirect()->route('admin.arcade-games.edit', $game)->with('status', 'Arcade-Spiel aktualisiert.');
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
