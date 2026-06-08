@php
    /** @var \App\Models\Team|null $team */
    $isEdit = (bool) ($isEdit ?? false);
    $viewer = auth()->user();
    $fieldValue = fn (string $field, $default = '') => old($field, $team?->{$field} ?? $default);

    $nameValue = $fieldValue('name', __('ui.create_new_team'));
    $taglineValue = $fieldValue('tagline', __('ui.team_default_tagline'));
    $descriptionValue = $fieldValue('description', __('ui.team_description_placeholder'));
    $platformValue = $fieldValue('platform');
    $playstyleValue = $fieldValue('playstyle');
    $regionValue = $fieldValue('region');
    $languageValue = $fieldValue('language');
    $visibilityValue = $fieldValue('visibility', 'public');
    $recruitmentValue = $fieldValue('recruitment_status', 'open');

    $avatarUrl = $team?->avatarUrl() ?? $viewer->avatarUrl();
    $coverUrl = $team?->coverUrl() ?? asset('assets/vikinger/img/default-cover.svg');
    $previewTags = collect([$platformValue, $playstyleValue, $regionValue, $languageValue])->filter()->values();
@endphp

<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <div class="flex items-center gap-2 text-sm font-semibold text-blue-600">
            <a href="{{ route('teams.index') }}" class="hover:underline">{{ __('ui.teams') }}</a>
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

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px]" data-hh-socialite-team-form>
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="space-y-5 min-w-0">
        <section class="overflow-hidden rounded-xl bg-white shadow-sm border1 dark:bg-dark2">
            <div class="relative h-44 bg-slate-900 md:h-52">
                <img src="{{ $coverUrl }}" alt="{{ $nameValue }}" class="h-full w-full object-cover opacity-80" data-hh-team-preview-cover>
                <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/10 to-transparent"></div>
                <div class="absolute bottom-4 left-5 right-5 flex items-end gap-4">
                    <img src="{{ $avatarUrl }}" alt="{{ $nameValue }}" class="h-20 w-20 rounded-full border-4 border-white bg-white object-cover shadow-lg dark:border-dark2 dark:bg-dark2" data-hh-team-preview-avatar>
                    <div class="min-w-0 pb-1">
                        <h2 class="truncate text-2xl font-bold text-white" data-hh-team-preview-name>{{ $nameValue }}</h2>
                        <p class="truncate text-sm font-semibold text-white/80" data-hh-team-preview-tagline>{{ $taglineValue }}</p>
                    </div>
                </div>
            </div>

            <div class="p-5">
                <h2 class="text-lg font-bold text-black dark:text-white">{{ __('ui.team_info') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-white/70">{{ __('ui.team_create_banner_text') }}</p>

                <div class="mt-5 space-y-4">
                    <div>
                        <label for="team-name" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.team_name') }}</label>
                        <input id="team-name" name="name" type="text" value="{{ old('name', $team?->name) }}" maxlength="80" required data-hh-team-live="name" class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white" placeholder="{{ __('ui.team_name') }}">
                        @error('name') <p class="mt-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="team-tagline" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.team_tagline') }}</label>
                        <input id="team-tagline" name="tagline" type="text" value="{{ old('tagline', $team?->tagline) }}" maxlength="140" data-hh-team-live="tagline" class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white" placeholder="{{ __('ui.team_default_tagline') }}">
                        @error('tagline') <p class="mt-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="team-description" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.team_description') }}</label>
                        <textarea id="team-description" name="description" rows="7" maxlength="2500" data-hh-team-live="description" class="w-full resize-y rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white" placeholder="{{ __('ui.team_description_placeholder') }}">{{ old('description', $team?->description) }}</textarea>
                        @error('description') <p class="mt-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm border1 dark:bg-dark2">
            <h2 class="text-lg font-bold text-black dark:text-white">{{ __('ui.avatar_and_cover') }}</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <label class="block cursor-pointer rounded-xl bg-secondery p-4 text-center ring-1 ring-transparent hover:ring-blue-500 dark:bg-dark3" for="team-avatar">
                    <ion-icon name="people-outline" class="mx-auto text-3xl text-blue-600"></ion-icon>
                    <span class="mt-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.team_avatar') }}</span>
                    <span class="mt-1 block text-xs font-semibold text-gray-500 dark:text-white/60">{{ __('ui.team_avatar_hint') }}</span>
                    <input id="team-avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="sr-only" data-hh-team-file="avatar">
                    @error('avatar') <span class="mt-2 block text-xs font-semibold text-rose-500">{{ $message }}</span> @enderror
                </label>

                <label class="block cursor-pointer rounded-xl bg-secondery p-4 text-center ring-1 ring-transparent hover:ring-blue-500 dark:bg-dark3" for="team-cover">
                    <ion-icon name="images-outline" class="mx-auto text-3xl text-blue-600"></ion-icon>
                    <span class="mt-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.team_cover') }}</span>
                    <span class="mt-1 block text-xs font-semibold text-gray-500 dark:text-white/60">{{ __('ui.team_cover_hint') }}</span>
                    <input id="team-cover" type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="sr-only" data-hh-team-file="cover">
                    @error('cover') <span class="mt-2 block text-xs font-semibold text-rose-500">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm border1 dark:bg-dark2">
            <h2 class="text-lg font-bold text-black dark:text-white">{{ __('ui.team_setup') }}</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div>
                    <label for="team-platform" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.platform') }}</label>
                    <select id="team-platform" name="platform" data-hh-team-live="platform" class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white">
                        <option value="">{{ __('ui.select_option') }}</option>
                        @foreach (['PC', 'PlayStation', 'Xbox', 'Crossplay'] as $option)
                            <option value="{{ $option }}" @selected($platformValue === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    @error('platform') <p class="mt-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="team-playstyle" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.playstyle') }}</label>
                    <select id="team-playstyle" name="playstyle" data-hh-team-live="playstyle" class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white">
                        <option value="">{{ __('ui.select_option') }}</option>
                        @foreach (['Entspannt', 'Taktisch', 'Aggressiv', 'Competitive', 'Einsteigerfreundlich'] as $option)
                            <option value="{{ $option }}" @selected($playstyleValue === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    @error('playstyle') <p class="mt-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="team-region" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.region') }}</label>
                    <select id="team-region" name="region" data-hh-team-live="region" class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white">
                        <option value="">{{ __('ui.select_option') }}</option>
                        @foreach (['EU', 'US East', 'US West', 'Asia', 'Oceania'] as $option)
                            <option value="{{ $option }}" @selected($regionValue === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    @error('region') <p class="mt-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="team-language" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.language') }}</label>
                    <select id="team-language" name="language" data-hh-team-live="language" class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white">
                        <option value="">{{ __('ui.select_option') }}</option>
                        @foreach (['Deutsch', 'Englisch', 'Deutsch / Englisch', 'Mehrsprachig'] as $option)
                            <option value="{{ $option }}" @selected($languageValue === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    @error('language') <p class="mt-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>
    </div>

    <aside class="space-y-5 lg:sticky lg:top-24 self-start">
        <section class="rounded-xl bg-white p-5 shadow-sm border1 dark:bg-dark2">
            <h2 class="text-lg font-bold text-black dark:text-white">{{ __('ui.settings') }}</h2>
            <div class="mt-5 space-y-4">
                <div>
                    <label for="team-visibility" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.team_visibility') }}</label>
                    <select id="team-visibility" name="visibility" data-hh-team-live="visibility" required class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white">
                        <option value="public" @selected($visibilityValue === 'public')>{{ __('ui.visibility_public') }}</option>
                        <option value="private" @selected($visibilityValue === 'private')>{{ __('ui.visibility_private') }}</option>
                    </select>
                    @error('visibility') <p class="mt-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="team-recruitment" class="mb-2 block text-sm font-bold text-black dark:text-white">{{ __('ui.team_recruiting') }}</label>
                    <select id="team-recruitment" name="recruitment_status" data-hh-team-live="recruitment_status" required class="w-full rounded-xl border-0 bg-secondery px-4 py-3 text-sm font-semibold text-black outline-none ring-1 ring-transparent focus:ring-blue-500 dark:bg-dark3 dark:text-white">
                        <option value="open" @selected($recruitmentValue === 'open')>{{ __('ui.recruiting_open') }}</option>
                        <option value="closed" @selected($recruitmentValue === 'closed')>{{ __('ui.recruiting_closed') }}</option>
                    </select>
                    @error('recruitment_status') <p class="mt-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm border1 dark:bg-dark2">
            <h2 class="text-lg font-bold text-black dark:text-white">{{ __('ui.preview') }}</h2>
            <div class="mt-4 overflow-hidden rounded-xl bg-secondery dark:bg-dark3">
                <div class="relative h-28 bg-slate-900">
                    <img src="{{ $coverUrl }}" alt="{{ $nameValue }}" class="h-full w-full object-cover opacity-80" data-hh-team-card-cover>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                </div>
                <div class="p-4">
                    <div class="flex items-center gap-3">
                        <img src="{{ $avatarUrl }}" alt="{{ $nameValue }}" class="h-14 w-14 rounded-full border-4 border-white bg-white object-cover shadow-sm dark:border-dark2 dark:bg-dark2" data-hh-team-card-avatar>
                        <div class="min-w-0">
                            <p class="truncate font-bold text-black dark:text-white" data-hh-team-card-name>{{ $nameValue }}</p>
                            <p class="truncate text-xs font-semibold text-gray-500 dark:text-white/60" data-hh-team-card-tagline>{{ $taglineValue }}</p>
                        </div>
                    </div>

                    <p class="mt-4 text-sm text-gray-600 dark:text-white/70" data-hh-team-card-description>{{ \Illuminate\Support\Str::limit($descriptionValue, 150) }}</p>

                    <div class="mt-3 flex flex-wrap gap-2 {{ $previewTags->isEmpty() ? 'hidden' : '' }}" data-hh-team-card-tags>
                        @foreach ($previewTags as $tag)
                            <span class="rounded-lg bg-white px-2.5 py-1 text-xs font-bold text-gray-600 dark:bg-dark2 dark:text-white/70">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <div class="flex gap-3">
            <a href="{{ $cancelUrl }}" class="button flex-1 bg-white text-gray-700 shadow-sm hover:bg-slate-50 dark:bg-dark2 dark:text-white">{{ __('ui.cancel') }}</a>
            <button type="submit" class="button flex-1 bg-primary text-white">{{ $submitLabel }}</button>
        </div>

        @if ($isEdit && $team?->isOwner($viewer))
            <button type="submit" form="team-delete-form-{{ $team->id }}" class="w-full rounded-xl bg-rose-50 px-4 py-3 text-sm font-bold text-rose-600 hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-300">{{ __('ui.archive_team') }}</button>
        @endif
    </aside>
</form>

@if ($isEdit && $team?->isOwner($viewer))
    <form id="team-delete-form-{{ $team->id }}" method="POST" action="{{ route('teams.destroy', $team) }}" onsubmit="return confirm('{{ __('ui.archive_team_confirm') }}')">
        @csrf
        @method('DELETE')
    </form>
@endif

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-hh-socialite-team-form]').forEach((form) => {
            const fallback = {
                name: @json(__('ui.create_new_team')),
                tagline: @json(__('ui.team_default_tagline')),
                description: @json(__('ui.team_description_placeholder')),
            };

            const setText = (selector, value, defaultValue = '') => {
                form.querySelectorAll(selector).forEach((node) => {
                    node.textContent = value && String(value).trim() ? String(value).trim() : defaultValue;
                });
            };

            const syncTags = () => {
                const wrap = form.querySelector('[data-hh-team-card-tags]');
                if (!wrap) return;
                const tags = ['platform', 'playstyle', 'region', 'language']
                    .map((name) => form.querySelector(`[name="${name}"]`))
                    .filter(Boolean)
                    .map((field) => field.value)
                    .filter(Boolean);
                wrap.innerHTML = '';
                wrap.classList.toggle('hidden', tags.length === 0);
                tags.forEach((tag) => {
                    const span = document.createElement('span');
                    span.className = 'rounded-lg bg-white px-2.5 py-1 text-xs font-bold text-gray-600 dark:bg-dark2 dark:text-white/70';
                    span.textContent = tag;
                    wrap.appendChild(span);
                });
            };

            const sync = () => {
                const name = form.querySelector('[name="name"]')?.value || '';
                const tagline = form.querySelector('[name="tagline"]')?.value || '';
                const description = form.querySelector('[name="description"]')?.value || '';
                setText('[data-hh-team-preview-name], [data-hh-team-card-name]', name, fallback.name);
                setText('[data-hh-team-preview-tagline], [data-hh-team-card-tagline]', tagline, fallback.tagline);
                setText('[data-hh-team-card-description]', description.slice(0, 150), fallback.description);
                syncTags();
            };

            form.querySelectorAll('[data-hh-team-live]').forEach((field) => {
                field.addEventListener('input', sync);
                field.addEventListener('change', sync);
            });

            form.querySelectorAll('[data-hh-team-file]').forEach((input) => {
                input.addEventListener('change', () => {
                    const file = input.files && input.files[0] ? input.files[0] : null;
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        const url = event.target.result;
                        if (input.dataset.hhTeamFile === 'avatar') {
                            form.querySelectorAll('[data-hh-team-preview-avatar], [data-hh-team-card-avatar]').forEach((img) => img.src = url);
                        }
                        if (input.dataset.hhTeamFile === 'cover') {
                            form.querySelectorAll('[data-hh-team-preview-cover], [data-hh-team-card-cover]').forEach((img) => img.src = url);
                        }
                    };
                    reader.readAsDataURL(file);
                });
            });

            sync();
        });
    });
</script>
