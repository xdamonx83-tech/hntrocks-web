<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppRemoteFeedCard;
use App\Services\AppConfig\AppRemoteConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JsonException;

class AdminAppRemoteFeedCardController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        return view('admin.app-remote-feed-cards.index', [
            'cards' => AppRemoteFeedCard::query()->latest()->paginate(20),
            'editingCard' => null,
        ]);
    }

    public function edit(Request $request, AppRemoteFeedCard $card): View
    {
        $this->guardAdmin($request);

        return view('admin.app-remote-feed-cards.index', [
            'cards' => AppRemoteFeedCard::query()->latest()->paginate(20),
            'editingCard' => $card,
        ]);
    }

    public function store(Request $request, AppRemoteConfigService $remoteConfig): RedirectResponse
    {
        $this->guardAdmin($request);

        $data = $this->validated($request, $remoteConfig);

        AppRemoteFeedCard::query()->create(array_merge($data, [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]));

        return redirect()->route('admin.app-remote-feed-cards.index')->with('status', 'Feed Card erstellt.');
    }

    public function update(Request $request, AppRemoteFeedCard $card, AppRemoteConfigService $remoteConfig): RedirectResponse
    {
        $this->guardAdmin($request);

        $card->update(array_merge($this->validated($request, $remoteConfig, $card), [
            'updated_by' => $request->user()->id,
        ]));

        return redirect()->route('admin.app-remote-feed-cards.index')->with('status', 'Feed Card gespeichert.');
    }

    public function deactivate(Request $request, AppRemoteFeedCard $card): RedirectResponse
    {
        $this->guardAdmin($request);

        $card->update([
            'is_active' => false,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Feed Card deaktiviert.');
    }

    private function validated(Request $request, AppRemoteConfigService $remoteConfig, ?AppRemoteFeedCard $card = null): array
    {
        $data = $request->validate([
            'remote_id' => [
                'required',
                'string',
                'max:191',
                'regex:/^[a-z0-9][a-z0-9_\-:]*$/',
                Rule::unique('app_remote_feed_cards', 'remote_id')->ignore($card?->id),
            ],
            'title_de' => ['required', 'string', 'max:160'],
            'title_en' => ['nullable', 'string', 'max:160'],
            'body_de' => ['required', 'string', 'max:1200'],
            'body_en' => ['nullable', 'string', 'max:1200'],
            'cta_label_de' => ['nullable', 'string', 'max:80'],
            'cta_label_en' => ['nullable', 'string', 'max:80'],
            'action_url' => ['nullable', 'string', 'max:255'],
            'style_variant' => ['nullable', 'string', 'max:40'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'dismissible' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'audience_type' => ['nullable', 'string', 'max:40'],
            'audience_payload' => ['nullable', 'string', 'max:4000'],
        ]);

        $actionUrl = $remoteConfig->validateActionUrl($data['action_url'] ?? null);
        if (($data['action_url'] ?? null) && $actionUrl === null) {
            throw ValidationException::withMessages(['action_url' => 'Dieses Ziel ist nicht erlaubt.']);
        }

        $audiencePayload = null;
        if (trim((string) ($data['audience_payload'] ?? '')) !== '') {
            try {
                $audiencePayload = json_decode((string) $data['audience_payload'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw ValidationException::withMessages(['audience_payload' => 'Audience Payload muss gültiges JSON sein.']);
            }
        }

        return [
            'remote_id' => $data['remote_id'],
            'title_de' => $data['title_de'],
            'title_en' => $data['title_en'] ?? null,
            'body_de' => $data['body_de'],
            'body_en' => $data['body_en'] ?? null,
            'cta_label_de' => $data['cta_label_de'] ?? null,
            'cta_label_en' => $data['cta_label_en'] ?? null,
            'action_url' => $actionUrl,
            'style_variant' => $remoteConfig->cardStyleVariant($data['style_variant'] ?? null),
            'priority' => (int) ($data['priority'] ?? 100),
            'is_active' => $request->boolean('is_active'),
            'dismissible' => $request->boolean('dismissible', true),
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'audience_type' => $remoteConfig->audienceType($data['audience_type'] ?? null),
            'audience_payload' => is_array($audiencePayload) ? $audiencePayload : null,
        ];
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
