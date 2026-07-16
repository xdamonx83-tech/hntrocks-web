<?php

namespace App\Http\Requests\Teams;

use App\Models\TeamSessionResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RespondTeamSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('respondToTeamSession', $this->route('team'));
    }

    public function rules(): array
    {
        return ['response' => ['required', 'string', Rule::in([TeamSessionResponse::RESPONSE_GOING, TeamSessionResponse::RESPONSE_MAYBE, TeamSessionResponse::RESPONSE_DECLINED])]];
    }
}
