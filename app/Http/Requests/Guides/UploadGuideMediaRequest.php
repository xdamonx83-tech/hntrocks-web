<?php

namespace App\Http\Requests\Guides;

use App\Models\Guide;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadGuideMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $guide = $this->route('guide');

        return $guide instanceof Guide && $this->user()?->can('update', $guide);
    }

    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::in(['cover', 'content'])],
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.(int) config('guides.upload_max_kb', 8192),
                'dimensions:min_width=320,min_height=180,max_width=8000,max_height=8000',
            ],
        ];
    }
}
