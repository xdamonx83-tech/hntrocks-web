@php
    $socialiteSidebarActive = static function (array|string $patterns): string {
        return request()->routeIs(...((array) $patterns)) ? 'active' : '';
    };

    $socialiteSidebarActiveMore = static function (array|string $patterns) use ($socialiteSidebarActive): string {
        $active = $socialiteSidebarActive($patterns);

        return trim('!hidden ' . $active);
    };

    $socialiteSidebarPh = static function (string $icon, string $class = 'leading-none flex-none', int $size = 20) {
        $map = [
            'feed' => 'house',
            'messages' => 'chat-circle',
            'moments' => 'video',
            'moment_week' => 'star',
            'cups' => 'trophy',
            'members' => 'users',
            'teams' => 'users-three',
            'lfg' => 'crosshair',
            'team_lfg' => 'flag-checkered',
            'profile' => 'user',
            'badges' => 'medal',
            'crowns' => 'crown',
            'contracts' => 'clipboard-text',
            'loadouts' => 'target',
            'hall' => 'crown',
            'ideas' => 'lightbulb',
            'feedback' => 'chat-teardrop-text',
            'android' => 'android-logo',
            'calendar' => 'calendar',
            'caret' => 'caret-down',
            'settings' => 'gear',
            'privacy' => 'lock',
            'edit' => 'pencil-simple',
            'legal' => 'scroll',
        ];

        $name = $map[$icon] ?? 'circle';
        $safeClass = trim($class);
        $safeSize = max(12, min(32, $size));
        $style = 'font-size: '.$safeSize.'px; line-height: 1; width: '.$safeSize.'px; height: '.$safeSize.'px; display: inline-flex; align-items: center; justify-content: center;';

        return new \Illuminate\Support\HtmlString('<i class="ph ph-'.$name.' '.$safeClass.'" style="'.$style.'" aria-hidden="true"></i>');
    };
