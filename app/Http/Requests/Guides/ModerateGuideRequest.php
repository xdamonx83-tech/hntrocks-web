<?php

namespace App\Http\Requests\Guides;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModerateGuideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'changes', 'reject'])],
            'reason' => [
                Rule::requiredIf(fn (): bool => in_array($this->input('action'), ['changes', 'reject'], true)),
                'nullable',
                'string',
                'min:10',
                'max:3000',
            ],
        ];
    }
}
