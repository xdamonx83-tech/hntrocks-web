<?php

namespace App\Http\Requests\Teams;

class UpdateTeamSessionRequest extends StoreTeamSessionRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('updateTeamSession', $this->route('team'));
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['title'][0] = 'sometimes';
        $rules['starts_at'][0] = 'sometimes';
        $rules['timezone'][0] = 'sometimes';

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        // Partial updates must preserve an omitted timezone.
    }
}
