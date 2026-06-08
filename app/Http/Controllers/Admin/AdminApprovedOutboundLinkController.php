<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovedOutboundLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminApprovedOutboundLinkController extends Controller
{
    public function index(Request $request): View
    {
        $links = ApprovedOutboundLink::query()
            ->with('creator:id,name,username')
            ->when($request->filled('status'), function ($query) use ($request): void {
                if ($request->string('status')->toString() === 'active') {
                    $query->where('is_active', true);
                }

                if ($request->string('status')->toString() === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.outbound-links.index', [
            'links' => $links,
            'status' => $request->string('status')->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $slug = $this->uniqueSlug($data['slug'] ?? $data['title']);

        ApprovedOutboundLink::create([
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'target_url' => $data['target_url'],
            'target_domain' => $this->domainFromUrl($data['target_url']),
            'is_active' => $request->boolean('is_active'),
            'created_by' => $request->user()?->id,
            'admin_note' => $data['admin_note'] ?? null,
        ]);

        return redirect()
            ->route('admin.outbound-links.index')
            ->with('status', 'Freigegebener externer Link wurde erstellt.');
    }

    public function update(Request $request, ApprovedOutboundLink $link): RedirectResponse
    {
        $data = $this->validatedData($request, $link);
        $slug = $this->uniqueSlug($data['slug'] ?? $data['title'], $link);

        $link->update([
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'target_url' => $data['target_url'],
            'target_domain' => $this->domainFromUrl($data['target_url']),
            'is_active' => $request->boolean('is_active'),
            'admin_note' => $data['admin_note'] ?? null,
        ]);

        return redirect()
            ->route('admin.outbound-links.index')
            ->with('status', 'Freigegebener externer Link wurde gespeichert.');
    }

    public function destroy(ApprovedOutboundLink $link): RedirectResponse
    {
        $link->delete();

        return redirect()
            ->route('admin.outbound-links.index')
            ->with('status', 'Freigegebener externer Link wurde gelöscht.');
    }

    private function validatedData(Request $request, ?ApprovedOutboundLink $link = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'slug' => [
                'nullable',
                'string',
                'max:140',
                'regex:/^[A-Za-z0-9\-]+$/',
                Rule::unique('approved_outbound_links', 'slug')->ignore($link?->id),
            ],
            'description' => ['nullable', 'string', 'max:1200'],
            'target_url' => ['required', 'url', 'max:2048', 'regex:/^https?:\/\//i'],
            'admin_note' => ['nullable', 'string', 'max:1200'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function uniqueSlug(string $value, ?ApprovedOutboundLink $ignore = null): string
    {
        $base = Str::slug($value);

        if ($base === '') {
            $base = 'link';
        }

        $slug = $base;
        $counter = 2;

        while (ApprovedOutboundLink::query()
            ->where('slug', $slug)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function domainFromUrl(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return Str::lower($host);
    }
}