@endphp

        <!-- sidebar -->
        <div id="site__sidebar" class="fixed top-0 left-0 z-[99] pt-[--m-top] overflow-hidden transition-transform xl:duration-500 max-xl:w-full max-xl:-translate-x-full">

            <!-- sidebar inner -->
            <div class="p-2 max-xl:bg-white shadow-sm 2xl:w-72 sm:w-64 w-[80%] h-[calc(100vh-64px)] relative z-30 max-lg:border-r dark:max-xl:!bg-slate-700 dark:border-slate-700">
        
                <div class="pr-4" data-simplebar>

                    <nav id="side">
                    
                        <ul>
                            <li class="{{ $socialiteSidebarActive('feed.*') }}">
                                <a href="{{ route('feed.index') }}">
                                    {!! $socialiteSidebarPh('feed') !!}
                                    <span>{{ __('ui.feed') }}</span> 
                                </a>
                            </li>
                            <li class="{{ $socialiteSidebarActive('messages.*') }}">
                                <a href="{{ route('messages.index') }}">
                                    {!! $socialiteSidebarPh('messages') !!}
                                    <span>{{ __('ui.messages') }}</span> 
                                </a>
                            </li> 
                            <li class="{{ $socialiteSidebarActive('moments.*') }}">
                                <a href="{{ route('moments.index') }}">
                                    {!! $socialiteSidebarPh('moments') !!}
                                    <span>{{ __('ui.moments') }}</span> 
                                </a>
                            </li>
                            <li class="{{ $socialiteSidebarActive('moment-of-week.*') }}">
                                <a href="{{ route('moment-of-week.index') }}">
                                    {!! $socialiteSidebarPh('moment_week') !!}
                                    <span>{{ __('ui.moment_week_nav') }}</span> 
                                </a>
                            </li>
                            <li class="{{ $socialiteSidebarActive('cups.*') }}">
                                <a href="{{ route('cups.index') }}">
                                    {!! $socialiteSidebarPh('cups') !!}
                                    <span>{{ __('ui.cups') }}</span> 
                                </a>
                            </li>
                            <li class="{{ $socialiteSidebarActive('members.*') }}">
                                <a href="{{ route('members.index') }}">
                                    {!! $socialiteSidebarPh('members') !!}
                                    <span>{{ __('ui.members') }}</span> 
                                </a>
                            </li>
                            <li class="{{ $socialiteSidebarActive('teams.*') }}">
                                <a href="{{ route('teams.index') }}">
                                    {!! $socialiteSidebarPh('teams') !!}
                                    <span>{{ __('ui.teams') }}</span> 
                                </a>
                            </li>
                            <li class="{{ $socialiteSidebarActive('lfg.*') }}">
                                <a href="{{ route('lfg.index') }}">
                                    {!! $socialiteSidebarPh('lfg') !!}
                                    <span>{{ __('ui.lfg') }}</span> 
                                </a>
                            </li> 
                            <li class="{{ $socialiteSidebarActive('team-lfg.*') }}">
                                <a href="{{ route('team-lfg.index') }}">
                                    {!! $socialiteSidebarPh('team_lfg') !!}
                                    <span>{{ __('ui.team_lfg') }}</span> 
                                </a>
                            </li> 
                            <li class="{{ $socialiteSidebarActiveMore(['profile.show', 'profile.about', 'profile.friends', 'profile.badges', 'profile.trophies', 'profile.teams', 'profile.contact', 'profile.public', 'profile.about.public', 'profile.friends.public', 'profile.badges.public', 'profile.trophies.public', 'profile.teams.public', 'profile.contact.public']) }}" id="show__more">
                                <a href="{{ route('profile.show') }}">
                                    {!! $socialiteSidebarPh('profile') !!}
                                    <span>{{ __('ui.profile') }}</span> 
                                </a>
                            </li>
                            <li class="{{ $socialiteSidebarActiveMore('gamification.*') }}" id="show__more">
                                <a href="{{ route('gamification.index') }}">
                                    {!! $socialiteSidebarPh('badges') !!}
                                    <span> {{ __('ui.badges_quests') }} </span> 
                                </a>
                            </li>
                            <li class="{{ $socialiteSidebarActiveMore('crowns.*') }}" id="show__more">
                                <a href="{{ route('crowns.index') }}">
                                    {!! $socialiteSidebarPh('crowns') !!}
                                    <span>{{ __('ui.crowns_nav') }}</span> 
                                </a>
                            </li>
                            <li class="{{ $socialiteSidebarActiveMore('contracts.*') }}" id="show__more">
                                <a href="{{ route('contracts.index') }}">
                                    {!! $socialiteSidebarPh('contracts') !!}
                                    <span>{{ __('ui.contracts_nav') }}</span> 
                                </a>
                            </li>
                            <li class="{{ $socialiteSidebarActiveMore('loadout-challenges.*') }}" id="show__more">
                                <a href="{{ route('loadout-challenges.index') }}">
                                    {!! $socialiteSidebarPh('loadouts') !!}
                                    <span>{{ __('ui.loadout_nav') }}</span> 
                                </a>
                            </li>
                            <li class="{{ $socialiteSidebarActiveMore('hall-of-fame.*') }}" id="show__more">
                                <a href="{{ route('hall-of-fame.index') }}">
                                    {!! $socialiteSidebarPh('hall') !!}
                                    <span>{{ __('ui.hall_of_fame') }}</span> 
                                </a>
                            </li>
                            <li class="{{ $socialiteSidebarActiveMore('cup-ideas.*') }}" id="show__more">
                                <a href="{{ route('cup-ideas.index') }}">
                                    {!! $socialiteSidebarPh('ideas') !!}
                                    <span>{{ __('ui.cup_ideas_nav') }}</span> 
                                </a>
                            </li>
                            <li class="{{ $socialiteSidebarActiveMore('cup-feedback.*') }}" id="show__more">
                                <a href="{{ route('cup-feedback.create') }}">
                                    {!! $socialiteSidebarPh('feedback') !!}
                                    <span>{{ __('ui.cup_feedback') }}</span> 
                                </a>
                            </li>
                            <li class="{{ $socialiteSidebarActiveMore('app-beta.*') }}" id="show__more">
                                <a href="{{ route('app-beta.index') }}">
                                    {!! $socialiteSidebarPh('android') !!}
                                    <span>{{ __('ui.android_app') }}</span> 
                                </a>
                            </li>

                    </ul>
                        
                        <button type="button" class="flex items-center gap-4 py-2 px-4 w-full font-medium text-sm text-black dark:text-white" uk-toggle="target: #show__more; cls: !hidden uk-animation-fade"> 
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gray-200 text-slate-600 dark:bg-slate-700 dark:text-white">{!! $socialiteSidebarPh('caret', 'leading-none flex-none', 16) !!}</span> 
                            <span id="show__more">{{ __('ui.see_more') }}</span> 
                            <span class="!hidden" id="show__more">{{ __('ui.see_less') }}</span> 
                        </button>
        
                    </nav>
        
                    <div class="font-medium text-sm text-black border-t pt-3 mt-2 dark:text-white dark:border-slate-800">
                        <div class="px-3 pb-2 text-sm font-medium"> 
                            <div class="text-black dark:text-white">{{ __('ui.shortcut') }}</div> 
                        </div>
                        <a href="{{ route('cups.index') }}">
                            <div class="flex items-center gap-2 p-3 px-4 rounded-xl hover:bg-secondery">
                                {!! $socialiteSidebarPh('calendar') !!}
                                <div>{{ __('ui.current_cups') }}</div>
                            </div>
                        </a>
                        <a href="{{ route('teams.index') }}">
                            <div class="flex items-center gap-2 p-3 px-4 rounded-xl hover:bg-secondery">
                                {!! $socialiteSidebarPh('teams') !!}
                                <div>{{ __('ui.recruiting_teams') }}</div>
                            </div>
                        </a>
                        <a href="{{ route('contracts.index') }}">
                            <div class="flex items-center gap-2 p-3 px-4 rounded-xl hover:bg-secondery">
                                {!! $socialiteSidebarPh('contracts') !!}
                                <div>{{ __('ui.contracts_nav') }}</div>
                            </div>
                        </a>
                        <a href="{{ route('loadout-challenges.index') }}">
                            <div class="flex items-center gap-2 p-3 px-4 rounded-xl hover:bg-secondery">
                                {!! $socialiteSidebarPh('loadouts') !!}
                                <div>{{ __('ui.loadout_nav') }}</div>
                            </div>
                        </a>
                        <a href="{{ route('app-beta.index') }}">
                            <div class="flex items-center gap-2 p-3 px-4 rounded-xl hover:bg-secondery">
                                {!! $socialiteSidebarPh('android') !!}
                                <div>{{ __('ui.android_beta') }}</div>
                            </div>
                        </a> 
                    </div>
        
                    <nav id="side" class="font-medium text-sm text-black border-t pt-3 mt-2 dark:text-white dark:border-slate-800">
                        <div class="px-3 pb-2 text-sm font-medium"> 
                            <div class="text-black dark:text-white">{{ __('ui.account') }}</div> 
                        </div>
        
                        <ul class="mt-2 -space-y-2" 
                            uk-nav="multiple: true">
                
                            <li class="{{ $socialiteSidebarActive(['account.settings.*', 'settings.security.*']) }}">
                                <a href="{{ route('account.settings.edit') }}"> 
                                    {!! $socialiteSidebarPh('settings') !!}
                                    <span>{{ __('ui.settings') }}</span>                  
                                </a>
                            </li>
                            <li class="{{ $socialiteSidebarActive('settings.privacy.*') }}">
                                <a href="{{ route('settings.privacy.edit') }}"> 
                                    {!! $socialiteSidebarPh('privacy') !!}
                                    <span>{{ __('ui.privacy') }}</span>                  
                                </a>
                            </li>
                            <li class="{{ $socialiteSidebarActive('profile.edit') }}">   
                                <a href="{{ route('profile.edit') }}"> 
                                    {!! $socialiteSidebarPh('edit') !!}
                                    <span>{{ __('ui.edit_profile') }}</span>                  
                                </a>
                            </li>
                            <li class="uk-parent {{ $socialiteSidebarActive('legal.*') }}">
                                <a href="#" class="group"> 
                                    {!! $socialiteSidebarPh('legal') !!}
                                    <span>{{ __('ui.legal') }}</span>   
                                    {!! $socialiteSidebarPh('caret', 'leading-none ml-auto duration-200 group-aria-expanded:rotate-180', 16) !!}              
                                </a>
                                <ul class="pl-10 my-1 space-y-0 text-sm">
                                    <li><a href="{{ route('legal.impressum') }}" class="!py-2 !rounded -md">Impressum</a></li>
                                    <li><a href="{{ route('legal.datenschutz') }}" class="!py-2 !rounded -md">Datenschutz</a></li>
                                    <li><a href="{{ route('legal.nutzungsbedingungen') }}" class="!py-2 !rounded -md">{{ __('ui.terms') }}</a></li>
                                    <li><a href="{{ route('legal.account_deletion') }}" class="!py-2 !rounded -md">{{ __('ui.account_deletion') }}</a></li>
                                </ul>
                            </li>
                        
                        </ul>
        
                    </nav>
                

                    <div class="text-xs font-medium flex flex-wrap gap-2 gap-y-0.5 p-2 mt-2">
                        <a href="{{ route('legal.impressum') }}" class="hover:underline">Impressum</a>
                        <a href="{{ route('legal.datenschutz') }}" class="hover:underline">Datenschutz</a>
                        <a href="{{ route('legal.nutzungsbedingungen') }}" class="hover:underline">{{ __('ui.terms') }}</a>
                        <a href="{{ route('legal.netiquette') }}" class="hover:underline">Netiquette</a>
                    </div>

                </div>

            </div>

            <!-- sidebar overly -->
            <div id="site__sidebar__overly" 
                class="absolute top-0 left-0 z-20 w-screen h-screen xl:hidden backdrop-blur-sm"
                uk-toggle="target: #site__sidebar ; cls :!-translate-x-0"> 
            </div>
        </div>
