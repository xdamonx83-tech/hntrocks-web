<?php

namespace App\Http\Requests\Admin;

use App\Models\Arcade\ArcadeGame;
use App\Services\Arcade\ArcadeDynamicClientPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArcadeGameReleaseRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isAdmin() ?? false; }

    public function rules(): array
    {
        $game = $this->route('game');
        $gameId = $game instanceof ArcadeGame ? $game->id : 0;
        $trustedUrl = function ($attribute, $value, $fail): void {
            if ($value && ! app(ArcadeDynamicClientPolicy::class)->isTrustedHttpsUrl((string) $value)) {
                $fail('The URL must use HTTPS and an HNT-controlled trusted Arcade host.');
            }
        };

        return [
            'version' => [
                'required',
                'string',
                'max:64',
                'regex:/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/',
                Rule::unique('arcade_game_releases', 'version')->where(fn ($query) => $query->where('game_id', $gameId)),
            ],
            'entrypoint_url' => ['required', 'string', 'max:2048', $trustedUrl],
            'manifest_url' => ['nullable', 'string', 'max:2048', $trustedUrl],
            'integrity_sha256' => ['required', 'string', 'regex:/^[a-fA-F0-9]{64}$/'],
        ];
    }
}
