@php
    /** @var \App\Models\LfgPost|null $post */
    $isEdit = (bool) ($isEdit ?? false);
    $viewer = auth()->user();
    $optionLabel = fn (string $field, string $value): string => \App\Models\LfgPost::localizedOptionLabelFor($field, $value) ?? $value;
    $fieldValue = fn (string $field, $default = '') => old($field, $post?->{$field} ?? $default);
    $expiresAt = old('expires_at', $post?->expires_at?->format('Y-m-d\TH:i') ?? '');
    $slotTotal = min(3, max(2, (int) old('slots_total', $post?->slots_total ?? 2)));
    $slotFilled = min(3, max(1, (int) old('slots_filled', $post?->slots_filled ?? 1)));
    $statusValue = old('status', $post?->status ?? 'open');
    $visibilityValue = old('visibility', $post?->visibility ?? 'public');
    $voiceRequired = (bool) old('voice_required', $post?->voice_required ?? false);
    $previewTitle = old('title', $post?->title ?? __('ui.lfg_create_preview_title'));
    $previewBody = old('body', $post?->body ?? __('ui.lfg_create_preview_text'));
    $previewTags = collect([
        old('platform', $post?->platform),
        old('playstyle', $post?->playstyle),
        old('region', $post?->region),
        old('language', $post?->language),
    ])->filter()->values();
@endphp

<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <div class="flex items-center gap-2 text-sm font-semibold text-blue-600">
            <a href="{{ route('lfg.index') }}" class="hover:underline">{{ __('ui.lfg_index_title') }}</a>
            <ion-icon name="chevron-forward-outline" class="text-base"></ion-icon>
            <span>{{ $title }}</span>
        </div>
        <h1 class="mt-2 text-3xl font-bold text-black dark:text-white">{{ $title }}</h1>
        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-white/70">{{ $intro }}</p>
    </div>

    <a href="{{ $classicUrl }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-slate-50 dark:bg-dark2 dark:text-white">
        <ion-icon name="open-outline" class="text-lg"></ion-icon>
        {{ __('ui.classic') }}
    </a>
</div>

