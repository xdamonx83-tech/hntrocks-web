<?php

namespace App\Http\Requests\Guides;

use App\Models\Guide;
use App\Services\Guides\GuideContentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveGuideRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $guide = $this->route('guide');

        return $guide instanceof Guide && $this->user()?->can('update', $guide);
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:160'],
            'summary' => ['nullable', 'string', 'max:420'],
            'category_id' => ['nullable', 'integer', Rule::exists('guide_categories', 'id')->where('is_active', true)],
            'cover_media_id' => ['nullable', 'integer'],
            'tags' => ['nullable', 'array', 'max:8'],
            'tags.*' => ['nullable', 'string', 'max:30'],
            'language' => ['nullable', Rule::in(['de', 'en'])],
            'difficulty' => ['nullable', Rule::in(['beginner', 'advanced', 'expert'])],
            'platform' => ['nullable', Rule::in(['all', 'pc', 'playstation', 'xbox'])],
            'content_blocks' => ['nullable', 'array', 'max:60'],
            'content_blocks.*' => ['array'],
            'content_blocks.*.id' => ['nullable', 'string', 'max:80'],
            'content_blocks.*.type' => ['required_with:content_blocks', Rule::in(GuideContentService::BLOCK_TYPES)],
            'content_blocks.*.level' => ['nullable', 'integer', 'between:2,4'],
            'content_blocks.*.text' => ['nullable', 'string', 'max:5000'],
            'content_blocks.*.title' => ['nullable', 'string', 'max:120'],
            'content_blocks.*.items' => ['nullable', 'array', 'max:30'],
            'content_blocks.*.items.*' => ['nullable', 'string', 'max:800'],
            'content_blocks.*.media_id' => ['nullable', 'integer'],
            'content_blocks.*.caption' => ['nullable', 'string', 'max:240'],
        ];
    }
}
