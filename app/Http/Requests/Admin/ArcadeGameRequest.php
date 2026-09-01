<?php

namespace App\Http\Requests\Admin;

use App\Services\Arcade\ArcadeDynamicClientPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArcadeGameRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isAdmin() ?? false; }

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'key' => [$creating ? 'required' : 'sometimes', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('arcade_games', 'key')],
            'name_de' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'description_de' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['native', 'web'])],
            'status' => ['required', Rule::in(['draft', 'active', 'coming_soon', 'maintenance', 'event', 'disabled'])],
            'cover' => ['nullable', 'image', 'max:5120'],
            'sort_order' => ['required', 'integer'],
            'min_players' => ['required', 'integer', 'min:1'],
            'max_players' => ['required', 'integer', 'gte:min_players'],
            'casual_enabled' => ['nullable', 'boolean'],
            'ranked_enabled' => ['nullable', 'boolean'],
            'game_version' => ['required', 'integer', 'min:1'],
            'client_engine_key' => ['nullable', 'string', 'max:100'],
            'min_client_version' => ['nullable', 'string', 'max:50'],
            'launch_url' => ['nullable', 'string', 'max:2048', function ($attribute, $value, $fail): void {
                if ($value && ! app(ArcadeDynamicClientPolicy::class)->isTrustedHttpsUrl((string) $value)) {
                    $fail('The launch URL must use HTTPS and an HNT-controlled trusted Arcade host.');
                }
            }],
            'badge_de' => ['nullable', 'string', 'max:255'],
            'badge_en' => ['nullable', 'string', 'max:255'],
        ];
    }
}