@if ($errors->any())
    <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
        <div>{{ __('ui.please_check') }}</div>
        <ul class="mt-1 list-disc pl-5 font-medium">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px]" data-hh-socialite-preview-form>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="space-y-5 min-w-0">
        <section class="rounded-xl bg-white p-5 shadow-sm border1 dark:bg-dark2">
            <h2 class="text-lg font-bold text-black dark:text-white">{{ __('ui.lfg_create_tab_info') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-white/70">{{ __('ui.lfg_create_preview_text') }}</p>

            <div class="mt-5 space-y-4">
                <div>
                    <label for="lfg-title" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.lfg_form_title') }}</label>
                    <input id="lfg-title" type="text" name="title" value="{{ old('title', $post?->title) }}" maxlength="120" required class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white" placeholder="{{ __('ui.lfg_form_title_placeholder') }}">
                    @error('title') <p class="mt-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="lfg-body" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.lfg_form_body') }}</label>
                    <textarea id="lfg-body" name="body" rows="7" maxlength="2800" data-hh-mention-context="lfg" class="w-full resize-y rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white" placeholder="{{ __('ui.lfg_form_body_placeholder') }}">{{ old('body', $post?->body) }}</textarea>
                    @error('body') <p class="mt-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm border1 dark:bg-dark2">
            <h2 class="text-lg font-bold text-black dark:text-white">{{ __('ui.lfg_create_tab_details') }}</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div>
                    <label for="lfg-platform" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.lfg_detail_platform') }}</label>
                    <select id="lfg-platform" name="platform" class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white">
                        <option value="">{{ __('ui.select_option') }}</option>
                        @foreach (['PC', 'PlayStation', 'Xbox', 'Crossplay'] as $option)
                            <option value="{{ $option }}" @selected($fieldValue('platform') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="lfg-playstyle" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.lfg_detail_playstyle') }}</label>
                    <select id="lfg-playstyle" name="playstyle" class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white">
                        <option value="">{{ __('ui.select_option') }}</option>
                        @foreach (['Entspannt', 'Taktisch', 'Aggressiv', 'Competitive', 'Einsteigerfreundlich'] as $option)
                            <option value="{{ $option }}" @selected($fieldValue('playstyle') === $option)>{{ $optionLabel('playstyle', $option) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="lfg-region" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.lfg_detail_region') }}</label>
                    <select id="lfg-region" name="region" class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white">
                        <option value="">{{ __('ui.select_option') }}</option>
                        @foreach (['EU', 'US East', 'US West', 'Asia', 'Oceania'] as $option)
                            <option value="{{ $option }}" @selected($fieldValue('region') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="lfg-language" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.language') }}</label>
                    <select id="lfg-language" name="language" class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white">
                        <option value="">{{ __('ui.select_option') }}</option>
                        @foreach (['Deutsch', 'Englisch', 'Deutsch / Englisch', 'Mehrsprachig'] as $option)
                            <option value="{{ $option }}" @selected($fieldValue('language') === $option)>{{ $optionLabel('language', $option) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="lfg-preferred-time" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.lfg_form_preferred_time') }}</label>
                    <select id="lfg-preferred-time" name="preferred_time" class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white">
                        <option value="">{{ __('ui.select_option') }}</option>
                        @foreach (['Morgens', 'Mittags', 'Abends', 'Nachts', 'Wochenende', 'Flexibel'] as $option)
                            <option value="{{ $option }}" @selected($fieldValue('preferred_time') === $option)>{{ $optionLabel('preferred_time', $option) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="lfg-experience" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.lfg_detail_experience') }}</label>
                    <select id="lfg-experience" name="experience_level" class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white">
                        <option value="">{{ __('ui.select_option') }}</option>
                        @foreach (['Einsteiger', 'Fortgeschritten', 'Erfahren', 'Competitive', 'Egal'] as $option)
                            <option value="{{ $option }}" @selected($fieldValue('experience_level') === $option)>{{ $optionLabel('experience_level', $option) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>
    </div>

    <aside class="space-y-5 lg:sticky lg:top-24 self-start">
        <section class="rounded-xl bg-white p-5 shadow-sm border1 dark:bg-dark2">
            <h2 class="text-lg font-bold text-black dark:text-white">{{ __('ui.lfg_create_tab_settings') }}</h2>
            <div class="mt-5 space-y-4">
                <div>
                    <label for="lfg-slots-total" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.lfg_form_slots_total') }}</label>
                    <select id="lfg-slots-total" name="slots_total" required class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white">
                        @foreach ([2, 3] as $option)
                            <option value="{{ $option }}" @selected($slotTotal === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($isEdit)
                    <div>
                        <label for="lfg-slots-filled" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.lfg_form_slots_filled') }}</label>
                        <input id="lfg-slots-filled" name="slots_filled" type="number" min="1" max="3" value="{{ $slotFilled }}" required class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white">
                    </div>

                    <div>
                        <label for="lfg-status" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.status') }}</label>
                        <select id="lfg-status" name="status" required class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white">
                            <option value="open" @selected($statusValue === 'open')>{{ __('ui.lfg_status_open') }}</option>
                            <option value="full" @selected($statusValue === 'full')>{{ __('ui.lfg_status_full') }}</option>
                            <option value="closed" @selected($statusValue === 'closed')>{{ __('ui.lfg_status_closed') }}</option>
                        </select>
                    </div>
                @endif

                <div>
                    <label for="lfg-visibility" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.visibility') }}</label>
                    <select id="lfg-visibility" name="visibility" required class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white">
                        <option value="public" @selected($visibilityValue === 'public')>{{ __('ui.lfg_visibility_public') }}</option>
                        <option value="private" @selected($visibilityValue === 'private')>{{ __('ui.lfg_visibility_private') }}</option>
                    </select>
                </div>

                <div>
                    <label for="lfg-expires-at" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.lfg_form_expires_at') }}</label>
                    <input id="lfg-expires-at" name="expires_at" type="datetime-local" value="{{ $expiresAt }}" class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white">
                </div>

                <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl bg-secondery px-4 py-3 dark:bg-dark3">
                    <span>
                        <span class="block text-sm font-bold text-black dark:text-white">{{ __('ui.lfg_form_voice_required') }}</span>
                        <span class="block text-xs text-gray-500 dark:text-white/60">{{ __('ui.lfg_form_voice_required_text') }}</span>
                    </span>
                    <input type="checkbox" name="voice_required" value="1" @checked($voiceRequired) class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                </label>
            </div>
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm border1 dark:bg-dark2">
            <h2 class="text-lg font-bold text-black dark:text-white">{{ __('ui.preview') }}</h2>
            <div class="mt-4 rounded-xl bg-secondery p-4 dark:bg-dark3">
                <div class="flex items-center gap-3">
                    <img src="{{ $viewer->avatarUrl() }}" alt="{{ $viewer->name }}" class="h-12 w-12 rounded-full object-cover">
                    <div class="min-w-0">
                        <p class="font-bold text-black dark:text-white truncate">{{ $viewer->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-white/60"><span>@</span>{{ $viewer->username }}</p>
                    </div>
                </div>
                <h3 class="mt-4 text-base font-bold text-black dark:text-white" data-hh-preview-title>{{ $previewTitle }}</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-white/70" data-hh-preview-body>{{ \Illuminate\Support\Str::limit($previewBody, 180) }}</p>
                <div class="mt-3 flex flex-wrap gap-2 {{ $previewTags->isEmpty() ? 'hidden' : '' }}" data-hh-preview-tags>
                    @foreach ($previewTags as $tag)
                        <span class="rounded-lg bg-white px-2.5 py-1 text-xs font-bold text-gray-600 dark:bg-dark2 dark:text-white/70">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="flex gap-3">
            <a href="{{ $isEdit && $post ? route('lfg.show', $post) : route('lfg.index') }}" class="button flex-1 bg-white text-gray-700 shadow-sm hover:bg-slate-50 dark:bg-dark2 dark:text-white">{{ __('ui.cancel') }}</a>
            <button type="submit" class="button flex-1 bg-primary text-white">{{ $submitLabel }}</button>
        </div>

        @if ($isEdit && $post)
            <button type="submit" form="lfg-delete-form-{{ $post->id }}" class="w-full rounded-xl bg-rose-50 px-4 py-3 text-sm font-bold text-rose-600 hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-300">{{ __('ui.lfg_archive') }}</button>
        @endif
    </aside>
</form>

@if ($isEdit && $post)
    <form id="lfg-delete-form-{{ $post->id }}" method="POST" action="{{ route('lfg.destroy', $post) }}" onsubmit="return confirm('{{ __('ui.lfg_archive_confirm') }}')">
        @csrf
        @method('DELETE')
    </form>
@endif

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-hh-socialite-preview-form]').forEach((form) => {
            const previewTitle = form.querySelector('[data-hh-preview-title]');
            const previewBody = form.querySelector('[data-hh-preview-body]');
            const previewTags = form.querySelector('[data-hh-preview-tags]');
            const titleInput = form.querySelector('[name="title"]');
            const bodyInput = form.querySelector('[name="body"]');
            const tagFields = ['platform', 'playstyle', 'region', 'language', 'preferred_time', 'experience_level']
                .map((name) => form.querySelector(`[name="${name}"]`))
                .filter(Boolean);
            const defaults = {
                title: @json(__('ui.lfg_create_preview_title')),
                body: @json(__('ui.lfg_create_preview_text')),
            };
            const limit = (value, max = 180) => {
                const text = String(value || '').trim();
                return text.length > max ? `${text.slice(0, max - 1)}…` : text;
            };
            const selectedText = (field) => {
                if (!field || !field.value) return '';
                const option = field.options ? field.options[field.selectedIndex] : null;
                return option ? option.text.trim() : field.value.trim();
            };
            const render = () => {
                if (previewTitle) previewTitle.textContent = titleInput?.value.trim() || defaults.title;
                if (previewBody) previewBody.textContent = limit(bodyInput?.value || defaults.body);
                if (previewTags) {
                    const tags = tagFields.map(selectedText).filter(Boolean);
                    previewTags.innerHTML = '';
                    previewTags.classList.toggle('hidden', tags.length === 0);
                    tags.slice(0, 6).forEach((tag) => {
                        const span = document.createElement('span');
                        span.className = 'rounded-lg bg-white px-2.5 py-1 text-xs font-bold text-gray-600 dark:bg-dark2 dark:text-white/70';
                        span.textContent = tag;
                        previewTags.appendChild(span);
                    });
                }
            };
            [titleInput, bodyInput, ...tagFields].filter(Boolean).forEach((field) => {
                field.addEventListener('input', render);
                field.addEventListener('change', render);
            });
            render();
        });
    });
</script>
