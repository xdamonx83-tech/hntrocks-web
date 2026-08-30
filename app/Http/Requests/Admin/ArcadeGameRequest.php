<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArcadeGameRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user()?->is_admin; }
    public function rules(): array
    {
        return ['name_de' => ['required', 'string', 'max:255'], 'name_en' => ['required', 'string', 'max:255'], 'description_de' => ['nullable', 'string'], 'description_en' => ['nullable', 'string'], 'type' => ['required', Rule::in(['native', 'web'])], 'status' => ['required', Rule::in(['active', 'coming_soon', 'maintenance', 'event', 'disabled'])], 'cover' => ['nullable', 'image', 'max:5120'], 'sort_order' => ['required', 'integer'], 'min_players' => ['required', 'integer', 'min:1'], 'max_players' => ['required', 'integer', 'gte:min_players'], 'casual_enabled' => ['nullable', 'boolean'], 'ranked_enabled' => ['nullable', 'boolean'], 'game_version' => ['required', 'integer', 'min:1'], 'client_engine_key' => ['nullable', 'string', 'max:100'], 'launch_url' => ['nullable', 'url:https', function ($attribute, $value, $fail): void { if ($value && (! in_array(strtolower((string) parse_url($value, PHP_URL_HOST)), config('arcade.trusted_web_hosts'), true))) $fail('The launch URL host is not trusted.'); }], 'badge_de' => ['nullable', 'string', 'max:255'], 'badge_en' => ['nullable', 'string', 'max:255']];
    }
}
