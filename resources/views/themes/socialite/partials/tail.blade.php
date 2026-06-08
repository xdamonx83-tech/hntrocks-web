

    </div>


    <!-- Socialite report modal -->
    <div id="socialite-report-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4" data-socialite-report-modal aria-hidden="true">
        <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" data-socialite-report-close></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl dark:bg-dark2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-black dark:text-white">{{ __('ui.report_content') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-white/70" data-socialite-report-label>{{ __('ui.report_modal_intro') }}</p>
                </div>
                <button type="button" class="button-icon" data-socialite-report-close aria-label="{{ __('ui.close_report_modal') }}">
                    <ion-icon class="text-xl" name="close-outline"></ion-icon>
                </button>
            </div>

            <form method="post" action="{{ route('reports.store') }}" class="mt-5 space-y-4" data-socialite-report-form>
                @csrf
                <input type="hidden" name="type" data-socialite-report-type>
                <input type="hidden" name="id" data-socialite-report-id>

                <label class="block text-sm font-semibold text-black dark:text-white">
                    {{ __('ui.report_reason') }}
                    <select name="reason" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none dark:border-slate-700 dark:bg-dark3 dark:text-white" required>
                        <option value="spam">{{ __('ui.report_reason_spam') }}</option>
                        <option value="abuse">{{ __('ui.report_reason_abuse') }}</option>
                        <option value="hate">{{ __('ui.report_reason_hate') }}</option>
                        <option value="nsfw">{{ __('ui.report_reason_nsfw') }}</option>
                        <option value="fraud">{{ __('ui.report_reason_fraud') }}</option>
                        <option value="cheating">{{ __('ui.report_reason_cheating') }}</option>
                        <option value="privacy">{{ __('ui.report_reason_privacy') }}</option>
                        <option value="other">{{ __('ui.other') }}</option>
                    </select>
                </label>

                <label class="block text-sm font-semibold text-black dark:text-white">
                    {{ __('ui.report_details_optional') }}
                    <textarea name="body" rows="4" maxlength="2000" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none dark:border-slate-700 dark:bg-dark3 dark:text-white" placeholder="{{ __('ui.report_note_placeholder') }}"></textarea>
                </label>

                <p class="hidden rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300" data-socialite-report-status></p>

                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-full bg-slate-100 px-5 py-2.5 text-sm font-semibold dark:bg-dark3 dark:text-white" data-socialite-report-close>{{ __('ui.cancel') }}</button>
                    <button type="submit" class="rounded-full bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white" data-socialite-report-submit>{{ __('ui.send_report') }}</button>
                </div>
            </form>
        </div>
    </div>


    @include('themes.socialite.partials.chat-tabs')


    <!-- post preview modal --> 
    <div class="hidden lg:p-20 max-lg:!items-start" id="preview_modal" uk-modal="">
        
        <div class="uk-modal-dialog tt relative mx-auto overflow-hidden shadow-xl rounded-lg lg:flex items-center ax-w-[86rem] w-full lg:h-[80vh]">
          
            <!-- image previewer -->
            <div class="lg:h-full lg:w-[calc(100vw-400px)] w-full h-96 flex justify-center items-center relative">
                
                <div class="relative z-10 w-full h-full">
                    <img src="/assets/socialite/images/post/post-1.jpg" alt="" class="w-full h-full object-cover absolute">
                </div>
  
                <!-- close button -->
                <button type="button"  class="bg-white rounded-full p-2 absolute right-0 top-0 m-3 uk-animation-slide-right-medium z-10 dark:bg-slate-600 uk-modal-close">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

            </div>

            <!-- right sidebar -->
            <div class="lg:w-[400px] w-full bg-white h-full relative  overflow-y-auto shadow-xl dark:bg-dark2 flex flex-col justify-between">
                
                <div class="p-5 pb-0">

                    <!-- story heading -->
                    <div class="flex gap-3 text-sm font-medium">
                        <img src="/assets/socialite/images/avatars/avatar-5.jpg" alt="" class="w-9 h-9 rounded-full">
                        <div class="flex-1">
                            <h4 class="text-black font-medium dark:text-white"> Steeve </h4>
                            <div class="text-gray-500 text-xs dark:text-white/80"> 2 hours ago</div>
                        </div>
 
                        <!-- dropdown -->
                        <div class="-m-1">
                            <button type="button" class="button__ico w-8 h-8"> <ion-icon class="text-xl" name="ellipsis-horizontal"></ion-icon> </button>
                            <div  class="w-[253px]" uk-dropdown="pos: bottom-right; animation: uk-animation-scale-up uk-transform-origin-top-right; animate-out: true"> 
                                <nav> 
                                    <a href="#"> <ion-icon class="text-xl shrink-0" name="bookmark-outline"></ion-icon>  {{ __('ui.add_to_favorites') }} </a>  
                                    <a href="#"> <ion-icon class="text-xl shrink-0" name="notifications-off-outline"></ion-icon> {{ __('ui.mute_notification') }} </a>  
                                    <a href="#"> <ion-icon class="text-xl shrink-0" name="flag-outline"></ion-icon>  {{ __('ui.report_this_post') }} </a>  
                                    <a href="#"> <ion-icon class="text-xl shrink-0" name="share-outline"></ion-icon>  {{ __('ui.share_profile') }} </a>  
                                    <hr>
                                    <a href="#" class="text-red-400 hover:!bg-red-50 dark:hover:!bg-red-500/50"> <ion-icon class="text-xl shrink-0" name="stop-circle-outline"></ion-icon>  {{ __('ui.unfollow') }} </a>  
                                </nav>
                            </div>
                        </div>
                    </div>

                    <p class="font-normal text-sm leading-6 mt-4"> {{ __('ui.demo_photo_text') }} </p>

                    <div class="shadow relative -mx-5 px-5 py-3 mt-3">
                        <div class="flex items-center gap-4 text-xs font-semibold">
                            <div class="flex items-center gap-2.5">
                                <button type="button" class="button__ico text-red-500 bg-red-100 dark:bg-slate-700"> <ion-icon class="text-lg" name="heart"></ion-icon> </button>
                                <a href="#">1,300</a>
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="button" class="button__ico bg-slate-100 dark:bg-slate-700"> <ion-icon class="text-lg" name="chatbubble-ellipses"></ion-icon> </button>
                                <span>260</span>
                            </div>
                            <button type="button" class="button__ico ml-auto"> <ion-icon class="text-xl" name="share-outline"></ion-icon> </button>
                            <button type="button" class="button__ico"> <ion-icon class="text-xl" name="bookmark-outline"></ion-icon> </button>
                        </div>
                    </div>

                </div>

                <div class="p-5 h-full overflow-y-auto flex-1">

                    <!-- comment list -->
                    <div class="relative text-sm font-medium space-y-5"> 
                
                        <div class="flex items-start gap-3 relative">
                            <img src="/assets/socialite/images/avatars/avatar-2.jpg" alt="" class="w-6 h-6 mt-1 rounded-full">
                            <div class="flex-1">
                                <a href="#" class="text-black font-medium inline-block dark:text-white"> Steeve </a>
                                <p class="mt-0.5">What a beautiful, I love it. 😍 </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 relative">
                            <img src="/assets/socialite/images/avatars/avatar-3.jpg" alt="" class="w-6 h-6 mt-1 rounded-full">
                            <div class="flex-1">
                                <a href="#" class="text-black font-medium inline-block dark:text-white"> Monroe </a>
                                <p class="mt-0.5">   You captured the moment.😎 </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 relative">
                            <img src="/assets/socialite/images/avatars/avatar-7.jpg" alt="" class="w-6 h-6 mt-1 rounded-full">
                            <div class="flex-1">
                                <a href="#" class="text-black font-medium inline-block dark:text-white"> Alexa </a>
                                <p class="mt-0.5"> This photo is amazing!   </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 relative">
                            <img src="/assets/socialite/images/avatars/avatar-4.jpg" alt="" class="w-6 h-6 mt-1 rounded-full">
                            <div class="flex-1">
                                <a href="#" class="text-black font-medium inline-block dark:text-white"> John  </a>
                                <p class="mt-0.5"> Wow, You are so talented 😍 </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 relative">
                            <img src="/assets/socialite/images/avatars/avatar-5.jpg" alt="" class="w-6 h-6 mt-1 rounded-full">
                            <div class="flex-1">
                                <a href="#" class="text-black font-medium inline-block dark:text-white"> Michael </a>
                                <p class="mt-0.5"> I love taking photos   🌳🐶</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 relative">
                            <img src="/assets/socialite/images/avatars/avatar-3.jpg" alt="" class="w-6 h-6 mt-1 rounded-full">
                            <div class="flex-1">
                                <a href="#" class="text-black font-medium inline-block dark:text-white"> Monroe </a>
                                <p class="mt-0.5">  Awesome. 😊😢 </p>
                            </div>
                        </div> 
                        <div class="flex items-start gap-3 relative">
                            <img src="/assets/socialite/images/avatars/avatar-5.jpg" alt="" class="w-6 h-6 mt-1 rounded-full">
                            <div class="flex-1">
                                <a href="#" class="text-black font-medium inline-block dark:text-white"> Jesse </a>
                                <p class="mt-0.5"> Well done 🎨📸   </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 relative">
                            <img src="/assets/socialite/images/avatars/avatar-2.jpg" alt="" class="w-6 h-6 mt-1 rounded-full">
                            <div class="flex-1">
                                <a href="#" class="text-black font-medium inline-block dark:text-white"> Steeve </a>
                                <p class="mt-0.5">What a beautiful, I love it. 😍 </p>
                            </div>
                        </div> 
                        <div class="flex items-start gap-3 relative">
                            <img src="/assets/socialite/images/avatars/avatar-7.jpg" alt="" class="w-6 h-6 mt-1 rounded-full">
                            <div class="flex-1">
                                <a href="#" class="text-black font-medium inline-block dark:text-white"> Alexa </a>
                                <p class="mt-0.5"> This photo is amazing!   </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 relative">
                            <img src="/assets/socialite/images/avatars/avatar-4.jpg" alt="" class="w-6 h-6 mt-1 rounded-full">
                            <div class="flex-1">
                                <a href="#" class="text-black font-medium inline-block dark:text-white"> John  </a>
                                <p class="mt-0.5"> Wow, You are so talented 😍 </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 relative">
                            <img src="/assets/socialite/images/avatars/avatar-5.jpg" alt="" class="w-6 h-6 mt-1 rounded-full">
                            <div class="flex-1">
                                <a href="#" class="text-black font-medium inline-block dark:text-white"> Michael </a>
                                <p class="mt-0.5"> I love taking photos   🌳🐶</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 relative">
                            <img src="/assets/socialite/images/avatars/avatar-3.jpg" alt="" class="w-6 h-6 mt-1 rounded-full">
                            <div class="flex-1">
                                <a href="#" class="text-black font-medium inline-block dark:text-white"> Monroe </a>
                                <p class="mt-0.5">  Awesome. 😊😢 </p>
                            </div>
                        </div>  
                         
                    </div>

                </div>

                <div class="bg-white p-3 text-sm font-medium flex items-center gap-2">
                                
                    <img src="/assets/socialite/images/avatars/avatar-2.jpg" alt="" class="w-6 h-6 rounded-full">
                    
                    <div class="flex-1 relative overflow-hidden ">
                        <textarea placeholder="{{ __('ui.add_comment_placeholder') }}" rows="1" class="w-full resize-  px-4 py-2 focus:!border-transparent focus:!ring-transparent resize-y"></textarea>

                        <div class="flex items-center gap-2 absolute bottom-0.5 right-0 m-3">
                            <ion-icon class="text-xl flex text-blue-700" name="image"></ion-icon> 
                            <ion-icon class="text-xl flex text-yellow-500" name="happy"></ion-icon> 
                        </div>

                    </div>

                    <button type="submit" class="hidden text-sm rounded-full py-1.5 px-4 font-semibold bg-secondery"> {{ __('ui.reply') }}</button>
                
                </div>

            </div>
   
        </div>
        
    </div>

    <!-- create status -->
    <div class="hidden lg:p-20" id="create-status" uk-modal="">
        <div class="uk-modal-dialog tt relative overflow-hidden mx-auto bg-white shadow-xl rounded-lg md:w-[520px] w-full dark:bg-dark2">
            <form method="post" action="{{ route('feed.store') }}" enctype="multipart/form-data" data-socialite-composer-form>
                @csrf
                <input type="hidden" name="background_style" value="none">
                <input type="hidden" name="feeling_key" value="{{ old('feeling_key', 'none') }}" data-socialite-feeling-key>
                <input type="hidden" name="gif_provider" value="{{ old('gif_provider') }}" data-socialite-gif-provider>
                <input type="hidden" name="gif_id" value="{{ old('gif_id') }}" data-socialite-gif-id>
                <input type="hidden" name="gif_url" value="{{ old('gif_url') }}" data-socialite-gif-url>
                <input type="hidden" name="gif_preview_url" value="{{ old('gif_preview_url') }}" data-socialite-gif-preview-url>
                <input type="hidden" name="gif_title" value="{{ old('gif_title') }}" data-socialite-gif-title>
                <input type="hidden" name="gif_source_url" value="{{ old('gif_source_url') }}" data-socialite-gif-source-url>

                <div class="text-center py-4 border-b mb-0 dark:border-slate-700">
                    <h2 class="text-sm font-medium text-black dark:text-white">{{ __('ui.header_create_post') }}</h2>

                    <!-- close button -->
                    <button type="button" class="button-icon absolute top-0 right-0 m-2.5 uk-modal-close">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-5 mt-3 p-2">
                    <textarea class="w-full !text-black placeholder:!text-black !bg-white !border-transparent focus:!border-transparent focus:!ring-transparent !font-normal !text-xl dark:!text-white dark:placeholder:!text-white dark:!bg-slate-800" name="body" rows="6" maxlength="5000" placeholder="{{ __('ui.feed_share_with_hunters') }}">{{ old('body') }}</textarea>
                    @error('body')
                        <p class="px-3 text-sm font-semibold text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                @php
                    $socialiteFeedMediaMaxFiles = (int) config('hunthub.upload_limits.feed_media_count', 12);
                    $socialiteFeedMediaMaxKb = (int) config('hunthub.upload_limits.feed_media_kb', 102400);
                    $socialiteFeedMediaMaxMb = max(1, (int) ceil($socialiteFeedMediaMaxKb / 1024));
                @endphp

                <input
                    id="socialite_feed_media"
                    type="file"
                    name="media[]"
                    accept="image/*,video/mp4,video/webm,video/quicktime"
                    multiple
                    class="hidden"
                    data-socialite-media-max-files="{{ $socialiteFeedMediaMaxFiles }}"
                    data-socialite-media-max-mb="{{ $socialiteFeedMediaMaxMb }}"
                    data-socialite-media-max-bytes="{{ $socialiteFeedMediaMaxKb * 1024 }}"
                >

                <div class="hidden px-4 pb-2" data-socialite-media-preview-wrap>
                    <div class="flex items-center justify-between gap-3 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <span>{{ __('ui.selected_media_limit', ['files' => $socialiteFeedMediaMaxFiles, 'mb' => $socialiteFeedMediaMaxMb]) }}</span>
                        <button type="button" class="text-slate-500 hover:text-red-500" data-socialite-media-clear>{{ __('ui.clear_all') }}</button>
                    </div>
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4" data-socialite-media-preview></div>
                </div>

                <p class="hidden px-5 pt-1 text-xs font-semibold text-red-600" data-socialite-media-status></p>

                <div class="flex items-center gap-2 text-sm py-2 px-4 font-medium flex-wrap">
                    <label for="socialite_feed_media" class="flex items-center gap-1.5 bg-sky-50 text-sky-600 rounded-full py-1 px-2 border-2 border-sky-100 dark:bg-sky-950 dark:border-sky-900 cursor-pointer">
                        <ion-icon name="image" class="text-base"></ion-icon>
                        <span>{{ __('ui.feed_photo') }}</span>
                        <span class="hidden text-xs font-semibold" data-socialite-media-count></span>
                    </label>
                    <label for="socialite_feed_media" class="flex items-center gap-1.5 bg-teal-50 text-teal-600 rounded-full py-1 px-2 border-2 border-teal-100 dark:bg-teal-950 dark:border-teal-900 cursor-pointer">
                        <ion-icon name="videocam" class="text-base"></ion-icon>
                        {{ __('ui.feed_video') }}
                    </label>
                    <button type="button" class="flex items-center gap-1.5 bg-orange-50 text-orange-600 rounded-full py-1 px-2 border-2 border-orange-100 dark:bg-yellow-950 dark:border-yellow-900" data-socialite-panel-toggle="feeling">
                        <ion-icon name="happy" class="text-base"></ion-icon>
                        <span data-socialite-feeling-label>{{ __('ui.feed_feeling') }}</span>
                    </button>
                    <button type="button" class="flex items-center gap-1.5 bg-red-50 text-red-600 rounded-full py-1 px-2 border-2 border-rose-100 dark:bg-rose-950 dark:border-rose-900" data-socialite-panel-toggle="poll">
                        <ion-icon name="bar-chart" class="text-base"></ion-icon>
                        {{ __('ui.poll') }}
                    </button>
                    <button type="button" class="flex items-center gap-1.5 bg-purple-50 text-purple-600 rounded-full py-1 px-2 border-2 border-purple-100 dark:bg-purple-950 dark:border-purple-900" data-socialite-panel-toggle="gif">
                        <ion-icon name="sparkles" class="text-base"></ion-icon>
                        GIF
                    </button>
                </div>

                <div class="px-4 pb-3 space-y-3 text-sm" data-socialite-composer-panels>
                    <div class="hidden rounded-2xl border border-slate-100 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800" data-socialite-panel="feeling">
                        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.feed_feeling') }}</div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="rounded-full bg-white px-3 py-1.5 shadow-sm ring-1 ring-slate-100 dark:bg-dark3 dark:ring-slate-700" data-socialite-feeling="none" data-socialite-feeling-text="{{ __('ui.feed_feeling') }}">{{ __('ui.feed_feeling_none') }}</button>
                            <button type="button" class="rounded-full bg-white px-3 py-1.5 shadow-sm ring-1 ring-slate-100 dark:bg-dark3 dark:ring-slate-700" data-socialite-feeling="happy" data-socialite-feeling-text="😊 {{ __('ui.feed_feeling_happy') }}">😊 {{ __('ui.feed_feeling_happy') }}</button>
                            <button type="button" class="rounded-full bg-white px-3 py-1.5 shadow-sm ring-1 ring-slate-100 dark:bg-dark3 dark:ring-slate-700" data-socialite-feeling="excited" data-socialite-feeling-text="🔥 {{ __('ui.feed_feeling_excited') }}">🔥 {{ __('ui.feed_feeling_excited') }}</button>
                            <button type="button" class="rounded-full bg-white px-3 py-1.5 shadow-sm ring-1 ring-slate-100 dark:bg-dark3 dark:ring-slate-700" data-socialite-feeling="focused" data-socialite-feeling-text="🎯 {{ __('ui.feed_feeling_focused') }}">🎯 {{ __('ui.feed_feeling_focused') }}</button>
                            <button type="button" class="rounded-full bg-white px-3 py-1.5 shadow-sm ring-1 ring-slate-100 dark:bg-dark3 dark:ring-slate-700" data-socialite-feeling="chill" data-socialite-feeling-text="🌙 {{ __('ui.feed_feeling_chill') }}">🌙 {{ __('ui.feed_feeling_chill') }}</button>
                            <button type="button" class="rounded-full bg-white px-3 py-1.5 shadow-sm ring-1 ring-slate-100 dark:bg-dark3 dark:ring-slate-700" data-socialite-feeling="tired" data-socialite-feeling-text="😴 {{ __('ui.feed_feeling_tired') }}">😴 {{ __('ui.feed_feeling_tired') }}</button>
                            <button type="button" class="rounded-full bg-white px-3 py-1.5 shadow-sm ring-1 ring-slate-100 dark:bg-dark3 dark:ring-slate-700" data-socialite-feeling="salty" data-socialite-feeling-text="🧂 {{ __('ui.feed_feeling_salty') }}">🧂 {{ __('ui.feed_feeling_salty') }}</button>
                        </div>
                    </div>

                    <div class="hidden rounded-2xl border border-slate-100 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800" data-socialite-panel="poll">
                        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.poll') }}</div>
                        <input type="text" name="poll_question" maxlength="180" value="{{ old('poll_question') }}" placeholder="{{ __('ui.poll_ask_question') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-dark3">
                        <div class="mt-2 grid gap-2">
                            <input type="text" name="poll_options[]" maxlength="180" value="{{ old('poll_options.0') }}" placeholder="{{ __('ui.poll_option_1') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-dark3">
                            <input type="text" name="poll_options[]" maxlength="180" value="{{ old('poll_options.1') }}" placeholder="{{ __('ui.poll_option_2') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-dark3">
                            <input type="text" name="poll_options[]" maxlength="180" value="{{ old('poll_options.2') }}" placeholder="{{ __('ui.poll_option_3_optional') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-dark3">
                            <input type="text" name="poll_options[]" maxlength="180" value="{{ old('poll_options.3') }}" placeholder="{{ __('ui.poll_option_4_optional') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-dark3">
                        </div>
                        <p class="mt-2 text-xs text-slate-500">{{ __('ui.poll_min_two_options') }}</p>
                    </div>

                    <div class="hidden rounded-2xl border border-slate-100 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800" data-socialite-panel="gif" data-socialite-gif-search-url="{{ url('/feed/gifs/search') }}" data-socialite-gif-trending-url="{{ url('/feed/gifs/trending') }}">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">GIF</div>
                            <button type="button" class="text-xs font-semibold text-blue-600" data-socialite-gif-clear hidden>{{ __('ui.remove_gif') }}</button>
                        </div>
                        <div class="flex gap-2">
                            <input type="search" maxlength="80" placeholder="{{ __('ui.search_gifs') }}" class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-dark3" data-socialite-gif-query>
                            <button type="button" class="rounded-xl bg-blue-500 px-4 py-2 text-sm font-semibold text-white" data-socialite-gif-search>{{ __('ui.search') }}</button>
                        </div>
                        <div class="mt-3 hidden overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-100 dark:bg-dark3 dark:ring-slate-700" data-socialite-gif-selected>
                            <img src="" alt="{{ __('ui.selected_gif') }}" class="max-h-48 w-full object-cover" data-socialite-gif-selected-image>
                            <div class="p-2 text-xs font-semibold text-slate-600 dark:text-slate-300" data-socialite-gif-selected-title></div>
                        </div>
                        <div class="mt-3 grid max-h-64 grid-cols-3 gap-2 overflow-y-auto" data-socialite-gif-results></div>
                        <p class="mt-2 text-xs text-slate-500" data-socialite-gif-status>{{ __('ui.powered_by_giphy') }}</p>
                    </div>
                </div>

                <p class="hidden px-5 pt-1 text-sm font-semibold" data-socialite-composer-status></p>

                <div class="p-5 flex justify-between items-center">
                    <div>
                        <button class="inline-flex items-center py-1 px-2.5 gap-1 font-medium text-sm rounded-full bg-slate-50 border-2 border-slate-100 group aria-expanded:bg-slate-100 aria-expanded: dark:text-white dark:bg-slate-700 dark:border-slate-600" type="button">
                            Community
                            <ion-icon name="chevron-down-outline" class="text-base duration-500 group-aria-expanded:rotate-180"></ion-icon>
                        </button>

                        <div class="p-2 bg-white rounded-lg shadow-lg text-black font-medium border border-slate-100 w-60 dark:bg-slate-700" uk-drop="offset:10;pos: bottom-left; reveal-left;animate-out: true; animation: uk-animation-scale-up uk-transform-origin-bottom-left ; mode:click">
                            <label class="block">
                                <input type="radio" name="visibility" value="public" class="peer appearance-none hidden" @checked(old('visibility', 'public') === 'public')>
                                <div class="relative flex items-center justify-between cursor-pointer rounded-md p-2 px-3 hover:bg-secondery peer-checked:[&_.active]:block dark:bg-dark3">
                                    <div class="text-sm">{{ __('ui.community') }}</div>
                                    <ion-icon name="checkmark-circle" class="hidden active absolute -translate-y-1/2 right-2 text-2xl text-blue-600 uk-animation-scale-up"></ion-icon>
                                </div>
                            </label>
                            <label class="block">
                                <input type="radio" name="visibility" value="followers" class="peer appearance-none hidden" @checked(old('visibility') === 'followers')>
                                <div class="relative flex items-center justify-between cursor-pointer rounded-md p-2 px-3 hover:bg-secondery peer-checked:[&_.active]:block dark:bg-dark3">
                                    <div class="text-sm">{{ __('ui.friends') }}</div>
                                    <ion-icon name="checkmark-circle" class="hidden active absolute -translate-y-1/2 right-2 text-2xl text-blue-600 uk-animation-scale-up"></ion-icon>
                                </div>
                            </label>
                            <label class="block">
                                <input type="radio" name="visibility" value="private" class="peer appearance-none hidden" @checked(old('visibility') === 'private')>
                                <div class="relative flex items-center justify-between cursor-pointer rounded-md p-2 px-3 hover:bg-secondery peer-checked:[&_.active]:block dark:bg-dark3">
                                    <div class="text-sm">{{ __('ui.only_me') }}</div>
                                    <ion-icon name="checkmark-circle" class="hidden active absolute -translate-y-1/2 right-2 text-2xl text-blue-600 uk-animation-scale-up"></ion-icon>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="button bg-blue-500 text-white py-2 px-12 text-[14px]" data-socialite-composer-submit data-publish-label="{{ __('ui.publish') }}" data-publishing-label="{{ __('ui.publishing') }}">{{ __('ui.publish') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- create story -->
    <div class="hidden lg:p-20" id="create-story" uk-modal="">
   
        <div class="uk-modal-dialog tt relative overflow-hidden mx-auto bg-white p-7 shadow-xl rounded-lg md:w-[520px] w-full dark:bg-dark2">

            <div class="text-center py-3 border-b -m-7 mb-0 dark:border-slate-700">
                <h2 class="text-sm font-medium">{{ __('ui.header_create_post') }}</h2>

                <!-- close button -->
                <button type="button" class="button__ico absolute top-0 right-0 m-2.5 uk-modal-close">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
     
            </div>
                    
            <div class="space-y-5 mt-7">

                <div> 
                    <label for="" class="text-base">{{ __('ui.feed_share_with_hunters') }}</label>
                    <input type="text"  class="w-full mt-3">
                </div>

                <div>  
                    <div class="w-full h-72 relative border1 rounded-lg overflow-hidden bg-[url('../images/ad_pattern.png')] bg-repeat">
                    
                        <label for="createStatusUrl" class="flex flex-col justify-center items-center absolute -translate-x-1/2 left-1/2 bottom-0 z-10 w-full pb-6 pt-10 cursor-pointer bg-gradient-to-t from-gray-700/60">
                            <input id="createStatusUrl" type="file" class="hidden" />
                            <ion-icon name="image" class="text-3xl text-teal-600"></ion-icon>
                            <span class="text-white mt-2">{{ __('ui.browse_upload_image_or_clip') }}</span>
                        </label>

                        <img id="createStatusPhoto" src="#" alt="{{ __('ui.uploaded_photo') }}" accept="image/png, image/jpeg" style="display:none;" class="w-full h-full absolute object-cover">

                    </div>

                </div>
                
                <div class="flex justify-between items-center">

                    <div class="flex items-start gap-2">
                        <ion-icon name="time-outline" class="text-3xl text-sky-600  rounded-full bg-blue-50 dark:bg-transparent"></ion-icon>
                        <p class="text-sm text-gray-500 font-medium"> {{ __('ui.preview_local_only_line1') }} <br> {{ __('ui.preview_local_only_line2') }} </p>
                    </div>

                    <button type="button" class="button bg-blue-500 text-white px-8">{{ __('ui.preview') }}</button>

                </div>

            </div>
        
        </div>

    </div>

 
    <script>
        (() => {
            const modal = document.getElementById('create-status');
            if (!modal) {
                return;
            }

            const panels = Array.from(modal.querySelectorAll('[data-socialite-panel]'));
            const openPanel = (name) => {
                panels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.dataset.socialitePanel !== name);
                });
            };

            modal.querySelectorAll('[data-socialite-panel-toggle]').forEach((button) => {
                button.addEventListener('click', () => openPanel(button.dataset.socialitePanelToggle));
            });

            const composerForm = modal.querySelector('[data-socialite-composer-form]');
            const composerSubmit = modal.querySelector('[data-socialite-composer-submit]');
            const composerStatus = modal.querySelector('[data-socialite-composer-status]');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const socialiteI18n = {
                publish: @json(__('ui.publish')),
                publishing: @json(__('ui.publishing')),
                postPublishFailed: @json(__('ui.post_publish_failed')),
                postPublishedRefreshing: @json(__('ui.post_published_refreshing')),
                shareLinkCopied: @json(__('ui.share_link_copied')),
                copyShareLink: @json(__('ui.copy_share_link')),
                bookmarkSaved: @json(__('ui.feed_bookmark_saved')),
                bookmarkRemoved: @json(__('ui.feed_bookmark_removed')),
                removeBookmark: @json(__('ui.remove_bookmark')),
                addToFavorites: @json(__('ui.add_to_favorites')),
                vote: @json(__('ui.vote')),
                votes: @json(__('ui.votes')),
                replyLoadSingular: @json(__('ui.js_i18n_replies_singular')),
                replyLoadPlural: @json(__('ui.js_i18n_replies_plural')),
                loadReply: @json(__('ui.js_load_reply')),
                loadReplies: @json(__('ui.js_load_replies')),
                like: @json(__('ui.like')),
                liked: @json(__('ui.liked')),
                reply: @json(__('ui.reply')),
                edit: @json(__('ui.edit')),
                cancel: @json(__('ui.cancel')),
                save: @json(__('ui.save')),
                delete: @json(__('ui.delete')),
                commentOptions: @json(__('ui.comment_options')),
                deletePostConfirm: @json(__('ui.delete_post_confirm')),
                deletePostAdminConfirm: @json(__('ui.delete_post_admin_confirm')),
                postDeleted: @json(__('ui.feed_post_deleted')),
                commentUpdateFailed: @json(__('ui.comment_update_failed')),
                commentSaveFailed: @json(__('ui.comment_save_failed')),
                deleteCommentConfirm: @json(__('ui.delete_comment_confirm')),
                commentReactionFailed: @json(__('ui.comment_reaction_failed')),
                commentRequestFailed: @json(__('ui.comment_request_failed')),
                replyTo: @json(__('ui.reply_to_user')),
                repliesHide: @json(__('ui.replies_hide')),
                addCommentPlaceholder: @json(__('ui.add_comment_placeholder')),
                postUpdateFailed: @json(__('ui.post_update_failed')),
                postDeleteFailed: @json(__('ui.post_delete_failed')),
                commentDeleted: @json(__('ui.comment_deleted')),
                commentDeleteFailed: @json(__('ui.comment_delete_failed')),
                pollVoteFailed: @json(__('ui.poll_vote_failed')),
                voteSaved: @json(__('ui.vote_saved')),
                viewMoreComments: @json(__('ui.view_more_comments')),
                loading: @json(__('ui.loading')),
                feedLoadMorePosts: @json(__('ui.feed_load_more_posts')),
                commentsRequestFailed: @json(__('ui.comments_request_failed')),
                tryAgain: @json(__('ui.try_again')),
                removeSelectedMedia: @json(__('ui.remove_selected_media')),
                video: @json(__('ui.feed_video')),
                photo: @json(__('ui.feed_photo')),
                poweredByGiphy: @json(__('ui.powered_by_giphy')),
                feeling: @json(__('ui.feed_feeling')),
                noGifsFound: @json(__('ui.no_gifs_found')),
                loadingGifs: @json(__('ui.loading_gifs')),
                reportContent: @json(__('ui.report_content')),
                reportFailed: @json(__('ui.report_failed')),
                reportSent: @json(__('ui.report_sent')),
                reportCouldNotBeSent: @json(__('ui.report_could_not_be_sent')),
                reactionsTitle: @json(__('ui.reactions_title')),
                reactionsAll: @json(__('ui.reactions_all')),
                reactionsEmpty: @json(__('ui.reactions_empty')),
                reactionsLoadFailed: @json(__('ui.reactions_load_failed')),
                viewProfile: @json(__('ui.view_profile')),
            };
            window.socialiteI18n = Object.assign({}, window.socialiteI18n || {}, socialiteI18n);

            const setComposerBusy = (busy) => {
                if (!composerSubmit) {
                    return;
                }
                composerSubmit.disabled = busy;
                composerSubmit.classList.toggle('opacity-60', busy);
                composerSubmit.classList.toggle('pointer-events-none', busy);
                composerSubmit.textContent = busy ? (composerSubmit.dataset.publishingLabel || socialiteI18n.publishing) : (composerSubmit.dataset.publishLabel || socialiteI18n.publish);
            };

            const setComposerStatus = (message, type = 'info') => {
                if (!composerStatus) {
                    return;
                }
                composerStatus.textContent = message || '';
                composerStatus.classList.toggle('hidden', !message);
                composerStatus.classList.toggle('text-red-600', type === 'error');
                composerStatus.classList.toggle('text-emerald-600', type === 'success');
                composerStatus.classList.toggle('text-slate-500', type !== 'error' && type !== 'success');
            };

            const firstValidationMessage = (payload, fallback = socialiteI18n.postPublishFailed) => {
                if (payload?.errors && typeof payload.errors === 'object') {
                    const firstKey = Object.keys(payload.errors)[0];
                    const firstError = firstKey ? payload.errors[firstKey] : null;
                    if (Array.isArray(firstError) && firstError[0]) {
                        return firstError[0];
                    }
                }
                return payload?.message || fallback;
            };

            const refreshCurrentPreview = () => {
                const url = new URL(window.location.href);
                url.searchParams.delete('page');
                url.searchParams.delete('fragment');
                window.location.assign(url.toString());
            };

            const feelingInput = modal.querySelector('[data-socialite-feeling-key]');
            const feelingLabel = modal.querySelector('[data-socialite-feeling-label]');
            const feelingButtons = Array.from(modal.querySelectorAll('[data-socialite-feeling]'));
            const syncFeelingButtons = (selectedFeeling) => {
                const activeFeeling = selectedFeeling || 'none';
                feelingButtons.forEach((button) => {
                    const isActive = (button.dataset.socialiteFeeling || 'none') === activeFeeling;
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    button.classList.toggle('ring-2', isActive);
                    button.classList.toggle('ring-orange-200', isActive);
                });
            };

            feelingButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const selectedFeeling = button.dataset.socialiteFeeling || 'none';
                    if (feelingInput) {
                        feelingInput.value = selectedFeeling;
                    }
                    if (feelingLabel) {
                        feelingLabel.textContent = button.dataset.socialiteFeelingText || socialiteI18n.feeling;
                    }
                    syncFeelingButtons(selectedFeeling);
                });
            });
            syncFeelingButtons(feelingInput?.value || 'none');

            const mediaInput = modal.querySelector('#socialite_feed_media');
            const mediaCount = modal.querySelector('[data-socialite-media-count]');
            const mediaPreviewWrap = modal.querySelector('[data-socialite-media-preview-wrap]');
            const mediaPreview = modal.querySelector('[data-socialite-media-preview]');
            const mediaClear = modal.querySelector('[data-socialite-media-clear]');
            const mediaStatus = modal.querySelector('[data-socialite-media-status]');
            const mediaMaxFiles = Number.parseInt(mediaInput?.dataset.socialiteMediaMaxFiles || '12', 10);
            const mediaMaxMb = Number.parseInt(mediaInput?.dataset.socialiteMediaMaxMb || '100', 10);
            const mediaMaxBytes = Number.parseInt(mediaInput?.dataset.socialiteMediaMaxBytes || String(mediaMaxMb * 1024 * 1024), 10);
            let selectedMediaFiles = [];
            let mediaObjectUrls = [];
            let clearGifSelection = null;

            const revokeMediaObjectUrls = () => {
                mediaObjectUrls.forEach((url) => URL.revokeObjectURL(url));
                mediaObjectUrls = [];
            };

            const setMediaStatus = (message) => {
                if (!mediaStatus) {
                    return;
                }
                mediaStatus.textContent = message || '';
                mediaStatus.classList.toggle('hidden', !message);
            };

            const formatBytes = (bytes) => {
                const mb = bytes / (1024 * 1024);
                return `${mb >= 10 ? Math.round(mb) : mb.toFixed(1)} MB`;
            };

            const validateSelectedMedia = (files) => {
                const accepted = [];
                const rejected = [];
                const maxFiles = Number.isFinite(mediaMaxFiles) && mediaMaxFiles > 0 ? mediaMaxFiles : 12;
                const maxBytes = Number.isFinite(mediaMaxBytes) && mediaMaxBytes > 0 ? mediaMaxBytes : (mediaMaxMb * 1024 * 1024);

                files.forEach((file) => {
                    if (accepted.length >= maxFiles) {
                        rejected.push(`Only ${maxFiles} files are allowed.`);
                        return;
                    }

                    if (file.size > maxBytes) {
                        rejected.push(`${file.name || 'File'} is larger than ${formatBytes(maxBytes)}.`);
                        return;
                    }

                    accepted.push(file);
                });

                return {
                    accepted,
                    message: rejected.length ? [...new Set(rejected)].join(' ') : '',
                };
            };

            const syncMediaInput = () => {
                if (!mediaInput) {
                    return;
                }

                try {
                    const transfer = new DataTransfer();
                    selectedMediaFiles.forEach((file) => transfer.items.add(file));
                    mediaInput.files = transfer.files;
                } catch (error) {
                    // Older browsers may not allow programmatic FileList changes. In that rare case
                    // the visual preview still works, but individual removal may not affect submit.
                }
            };

            const renderMediaPreview = () => {
                if (!mediaPreview || !mediaPreviewWrap) {
                    return;
                }

                revokeMediaObjectUrls();
                mediaPreview.innerHTML = '';

                const count = selectedMediaFiles.length;
                if (mediaCount) {
                    mediaCount.textContent = count > 0 ? String(count) : '';
                    mediaCount.classList.toggle('hidden', count === 0);
                }
                mediaPreviewWrap.classList.toggle('hidden', count === 0);

                selectedMediaFiles.forEach((file, index) => {
                    const url = URL.createObjectURL(file);
                    mediaObjectUrls.push(url);

                    const item = document.createElement('div');
                    item.className = 'group relative overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-sm dark:border-slate-700 dark:bg-slate-800';

                    const media = file.type.startsWith('video/')
                        ? document.createElement('video')
                        : document.createElement('img');
                    media.src = url;
                    media.className = 'h-24 w-full object-cover sm:h-28';
                    if (media.tagName === 'VIDEO') {
                        media.muted = true;
                        media.playsInline = true;
                        media.preload = 'metadata';
                    }

                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'absolute right-1.5 top-1.5 grid h-7 w-7 place-items-center rounded-full bg-black/70 text-white shadow transition hover:bg-red-600';
                    remove.setAttribute('aria-label', socialiteI18n.removeSelectedMedia);
                    remove.innerHTML = '<ion-icon name="trash-outline" class="text-base"></ion-icon>';
                    remove.addEventListener('click', () => {
                        selectedMediaFiles.splice(index, 1);
                        setMediaStatus('');
                        syncMediaInput();
                        renderMediaPreview();
                    });

                    const label = document.createElement('div');
                    label.className = 'absolute bottom-0 left-0 right-0 truncate bg-black/55 px-2 py-1 text-[10px] font-semibold text-white';
                    label.textContent = `${file.type.startsWith('video/') ? socialiteI18n.video : socialiteI18n.photo} · ${formatBytes(file.size)}`;

                    item.appendChild(media);
                    item.appendChild(remove);
                    item.appendChild(label);
                    mediaPreview.appendChild(item);
                });
            };

            const clearMediaSelection = (message = '') => {
                selectedMediaFiles = [];
                setMediaStatus(message);
                if (mediaInput) {
                    mediaInput.value = '';
                }
                syncMediaInput();
                renderMediaPreview();
            };

            if (mediaInput) {
                mediaInput.addEventListener('change', () => {
                    const files = mediaInput.files ? Array.from(mediaInput.files) : [];
                    const validated = validateSelectedMedia(files);
                    selectedMediaFiles = validated.accepted;
                    setMediaStatus(validated.message);
                    if (selectedMediaFiles.length > 0 && typeof clearGifSelection === 'function') {
                        clearGifSelection('GIF removed because media was selected.');
                    }
                    syncMediaInput();
                    renderMediaPreview();
                });
            }

            if (mediaClear) {
                mediaClear.addEventListener('click', () => clearMediaSelection());
            }

            const gifPanel = modal.querySelector('[data-socialite-panel="gif"]');
            if (!gifPanel) {
                return;
            }

            const gifFields = {
                provider: modal.querySelector('[data-socialite-gif-provider]'),
                id: modal.querySelector('[data-socialite-gif-id]'),
                url: modal.querySelector('[data-socialite-gif-url]'),
                preview: modal.querySelector('[data-socialite-gif-preview-url]'),
                title: modal.querySelector('[data-socialite-gif-title]'),
                source: modal.querySelector('[data-socialite-gif-source-url]'),
            };
            const gifQuery = gifPanel.querySelector('[data-socialite-gif-query]');
            const gifSearch = gifPanel.querySelector('[data-socialite-gif-search]');
            const gifResults = gifPanel.querySelector('[data-socialite-gif-results]');
            const gifStatus = gifPanel.querySelector('[data-socialite-gif-status]');
            const gifSelected = gifPanel.querySelector('[data-socialite-gif-selected]');
            const gifSelectedImage = gifPanel.querySelector('[data-socialite-gif-selected-image]');
            const gifSelectedTitle = gifPanel.querySelector('[data-socialite-gif-selected-title]');
            const gifClear = gifPanel.querySelector('[data-socialite-gif-clear]');

            const setStatus = (message) => {
                if (gifStatus) {
                    gifStatus.textContent = message || socialiteI18n.poweredByGiphy;
                }
            };

            const clearGif = (message = '') => {
                const hadGif = Boolean(gifFields.url?.value);
                Object.values(gifFields).forEach((field) => {
                    if (field) {
                        field.value = '';
                    }
                });
                if (gifSelected) {
                    gifSelected.classList.add('hidden');
                }
                if (gifClear) {
                    gifClear.hidden = true;
                }
                if (message && hadGif) {
                    setStatus(message);
                }
            };
            clearGifSelection = clearGif;

            const selectGif = (gif) => {
                if (!gif || !gif.gif_url) {
                    return;
                }
                if (selectedMediaFiles.length > 0 || (mediaInput?.files?.length || 0) > 0) {
                    clearMediaSelection('Media removed because GIF was selected.');
                }
                if (gifFields.provider) gifFields.provider.value = gif.provider || 'giphy';
                if (gifFields.id) gifFields.id.value = gif.id || '';
                if (gifFields.url) gifFields.url.value = gif.gif_url || '';
                if (gifFields.preview) gifFields.preview.value = gif.preview_url || gif.gif_url || '';
                if (gifFields.title) gifFields.title.value = gif.title || 'GIF';
                if (gifFields.source) gifFields.source.value = gif.source_url || '';

                if (gifSelectedImage) {
                    gifSelectedImage.src = gif.preview_url || gif.gif_url;
                }
                if (gifSelectedTitle) {
                    gifSelectedTitle.textContent = gif.title || 'GIF';
                }
                if (gifSelected) {
                    gifSelected.classList.remove('hidden');
                }
                if (gifClear) {
                    gifClear.hidden = false;
                }
                setStatus('GIF selected.');
            };

            const renderGifs = (payload) => {
                if (!gifResults) {
                    return;
                }
                const results = Array.isArray(payload?.results) ? payload.results : [];
                gifResults.innerHTML = '';

                if (payload?.message) {
                    setStatus(payload.message);
                } else {
                    setStatus(payload?.attribution || socialiteI18n.poweredByGiphy);
                }

                results.forEach((gif) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-100 dark:bg-dark3 dark:ring-slate-700';
                    button.innerHTML = `<img src="${gif.preview_url || gif.gif_url}" alt="${gif.title || 'GIF'}" class="h-24 w-full object-cover">`;
                    button.addEventListener('click', () => selectGif(gif));
                    gifResults.appendChild(button);
                });

                if (!results.length && !payload?.message) {
                    setStatus(socialiteI18n.noGifsFound);
                }
            };

            const loadGifs = async (query = '') => {
                if (!gifResults) {
                    return;
                }

                setStatus(socialiteI18n.loadingGifs);
                const baseUrl = query ? gifPanel.dataset.socialiteGifSearchUrl : gifPanel.dataset.socialiteGifTrendingUrl;
                const url = new URL(baseUrl, window.location.origin);
                if (query) {
                    url.searchParams.set('q', query);
                }
                url.searchParams.set('limit', '12');

                try {
                    const response = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                    renderGifs(await response.json());
                } catch (error) {
                    setStatus('GIFs could not be loaded');
                }
            };

            let gifTrendingLoaded = false;
            const loadTrendingGifsOnce = () => {
                if (gifTrendingLoaded) {
                    return;
                }
                gifTrendingLoaded = true;
                loadGifs('');
            };

            modal.querySelectorAll('[data-socialite-panel-toggle="gif"]').forEach((button) => {
                button.addEventListener('click', loadTrendingGifsOnce);
            });

            if (gifSearch) {
                gifSearch.addEventListener('click', () => loadGifs(gifQuery ? gifQuery.value.trim() : ''));
            }
            if (gifQuery) {
                gifQuery.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        loadGifs(gifQuery.value.trim());
                    }
                });
            }
            if (gifClear) {
                gifClear.addEventListener('click', clearGif);
            }

            if (composerForm) {
                composerForm.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    setComposerStatus('');

                    const formData = new FormData(composerForm);
                    const body = String(formData.get('body') || '').trim();
                    const pollOptions = formData.getAll('poll_options[]').map((value) => String(value || '').trim()).filter(Boolean);
                    const hasPoll = pollOptions.length >= 2;
                    const hasGif = Boolean(gifFields.url?.value);
                    const currentMediaFiles = mediaInput?.files ? Array.from(mediaInput.files) : selectedMediaFiles;
                    const validatedMedia = validateSelectedMedia(currentMediaFiles);
                    selectedMediaFiles = validatedMedia.accepted;
                    setMediaStatus(validatedMedia.message);
                    syncMediaInput();
                    renderMediaPreview();
                    const hasMedia = selectedMediaFiles.length > 0;

                    if (!body && !hasMedia && !hasPoll && !hasGif) {
                        setComposerStatus('Write something, add media, select a GIF, or create a poll first.', 'error');
                        return;
                    }

                    setComposerBusy(true);

                    try {
                        const response = await fetch(composerForm.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: formData,
                            credentials: 'same-origin',
                        });

                        let payload = null;
                        try {
                            payload = await response.json();
                        } catch (error) {
                            payload = null;
                        }

                        if (!response.ok) {
                            throw new Error(firstValidationMessage(payload));
                        }

                        setComposerStatus(payload?.message || socialiteI18n.postPublishedRefreshing, 'success');
                        window.setTimeout(refreshCurrentPreview, 650);
                    } catch (error) {
                        setComposerStatus(error.message || socialiteI18n.postPublishFailed, 'error');
                        setComposerBusy(false);
                    }
                });
            }
        })();
    </script>



    <script>
        (() => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const reactionEmojis = {
                like: '👍',
                love: '❤️',
                funny: '😂',
                wow: '😯',
                sad: '😢',
                angry: '😡',
                dislike: '👎',
                happy: '😊'
            };

            const getSocialiteText = (key, fallback) => {
                return (window.socialiteI18n && window.socialiteI18n[key]) || fallback;
            };

            const ensureReactionModal = () => {
                let modal = document.querySelector('[data-socialite-reaction-modal]');
                if (modal) return modal;

                modal = document.createElement('div');
                modal.className = 'hh-reaction-modal hidden';
                modal.dataset.socialiteReactionModal = '1';
                modal.innerHTML = `
                    <div class="hh-reaction-modal__backdrop" data-socialite-reaction-modal-close></div>
                    <div class="hh-reaction-modal__panel" role="dialog" aria-modal="true" aria-labelledby="hh-reaction-modal-title">
                        <div class="hh-reaction-modal__head">
                            <h3 id="hh-reaction-modal-title">${escapeHtml(getSocialiteText('reactionsTitle', 'Reaktionen'))}</h3>
                            <button type="button" class="hh-reaction-modal__close" data-socialite-reaction-modal-close aria-label="Close">×</button>
                        </div>
                        <div class="hh-reaction-modal__tabs" data-socialite-reaction-tabs></div>
                        <div class="hh-reaction-modal__body" data-socialite-reaction-users></div>
                    </div>
                `;
                document.body.appendChild(modal);
                modal.querySelectorAll('[data-socialite-reaction-modal-close]').forEach((button) => {
                    button.addEventListener('click', () => closeReactionModal());
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                        closeReactionModal();
                    }
                });
                return modal;
            };

            const closeReactionModal = () => {
                const modal = document.querySelector('[data-socialite-reaction-modal]');
                if (!modal) return;
                modal.classList.add('hidden');
                document.documentElement.classList.remove('hh-reaction-modal-open');
            };

            const renderReactionUsers = (modal, users, type = 'all') => {
                const body = modal.querySelector('[data-socialite-reaction-users]');
                if (!body) return;

                const filtered = type === 'all' ? users : users.filter((user) => user.type === type);
                if (!filtered.length) {
                    body.innerHTML = `<div class="hh-reaction-modal__empty">${escapeHtml(getSocialiteText('reactionsEmpty', 'Noch keine Reaktionen.'))}</div>`;
                    return;
                }

                body.innerHTML = filtered.map((user) => `
                    <div class="hh-reaction-user">
                        <a href="${escapeHtml(user.profile_url || '#')}" class="hh-reaction-user__avatar">
                            <img src="${escapeHtml(user.avatar || '')}" alt="${escapeHtml(user.name || 'Hunter')}">
                            <span title="${escapeHtml(user.reaction_label || '')}">${escapeHtml(user.reaction_emoji || '👍')}</span>
                        </a>
                        <a href="${escapeHtml(user.profile_url || '#')}" class="hh-reaction-user__meta">
                            <strong>${escapeHtml(user.name || 'HNT Hunter')}</strong>
                            ${user.username ? `<small>${escapeHtml(user.username)}</small>` : ''}
                        </a>
                        <a href="${escapeHtml(user.profile_url || '#')}" class="hh-reaction-user__action">${escapeHtml(getSocialiteText('viewProfile', 'Profil ansehen'))}</a>
                    </div>
                `).join('');
            };

            const renderReactionTabs = (modal, payload) => {
                const tabs = modal.querySelector('[data-socialite-reaction-tabs]');
                const users = Array.isArray(payload.users) ? payload.users : [];
                const stats = Array.isArray(payload.stats) ? payload.stats : [];
                if (!tabs) return;

                const total = Number.parseInt(payload.total || users.length || 0, 10) || 0;
                const tabItems = [
                    { type: 'all', label: getSocialiteText('reactionsAll', 'Alle'), emoji: '', count: total },
                    ...stats
                ];

                tabs.innerHTML = tabItems.map((item, index) => `
                    <button type="button" class="hh-reaction-tab ${index === 0 ? 'is-active' : ''}" data-reaction-filter="${escapeHtml(item.type || 'all')}">
                        ${item.emoji ? `<span>${escapeHtml(item.emoji)}</span>` : ''}
                        <strong>${escapeHtml(item.label || '')}</strong>
                        <em>${formatCount(item.count || 0)}</em>
                    </button>
                `).join('');

                tabs.querySelectorAll('[data-reaction-filter]').forEach((button) => {
                    button.addEventListener('click', () => {
                        tabs.querySelectorAll('.hh-reaction-tab').forEach((tab) => tab.classList.remove('is-active'));
                        button.classList.add('is-active');
                        renderReactionUsers(modal, users, button.dataset.reactionFilter || 'all');
                    });
                });

                renderReactionUsers(modal, users, 'all');
            };

            const openReactionModal = async (button) => {
                const url = button?.dataset?.socialiteReactionListUrl;
                if (!url) return;

                const modal = ensureReactionModal();
                modal.classList.remove('hidden');
                document.documentElement.classList.add('hh-reaction-modal-open');

                const body = modal.querySelector('[data-socialite-reaction-users]');
                const tabs = modal.querySelector('[data-socialite-reaction-tabs]');
                if (tabs) tabs.innerHTML = '';
                if (body) body.innerHTML = `<div class="hh-reaction-modal__empty">${escapeHtml(getSocialiteText('loading', 'Lädt...'))}</div>`;

                try {
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload?.message || getSocialiteText('reactionsLoadFailed', 'Reaktionen konnten nicht geladen werden'));
                    renderReactionTabs(modal, payload);
                } catch (error) {
                    if (body) body.innerHTML = `<div class="hh-reaction-modal__empty">${escapeHtml(error.message || getSocialiteText('reactionsLoadFailed', 'Reaktionen konnten nicht geladen werden'))}</div>`;
                }
            };

            const formatCount = (value) => {
                const number = Number.parseInt(String(value).replace(/\D/g, ''), 10);
                if (!Number.isFinite(number)) {
                    return '0';
                }
                return new Intl.NumberFormat(document.documentElement.lang || 'en').format(number);
            };

            const setButtonBusy = (button, busy) => {
                if (!button) return;
                button.disabled = busy;
                button.classList.toggle('opacity-60', busy);
                button.classList.toggle('pointer-events-none', busy);
            };

            const updateSocialitePollCard = (card, selectedButton) => {
                if (!card || !selectedButton) return;

                const optionButtons = Array.from(card.querySelectorAll('[data-socialite-poll-option]'));
                const previousButton = optionButtons.find((button) => button.dataset.selected === '1');
                const totalNode = card.querySelector('[data-socialite-poll-total]');
                let totalVotes = Number.parseInt(totalNode?.dataset.totalVotes || '0', 10);

                if (!Number.isFinite(totalVotes)) {
                    totalVotes = 0;
                }

                if (!previousButton) {
                    totalVotes += 1;
                }

                optionButtons.forEach((button) => {
                    let votes = Number.parseInt(button.dataset.votes || '0', 10);
                    if (!Number.isFinite(votes)) {
                        votes = 0;
                    }

                    if (previousButton && button === previousButton && button !== selectedButton) {
                        votes = Math.max(0, votes - 1);
                    }

                    if (button === selectedButton && button !== previousButton) {
                        votes += 1;
                    }

                    const isSelected = button === selectedButton;
                    const percent = totalVotes > 0 ? Math.round((votes / totalVotes) * 100) : 0;
                    const bar = button.querySelector('[data-socialite-poll-bar]');
                    const percentNode = button.querySelector('[data-socialite-poll-percent]');
                    const checkNode = button.querySelector('[data-socialite-poll-check]');

                    button.dataset.votes = String(votes);
                    button.dataset.selected = isSelected ? '1' : '0';
                    button.classList.toggle('ring-blue-300', isSelected);

                    if (bar) {
                        bar.style.width = `${percent}%`;
                    }
                    if (percentNode) {
                        percentNode.textContent = `${percent}%`;
                    }
                    if (checkNode) {
                        checkNode.classList.toggle('hidden', !isSelected);
                    }
                });

                if (totalNode) {
                    totalNode.dataset.totalVotes = String(totalVotes);
                    totalNode.textContent = `${formatCount(totalVotes)} ${totalVotes === 1 ? socialiteI18n.vote : socialiteI18n.votes}`;
                }
            };

            const showSocialiteToast = (message) => {
                const toast = document.createElement('div');
                toast.className = 'fixed bottom-6 left-1/2 z-[10000] -translate-x-1/2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-xl dark:bg-white dark:text-slate-900';
                toast.textContent = message;
                document.body.appendChild(toast);
                window.setTimeout(() => toast.remove(), 2200);
            };

            const reportModal = document.querySelector('[data-socialite-report-modal]');
            const reportForm = reportModal?.querySelector('[data-socialite-report-form]');
            const reportStatus = reportModal?.querySelector('[data-socialite-report-status]');
            const reportType = reportModal?.querySelector('[data-socialite-report-type]');
            const reportId = reportModal?.querySelector('[data-socialite-report-id]');
            const reportLabel = reportModal?.querySelector('[data-socialite-report-label]');

            const closeReportModal = () => {
                if (!reportModal) return;
                reportModal.classList.add('hidden');
                reportModal.classList.remove('flex');
                reportModal.setAttribute('aria-hidden', 'true');
            };

            const openReportModal = (button) => {
                if (!reportModal || !reportForm) return;
                reportForm.reset();
                if (reportStatus) {
                    reportStatus.textContent = '';
                    reportStatus.classList.add('hidden');
                }
                if (reportType) reportType.value = button.dataset.reportType || '';
                if (reportId) reportId.value = button.dataset.reportId || '';
                if (reportLabel) reportLabel.textContent = button.dataset.reportLabel || socialiteI18n.reportContent;
                reportModal.classList.remove('hidden');
                reportModal.classList.add('flex');
                reportModal.setAttribute('aria-hidden', 'false');
            };

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const postTextToHtml = (value) => escapeHtml(value).replace(/\r?\n/g, '<br>');

            const safeInternalHref = (value) => {
                const href = String(value || '');
                return href.startsWith('/') && !href.startsWith('//') ? href : '#';
            };

            const renderSocialiteInternalLinkPreviews = (article, previews) => {
                const bodyWrap = article?.querySelector('[data-socialite-post-body-wrap]');
                const bodyNode = article?.querySelector('[data-socialite-post-body]');
                if (!bodyWrap || !bodyNode) return;

                bodyWrap.querySelectorAll('[data-hnt-internal-link-previews]').forEach((node) => node.remove());

                if (!Array.isArray(previews) || previews.length === 0) {
                    return;
                }

                const items = previews.map((preview) => {
                    const href = safeInternalHref(preview?.url);
                    const icon = escapeHtml(preview?.icon || 'link-outline');
                    const label = escapeHtml(preview?.label || 'Internal HNT.rocks link');
                    const title = escapeHtml(preview?.title || 'HNT.rocks');
                    const description = escapeHtml(preview?.description || '');
                    const displayUrl = escapeHtml(preview?.display_url || 'hnt.rocks');

                    return `
                        <a href="${href}" class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900/50 dark:hover:bg-slate-800">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-500 dark:bg-blue-500/10">
                                <ion-icon name="${icon}" class="text-xl"></ion-icon>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs font-bold uppercase tracking-wide text-blue-500">${label}</span>
                                <span class="block truncate text-sm font-bold text-slate-900 dark:text-white">${title}</span>
                                <span class="mt-0.5 block line-clamp-2 text-xs font-normal leading-5 text-slate-500 dark:text-white/60">${description}</span>
                                <span class="mt-1 block truncate text-xs font-normal text-slate-400 dark:text-white/40">${displayUrl}</span>
                            </span>
                        </a>
                    `;
                }).join('');

                bodyNode.insertAdjacentHTML('afterend', `<div class="mt-3 space-y-2" data-hnt-internal-link-previews>${items}</div>`);
            };

            const socialiteLucide = (name, className = 'h-4 w-4') => {
                const icons = {
                    more: '<circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle>',
                    edit: '<path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path>',
                    trash: '<path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line>',
                };

                return `<svg class="${className}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${icons[name] || ''}</svg>`;
            };

            const commentTemplate = (comment) => {
                const avatar = comment?.user?.avatar_url || '/assets/vikinger/img/default-avatar.svg';
                const name = comment?.user?.name || 'User';
                const profile = comment?.user?.profile_url || '#';
                const createdAt = comment?.created_at_label || 'now';
                const body = comment?.body_html || '';
                const rawBody = escapeHtml(comment?.body || '');
                const commentId = comment?.id || '';
                const parentId = comment?.parent_id || '';
                const rootId = comment?.root_id || parentId || commentId;
                const reactionUrl = comment?.routes?.reaction || '#';
                const updateUrl = comment?.routes?.update || '';
                const deleteUrl = comment?.routes?.delete || '';
                const reactionCount = Number.parseInt(comment?.reaction_count || '0', 10) || 0;
                const viewerReaction = comment?.viewer_reaction || null;
                const isReply = !!comment?.is_reply || !!parentId;
                const avatarClass = isReply ? 'w-6 h-6 mt-1' : 'w-8 h-8';
                const bubbleClass = isReply ? 'bg-slate-50 dark:bg-slate-800/70' : 'bg-secondery';
                const replyButton = isReply ? '' : `<button type="button" class="hover:text-blue-500" data-socialite-comment-reply data-comment-id="${commentId}" data-comment-author="${escapeHtml(name)}">${socialiteI18n.reply}</button>`;
                const actions = (comment?.can_edit || comment?.can_delete) ? `
                    <div class="relative shrink-0 -mr-1">
                        <button type="button" class="grid h-7 w-7 place-items-center rounded-full text-slate-500 hover:bg-white/70 hover:text-slate-900 dark:text-white/60 dark:hover:bg-white/10 dark:hover:text-white" aria-label="${socialiteI18n.commentOptions}">
                            ${socialiteLucide('more')}
                        </button>
                        <div class="w-[190px]" uk-dropdown="pos: bottom-right; animation: uk-animation-scale-up uk-transform-origin-top-right; animate-out: true; mode: click">
                            <nav>
                                ${comment?.can_edit ? `<button type="button" class="w-full flex items-center gap-3 px-3 py-2 text-left hover:bg-secondery rounded-lg dark:hover:bg-white/10" data-socialite-comment-edit>${socialiteLucide('edit')}${socialiteI18n.edit}</button>` : ''}
                                ${comment?.can_delete ? `<button type="button" class="w-full flex items-center gap-3 px-3 py-2 text-left text-red-600 hover:bg-red-50 rounded-lg dark:hover:bg-red-500/10" data-socialite-comment-delete data-delete-url="${deleteUrl}">${socialiteLucide('trash')}${socialiteI18n.delete}</button>` : ''}
                            </nav>
                        </div>
                    </div>
                ` : '';

                return `
                    <div class="flex gap-3 relative socialite-live-comment ${isReply ? 'socialite-live-comment-reply' : ''}" data-socialite-comment-id="${commentId}" data-socialite-comment-parent-id="${parentId}" data-socialite-root-id="${rootId}" data-socialite-comment-update-url="${updateUrl}" data-socialite-comment-delete-url="${deleteUrl}">
                        <a href="${profile}" class="shrink-0">
                            <img src="${avatar}" alt="${escapeHtml(name)}" class="${avatarClass} rounded-full object-cover">
                        </a>
                        <div class="flex-1 min-w-0">
                            <div class="${bubbleClass} rounded-xl px-4 py-2" data-socialite-comment-bubble>
                                <div class="flex items-start gap-2">
                                    <div class="min-w-0 flex-1">
                                        <a href="${profile}" class="font-semibold text-black dark:text-white">${escapeHtml(name)}</a>
                                        <div class="text-sm mt-0.5 leading-6 text-slate-700 dark:text-white/90 break-words" data-socialite-comment-body data-raw-body="${rawBody}">${body}</div>
                                    </div>
                                    ${actions}
                                </div>
                            </div>
                            <div class="flex items-center gap-3 mt-1 text-xs text-gray-500 dark:text-white/60">
                                <span>${createdAt}</span>
                                <form method="post" action="${reactionUrl}" class="m-0 inline-flex" data-socialite-comment-reaction-form>
                                    <input type="hidden" name="_token" value="${csrfToken}">
                                    <input type="hidden" name="type" value="like">
                                    <button type="submit" class="hover:text-blue-500 ${viewerReaction ? 'text-blue-500 font-semibold' : ''}" data-socialite-comment-like-button>
                                        ${viewerReaction ? socialiteI18n.liked : socialiteI18n.like} <span data-socialite-comment-like-count class="${reactionCount > 0 ? '' : 'hidden'}">${reactionCount}</span>
                                    </button>
                                </form>
                                ${replyButton}
                            </div>
                        </div>
                    </div>
                `;
            };

            const socialiteRepliesClosedLabel = (count) => {
                const template = count === 1 ? socialiteI18n.loadReply : socialiteI18n.loadReplies;
                return String(template || '').replace(':count', count);
            };

            const setSocialiteRepliesOpen = (thread, open) => {
                if (!thread) return;

                const toggle = thread.querySelector('[data-socialite-replies-toggle]');
                const replies = thread.querySelector('[data-socialite-replies]');
                const label = toggle?.querySelector('[data-socialite-replies-toggle-label]');
                const icon = toggle?.querySelector('[data-socialite-replies-toggle-icon]');
                const count = Number.parseInt(toggle?.dataset.repliesCount || '0', 10) || 0;

                if (replies) {
                    replies.classList.toggle('hidden', !open);
                }
                if (toggle) {
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                }
                if (label) {
                    label.textContent = open ? socialiteI18n.repliesHide : socialiteRepliesClosedLabel(count);
                }
                if (icon) {
                    icon.classList.toggle('rotate-180', open);
                }
            };

            const ensureSocialiteRepliesToggle = (thread, rootId, count = 1) => {
                if (!thread || !rootId) return null;

                let toggle = thread.querySelector('[data-socialite-replies-toggle]');
                if (toggle) return toggle;

                const rootComment = thread.querySelector(`[data-socialite-comment-id="${rootId}"]`);
                if (!rootComment) return null;

                const safeCount = Math.max(1, Number.parseInt(count || '1', 10) || 1);
                rootComment.insertAdjacentHTML('afterend', `
                    <button type="button" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-500 hover:text-blue-600" style="margin-left: 92px;" data-socialite-replies-toggle data-root-id="${rootId}" data-replies-count="${safeCount}" aria-expanded="false">
                        <ion-icon name="chevron-down-outline" class="text-sm duration-200" data-socialite-replies-toggle-icon></ion-icon>
                        <span data-socialite-replies-toggle-label>${socialiteRepliesClosedLabel(safeCount)}</span>
                    </button>
                `);

                return thread.querySelector('[data-socialite-replies-toggle]');
            };

            const ensureSocialiteRepliesContainer = (list, rootId, open = false) => {
                if (!list || !rootId) return null;

                let thread = list.querySelector(`[data-socialite-comment-thread="${rootId}"]`);
                if (!thread) {
                    const rootComment = list.querySelector(`[data-socialite-comment-id="${rootId}"]`);
                    thread = rootComment?.closest('[data-socialite-comment-thread]') || null;
                }
                if (!thread) return null;

                let replies = thread.querySelector(`[data-socialite-replies="${rootId}"]`);
                if (!replies) {
                    thread.insertAdjacentHTML('beforeend', `<div class="hidden space-y-2" style="margin-left: 92px;" data-socialite-replies="${rootId}"></div>`);
                    replies = thread.querySelector(`[data-socialite-replies="${rootId}"]`);
                }

                ensureSocialiteRepliesToggle(thread, rootId, Math.max(1, replies?.children?.length || 1));
                setSocialiteRepliesOpen(thread, !!open);

                return replies;
            };

            const syncSocialiteRepliesCount = (list, rootId) => {
                if (!list || !rootId) return;

                const thread = list.querySelector(`[data-socialite-comment-thread="${rootId}"]`);
                if (!thread) return;

                const replies = thread.querySelector(`[data-socialite-replies="${rootId}"]`);
                const count = replies?.querySelectorAll('[data-socialite-comment-id]')?.length || 0;
                const existingToggle = thread.querySelector('[data-socialite-replies-toggle]');

                if (count < 1) {
                    existingToggle?.remove();
                    replies?.classList.add('hidden');
                    return;
                }

                const toggle = ensureSocialiteRepliesToggle(thread, rootId, count);
                if (!toggle) return;

                toggle.dataset.repliesCount = String(count);
                const label = toggle.querySelector('[data-socialite-replies-toggle-label]');
                if (label && toggle.getAttribute('aria-expanded') !== 'true') {
                    label.textContent = socialiteRepliesClosedLabel(count);
                }
            };

            const insertSocialiteComment = (list, comment) => {
                if (!list || !comment) return;

                const moreLink = list.querySelector('[data-socialite-more-comments]');
                const rootId = comment.root_id || comment.parent_id;

                if (comment.is_reply && rootId) {
                    const replies = ensureSocialiteRepliesContainer(list, rootId, true);
                    if (replies) {
                        replies.insertAdjacentHTML('beforeend', commentTemplate(comment));
                        syncSocialiteRepliesCount(list, rootId);
                        const thread = list.querySelector(`[data-socialite-comment-thread="${rootId}"]`);
                        setSocialiteRepliesOpen(thread, true);
                        return;
                    }
                }

                const html = `<div class="space-y-2" data-socialite-comment-thread="${comment.id}">${commentTemplate(comment)}<div class="hidden space-y-2" style="margin-left: 92px;" data-socialite-replies="${comment.id}"></div></div>`;

                if (moreLink) {
                    moreLink.insertAdjacentHTML('beforebegin', html);
                } else {
                    list.insertAdjacentHTML('beforeend', html);
                }
            };

            const clearSocialiteReplyState = (form) => {
                if (!form) return;
                const parentInput = form.querySelector('[data-socialite-comment-parent]');
                const input = form.querySelector('[data-socialite-comment-input]');
                const cancel = form.querySelector('[data-socialite-comment-reply-cancel]');

                if (parentInput) parentInput.value = '';
                if (input) {
                    input.placeholder = socialiteI18n.addCommentPlaceholder;
                    autoSizeSocialiteCommentInput(input);
                }
                if (cancel) cancel.classList.add('hidden');
            };

            const autoSizeSocialiteCommentInput = (input) => {
                if (!input || input.tagName !== 'TEXTAREA') return;
                input.style.height = '40px';
                const nextHeight = Math.min(input.scrollHeight, 144);
                input.style.height = `${Math.max(40, nextHeight)}px`;
            };

            const removeSocialiteReadMore = (node) => {
                if (!node) return;

                const next = node.nextElementSibling;
                if (next?.matches?.('[data-socialite-read-more-toggle]')) {
                    next.remove();
                }

                node.style.maxHeight = '';
                node.style.overflow = '';
                node.removeAttribute('data-socialite-read-more-bound');
                node.removeAttribute('data-socialite-read-more-open');
                node.removeAttribute('data-socialite-read-more-limit');
            };

            const applySocialiteReadMore = (node, force = false) => {
                if (!node || node.dataset.socialiteReadMoreBinding === '1') return;
                if (node.classList.contains('hidden')) return;
                if (!force && node.dataset.socialiteReadMoreBound === '1') return;

                node.dataset.socialiteReadMoreBinding = '1';
                removeSocialiteReadMore(node);

                const isPost = node.matches('[data-socialite-post-body]');
                const limit = isPost ? 240 : 168;

                window.requestAnimationFrame(() => {
                    try {
                        const fullHeight = node.scrollHeight || 0;
                        if (fullHeight <= limit + 18) return;

                        node.dataset.socialiteReadMoreBound = '1';
                        node.dataset.socialiteReadMoreOpen = '0';
                        node.dataset.socialiteReadMoreLimit = String(limit);
                        node.style.maxHeight = `${limit}px`;
                        node.style.overflow = 'hidden';

                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'mt-1 inline-flex items-center text-sm font-semibold text-blue-500 hover:text-blue-600 dark:text-blue-300 dark:hover:text-blue-200';
                        button.dataset.socialiteReadMoreToggle = '1';
                        button.textContent = 'Mehr lesen';

                        button.addEventListener('click', () => {
                            const isOpen = node.dataset.socialiteReadMoreOpen === '1';
                            const savedLimit = Number.parseInt(node.dataset.socialiteReadMoreLimit || String(limit), 10) || limit;

                            node.dataset.socialiteReadMoreOpen = isOpen ? '0' : '1';
                            node.style.maxHeight = isOpen ? `${savedLimit}px` : 'none';
                            node.style.overflow = isOpen ? 'hidden' : 'visible';
                            button.textContent = isOpen ? 'Mehr lesen' : 'Weniger anzeigen';
                        });

                        node.insertAdjacentElement('afterend', button);
                    } finally {
                        delete node.dataset.socialiteReadMoreBinding;
                    }
                });
            };

            const refreshSocialiteReadMore = (node) => {
                if (!node) return;
                removeSocialiteReadMore(node);
                applySocialiteReadMore(node, true);
            };

            const bindSocialiteReadMore = (root = document) => {
                root.querySelectorAll('[data-socialite-post-body], [data-socialite-comment-body]').forEach((node) => {
                    applySocialiteReadMore(node);
                });
            };

            const closeUkDropdownNear = (element) => {
                const dropdown = element?.closest?.('[uk-dropdown]');
                if (dropdown && window.UIkit?.dropdown) {
                    try {
                        window.UIkit.dropdown(dropdown).hide(false);
                    } catch (error) {
                        dropdown.classList.remove('uk-open');
                    }
                }
            };

            const editSocialitePost = (button) => {
                const article = button.closest('[data-hnt-socialite-post]');
                const bodyWrap = article?.querySelector('[data-socialite-post-body-wrap]');
                const bodyNode = article?.querySelector('[data-socialite-post-body]');
                const updateUrl = article?.dataset.socialitePostUpdateUrl;
                if (!article || !bodyWrap || !bodyNode || !updateUrl || bodyWrap.querySelector('[data-socialite-post-edit-form]')) return;

                closeUkDropdownNear(button);
                const original = bodyNode.dataset.rawBody || bodyNode.textContent || '';
                bodyWrap.classList.remove('hidden');
                bodyNode.classList.add('hidden');
                bodyWrap.insertAdjacentHTML('beforeend', `
                    <form class="mt-1 space-y-2" data-socialite-post-edit-form>
                        <textarea name="body" rows="3" maxlength="5000" class="w-full resize-none rounded-xl bg-slate-100 px-4 py-3 text-sm leading-6 text-slate-800 outline-none ring-1 ring-slate-200 focus:ring-blue-300 dark:bg-slate-800 dark:text-white dark:ring-slate-700" data-socialite-post-edit-input>${escapeHtml(original)}</textarea>
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold text-slate-500 hover:bg-slate-100 dark:hover:bg-white/10" data-socialite-post-edit-cancel>${socialiteI18n.cancel}</button>
                            <button type="submit" class="rounded-full bg-blue-600 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-blue-700" data-socialite-post-edit-save>${socialiteI18n.save}</button>
                        </div>
                    </form>
                `);
                const form = bodyWrap.querySelector('[data-socialite-post-edit-form]');
                const textarea = form.querySelector('[data-socialite-post-edit-input]');
                autoSizeSocialiteCommentInput(textarea);
                textarea.focus();

                form.querySelector('[data-socialite-post-edit-cancel]')?.addEventListener('click', () => {
                    form.remove();
                    bodyNode.classList.remove('hidden');
                    if (!(bodyNode.dataset.rawBody || '').trim()) {
                        bodyWrap.classList.add('hidden');
                    }
                });

                textarea.addEventListener('input', () => autoSizeSocialiteCommentInput(textarea));
                textarea.addEventListener('keydown', (event) => {
                    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                        event.preventDefault();
                        form.requestSubmit();
                    }
                });

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const save = form.querySelector('[data-socialite-post-edit-save]');
                    const nextBody = textarea.value.trim();
                    if (!nextBody) return;
                    setButtonBusy(save, true);

                    const data = new FormData();
                    data.set('body', nextBody);
                    data.set('visibility', article.dataset.socialitePostVisibility || 'public');

                    try {
                        const response = await fetch(updateUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: (() => { data.set('_method', 'PUT'); return data; })()
                        });
                        const payload = await response.json();
                        if (!response.ok) throw new Error(payload?.message || socialiteI18n.postUpdateFailed);
                        bodyNode.innerHTML = payload.body_html || postTextToHtml(nextBody);
                        bodyNode.dataset.rawBody = payload.body || nextBody;
                        renderSocialiteInternalLinkPreviews(article, payload.internal_link_previews || []);
                        form.remove();
                        bodyNode.classList.remove('hidden');
                        bodyWrap.classList.remove('hidden');
                        refreshSocialiteReadMore(bodyNode);
                        showSocialiteToast('Beitrag aktualisiert');
                    } catch (error) {
                        showSocialiteToast(error.message || 'Beitrag konnte nicht gespeichert werden');
                    } finally {
                        setButtonBusy(save, false);
                    }
                });
            };

            const deleteSocialitePost = async (button) => {
                const article = button.closest('[data-hnt-socialite-post]');
                const deleteUrl = article?.dataset.socialitePostDeleteUrl;
                if (!article || !deleteUrl) return;

                closeUkDropdownNear(button);
                if (!window.confirm(button.dataset.adminDelete === '1' ? socialiteI18n.deletePostAdminConfirm : socialiteI18n.deletePostConfirm)) {
                    return;
                }

                setButtonBusy(button, true);
                try {
                    const data = new FormData();
                    data.set('_method', 'DELETE');
                    const response = await fetch(deleteUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: data
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(payload?.message || socialiteI18n.postDeleteFailed);
                    article.remove();
                    showSocialiteToast(socialiteI18n.postDeleted);
                } catch (error) {
                    showSocialiteToast(error.message || socialiteI18n.postDeleteFailed);
                } finally {
                    setButtonBusy(button, false);
                }
            };

            const editSocialiteComment = (button) => {
                const comment = button.closest('[data-socialite-comment-id]');
                const bodyNode = comment?.querySelector('[data-socialite-comment-body]');
                const bubble = comment?.querySelector('[data-socialite-comment-bubble]');
                const updateUrl = comment?.dataset.socialiteCommentUpdateUrl;
                if (!comment || !bodyNode || !bubble || !updateUrl || bubble.querySelector('[data-socialite-comment-edit-form]')) return;

                closeUkDropdownNear(button);
                const original = bodyNode.dataset.rawBody || bodyNode.textContent || '';
                bodyNode.classList.add('hidden');
                bodyNode.insertAdjacentHTML('afterend', `
                    <form class="mt-2 space-y-2" data-socialite-comment-edit-form>
                        <textarea name="body" rows="2" maxlength="50000" class="w-full resize-none rounded-lg bg-white/80 px-3 py-2 text-sm leading-6 text-slate-800 outline-none ring-1 ring-slate-200 focus:ring-blue-300 dark:bg-slate-900/70 dark:text-white dark:ring-slate-700" data-socialite-comment-edit-input>${escapeHtml(original)}</textarea>
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" class="rounded-full px-2.5 py-1 text-xs font-semibold text-slate-500 hover:bg-white/70 dark:hover:bg-white/10" data-socialite-comment-edit-cancel>${socialiteI18n.cancel}</button>
                            <button type="submit" class="rounded-full bg-blue-600 px-3 py-1 text-xs font-bold text-white hover:bg-blue-700" data-socialite-comment-edit-save>${socialiteI18n.save}</button>
                        </div>
                    </form>
                `);

                const form = bubble.querySelector('[data-socialite-comment-edit-form]');
                const textarea = form.querySelector('[data-socialite-comment-edit-input]');
                autoSizeSocialiteCommentInput(textarea);
                textarea.focus();

                form.querySelector('[data-socialite-comment-edit-cancel]')?.addEventListener('click', () => {
                    form.remove();
                    bodyNode.classList.remove('hidden');
                });
                textarea.addEventListener('input', () => autoSizeSocialiteCommentInput(textarea));
                textarea.addEventListener('keydown', (event) => {
                    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                        event.preventDefault();
                        form.requestSubmit();
                    }
                });
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const save = form.querySelector('[data-socialite-comment-edit-save]');
                    const nextBody = textarea.value.trim();
                    if (!nextBody) return;
                    setButtonBusy(save, true);
                    const data = new FormData();
                    data.set('_method', 'PATCH');
                    data.set('body', nextBody);
                    try {
                        const response = await fetch(updateUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: data
                        });
                        const payload = await response.json();
                        if (!response.ok) throw new Error(payload?.message || socialiteI18n.commentUpdateFailed);
                        bodyNode.innerHTML = payload.body_html || postTextToHtml(nextBody);
                        bodyNode.dataset.rawBody = payload.body || nextBody;
                        form.remove();
                        bodyNode.classList.remove('hidden');
                        refreshSocialiteReadMore(bodyNode);
                    } catch (error) {
                        showSocialiteToast(error.message || socialiteI18n.commentSaveFailed);
                    } finally {
                        setButtonBusy(save, false);
                    }
                });
            };

            const deleteSocialiteComment = async (button) => {
                const comment = button.closest('[data-socialite-comment-id]');
                const article = button.closest('[data-hnt-socialite-post]');
                const list = article?.querySelector('[data-socialite-comments-list]');
                const countNode = article?.querySelector('[data-socialite-comment-count]');
                const deleteUrl = button.dataset.deleteUrl || comment?.dataset.socialiteCommentDeleteUrl;
                if (!comment || !deleteUrl) return;

                closeUkDropdownNear(button);
                if (!window.confirm(socialiteI18n.deleteCommentConfirm)) return;
                setButtonBusy(button, true);
                try {
                    const data = new FormData();
                    data.set('_method', 'DELETE');
                    const response = await fetch(deleteUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: data
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(payload?.message || socialiteI18n.commentDeleteFailed);

                    const thread = comment.closest('[data-socialite-comment-thread]');
                    const isRoot = thread?.dataset.socialiteCommentThread === String(comment.dataset.socialiteCommentId || '');
                    if (isRoot) {
                        thread.remove();
                    } else {
                        const rootId = comment.dataset.socialiteRootId;
                        comment.remove();
                        syncSocialiteRepliesCount(list, rootId);
                    }

                    if (countNode && typeof payload.comment_count !== 'undefined') {
                        countNode.textContent = formatCount(payload.comment_count || 0);
                    }
                    showSocialiteToast(socialiteI18n.commentDeleted);
                } catch (error) {
                    showSocialiteToast(error.message || socialiteI18n.commentDeleteFailed);
                } finally {
                    setButtonBusy(button, false);
                }
            };

            const bindPostInteractions = (root = document) => {
                bindSocialiteReadMore(root);

                root.querySelectorAll('[data-socialite-post-edit]:not([data-socialite-bound-post-edit])').forEach((button) => {
                    button.dataset.socialiteBoundPostEdit = '1';
                    button.addEventListener('click', () => editSocialitePost(button));
                });

                root.querySelectorAll('[data-socialite-post-delete]:not([data-socialite-bound-post-delete])').forEach((button) => {
                    button.dataset.socialiteBoundPostDelete = '1';
                    button.addEventListener('click', () => deleteSocialitePost(button));
                });

                root.querySelectorAll('[data-socialite-comment-edit]:not([data-socialite-bound-comment-edit])').forEach((button) => {
                    button.dataset.socialiteBoundCommentEdit = '1';
                    button.addEventListener('click', () => editSocialiteComment(button));
                });

                root.querySelectorAll('[data-socialite-comment-delete]:not([data-socialite-bound-comment-delete])').forEach((button) => {
                    button.dataset.socialiteBoundCommentDelete = '1';
                    button.addEventListener('click', () => deleteSocialiteComment(button));
                });

                root.querySelectorAll('[data-socialite-share-button]:not([data-socialite-bound-share])').forEach((button) => {
                    button.dataset.socialiteBoundShare = '1';
                    button.addEventListener('click', async () => {
                        const shareUrl = button.dataset.shareUrl || window.location.href;
                        const shareTitle = button.dataset.shareTitle || document.title;

                        try {
                            if (navigator.share) {
                                await navigator.share({ title: shareTitle, url: shareUrl });
                                return;
                            }
                            await navigator.clipboard.writeText(shareUrl);
                            showSocialiteToast(socialiteI18n.shareLinkCopied);
                        } catch (error) {
                            window.prompt(socialiteI18n.copyShareLink, shareUrl);
                        }
                    });
                });

                root.querySelectorAll('[data-socialite-bookmark-form]:not([data-socialite-bound-bookmark])').forEach((form) => {
                    form.dataset.socialiteBoundBookmark = '1';
                    form.addEventListener('submit', async (event) => {
                        event.preventDefault();

                        const button = form.querySelector('[data-socialite-bookmark-button]') || form.querySelector('[data-socialite-bookmark-label]');
                        const icon = button?.querySelector('ion-icon');
                        const wasBookmarked = button?.dataset.bookmarked === '1' || (icon?.getAttribute('name') === 'bookmark');

                        setButtonBusy(button, true);

                        try {
                            const response = await fetch(form.action, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'text/html,application/xhtml+xml,application/xml',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                body: new FormData(form)
                            });

                            if (!response.ok) {
                                throw new Error(socialiteI18n.postUpdateFailed);
                            }

                            const nowBookmarked = !wasBookmarked;
                            form.closest('[data-hnt-socialite-post]')?.querySelectorAll('[data-socialite-bookmark-button], [data-socialite-bookmark-label]').forEach((bookmarkButton) => {
                                const bookmarkIcon = bookmarkButton.querySelector('ion-icon');
                                bookmarkButton.dataset.bookmarked = nowBookmarked ? '1' : '0';
                                bookmarkButton.classList.toggle('text-blue-500', nowBookmarked);
                                bookmarkButton.classList.toggle('bg-blue-50', nowBookmarked);
                                if (bookmarkIcon) {
                                    bookmarkIcon.setAttribute('name', nowBookmarked ? 'bookmark' : 'bookmark-outline');
                                }
                                if (bookmarkButton.hasAttribute('data-socialite-bookmark-label')) {
                                    bookmarkButton.innerHTML = `<ion-icon class="text-xl shrink-0" name="${nowBookmarked ? 'bookmark' : 'bookmark-outline'}"></ion-icon>${nowBookmarked ? socialiteI18n.removeBookmark : socialiteI18n.addToFavorites}`;
                                }
                            });

                            showSocialiteToast(nowBookmarked ? socialiteI18n.bookmarkSaved : socialiteI18n.bookmarkRemoved);
                        } catch (error) {
                            form.submit();
                        } finally {
                            setButtonBusy(button, false);
                        }
                    });
                });

                root.querySelectorAll('[data-socialite-report-open]:not([data-socialite-bound-report-open])').forEach((button) => {
                    button.dataset.socialiteBoundReportOpen = '1';
                    button.addEventListener('click', () => openReportModal(button));
                });

                root.querySelectorAll('[data-socialite-reaction-count][data-socialite-reaction-list-url]:not([data-socialite-bound-reaction-count])').forEach((button) => {
                    button.dataset.socialiteBoundReactionCount = '1';
                    button.addEventListener('click', () => openReactionModal(button));
                });

                root.querySelectorAll('[data-socialite-reaction-form]:not([data-socialite-bound-reaction])').forEach((form) => {
                    form.dataset.socialiteBoundReaction = '1';
                    form.addEventListener('submit', async (event) => {
                        event.preventDefault();

                        const button = form.querySelector('button[type="submit"]');
                        const actions = form.closest('[data-socialite-post-actions]');
                        const countNode = actions?.querySelector('[data-socialite-reaction-count]');
                        const mainButton = actions?.querySelector('[data-socialite-reaction-main]');
                        const requestedType = form.querySelector('input[name="type"]')?.value || 'like';

                        setButtonBusy(button, true);

                        try {
                            const response = await fetch(form.action, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                body: new FormData(form)
                            });

                            if (!response.ok) {
                                throw new Error('Reaction request failed');
                            }

                            const payload = await response.json();
                            if (countNode) {
                                countNode.textContent = formatCount(payload.count || 0);
                            }

                            if (mainButton) {
                                mainButton.classList.toggle('text-red-500', !!payload.reacted);
                                mainButton.classList.toggle('bg-red-100', !!payload.reacted);
                                mainButton.innerHTML = payload.reacted && reactionEmojis[payload.type || requestedType]
                                    ? `<span class="text-base">${reactionEmojis[payload.type || requestedType]}</span>`
                                    : '<ion-icon class="text-lg" name="heart"></ion-icon>';
                            }
                        } catch (error) {
                            form.submit();
                        } finally {
                            setButtonBusy(button, false);
                        }
                    });
                });

                root.querySelectorAll('[data-socialite-poll-form]:not([data-socialite-bound-poll])').forEach((form) => {
                    form.dataset.socialiteBoundPoll = '1';
                    form.addEventListener('submit', async (event) => {
                        event.preventDefault();

                        const button = form.querySelector('[data-socialite-poll-option]');
                        const card = form.closest('[data-socialite-poll-card]');

                        setButtonBusy(button, true);

                        try {
                            const response = await fetch(form.action, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                body: new FormData(form)
                            });

                            if (!response.ok) {
                                throw new Error(socialiteI18n.pollVoteFailed);
                            }

                            updateSocialitePollCard(card, button);
                            showSocialiteToast(socialiteI18n.voteSaved);
                        } catch (error) {
                            form.submit();
                        } finally {
                            setButtonBusy(button, false);
                        }
                    });
                });

                root.querySelectorAll('[data-socialite-more-comments]:not([data-socialite-bound-more-comments])').forEach((button) => {
                    button.dataset.socialiteBoundMoreComments = '1';
                    button.addEventListener('click', async (event) => {
                        event.preventDefault();

                        const list = button.closest('[data-socialite-comments-list]');
                        const article = button.closest('[data-hnt-socialite-post]');
                        const countNode = article?.querySelector('[data-socialite-comment-count]');
                        const label = button.querySelector('[data-socialite-more-comments-label]');
                        const url = button.dataset.socialiteMoreCommentsUrl || button.href;
                        const originalLabel = label ? label.textContent : socialiteI18n.viewMoreComments;

                        if (!list || !url) return;

                        setButtonBusy(button, true);
                        if (label) label.textContent = socialiteI18n.loading;

                        try {
                            const response = await fetch(url, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                credentials: 'same-origin'
                            });

                            if (!response.ok) {
                                throw new Error(socialiteI18n.commentsRequestFailed);
                            }

                            const payload = await response.json();
                            list.innerHTML = payload.html || '';

                            if (countNode && typeof payload.count !== 'undefined') {
                                countNode.textContent = formatCount(payload.count || 0);
                            }

                            bindPostInteractions(list);

                            if (window.UIkit && typeof window.UIkit.update === 'function') {
                                window.UIkit.update(list);
                            }
                        } catch (error) {
                            console.error(error);
                            setButtonBusy(button, false);
                            if (label) label.textContent = socialiteI18n.tryAgain;
                            window.setTimeout(() => {
                                if (label) label.textContent = originalLabel;
                            }, 1800);
                        }
                    });
                });

                root.querySelectorAll('[data-socialite-replies-toggle]:not([data-socialite-bound-replies-toggle])').forEach((button) => {
                    button.dataset.socialiteBoundRepliesToggle = '1';
                    button.addEventListener('click', () => {
                        const thread = button.closest('[data-socialite-comment-thread]');
                        const isOpen = button.getAttribute('aria-expanded') === 'true';
                        setSocialiteRepliesOpen(thread, !isOpen);
                    });
                });

                root.querySelectorAll('[data-socialite-comment-reaction-form]:not([data-socialite-bound-comment-reaction])').forEach((form) => {
                    form.dataset.socialiteBoundCommentReaction = '1';
                    form.addEventListener('submit', async (event) => {
                        event.preventDefault();

                        const button = form.querySelector('[data-socialite-comment-like-button]');
                        const countNode = form.querySelector('[data-socialite-comment-like-count]');

                        if (!form.action || form.action === '#') return;

                        setButtonBusy(button, true);

                        try {
                            const response = await fetch(form.action, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                body: new FormData(form)
                            });

                            if (!response.ok) {
                                throw new Error(socialiteI18n.commentReactionFailed);
                            }

                            const payload = await response.json();
                            const count = Number.parseInt(payload.count || '0', 10) || 0;

                            button.classList.toggle('text-blue-500', !!payload.reacted);
                            button.classList.toggle('font-semibold', !!payload.reacted);
                            button.childNodes[0].nodeValue = payload.reacted ? `${socialiteI18n.liked} ` : `${socialiteI18n.like} `;

                            if (countNode) {
                                countNode.textContent = count;
                                countNode.classList.toggle('hidden', count < 1);
                            }
                        } catch (error) {
                            console.error(error);
                        } finally {
                            setButtonBusy(button, false);
                        }
                    });
                });

                root.querySelectorAll('[data-socialite-comment-reply]:not([data-socialite-bound-comment-reply])').forEach((button) => {
                    button.dataset.socialiteBoundCommentReply = '1';
                    button.addEventListener('click', () => {
                        const article = button.closest('[data-hnt-socialite-post]');
                        const form = article?.querySelector('[data-socialite-comment-form]');
                        const parentInput = form?.querySelector('[data-socialite-comment-parent]');
                        const input = form?.querySelector('[data-socialite-comment-input]');
                        const cancel = form?.querySelector('[data-socialite-comment-reply-cancel]');
                        const commentId = button.dataset.commentId || '';
                        const author = button.dataset.commentAuthor || 'comment';

                        if (parentInput) parentInput.value = commentId;
                        if (input) {
                            input.placeholder = String(socialiteI18n.replyTo || '').replace(':name', author);
                            input.focus();
                            autoSizeSocialiteCommentInput(input);
                        }
                        if (cancel) cancel.classList.remove('hidden');
                    });
                });

                root.querySelectorAll('[data-socialite-comment-reply-cancel]:not([data-socialite-bound-reply-cancel])').forEach((button) => {
                    button.dataset.socialiteBoundReplyCancel = '1';
                    button.addEventListener('click', () => clearSocialiteReplyState(button.closest('[data-socialite-comment-form]')));
                });

                root.querySelectorAll('[data-socialite-comment-form]:not([data-socialite-bound-comment])').forEach((form) => {
                    form.dataset.socialiteBoundComment = '1';
                    const commentInput = form.querySelector('[data-socialite-comment-input]');
                    if (commentInput) {
                        autoSizeSocialiteCommentInput(commentInput);
                        commentInput.addEventListener('input', () => autoSizeSocialiteCommentInput(commentInput));
                        commentInput.addEventListener('keydown', (event) => {
                            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                                event.preventDefault();
                                form.requestSubmit();
                            }
                        });
                    }
                    form.addEventListener('submit', async (event) => {
                        event.preventDefault();

                        const input = form.querySelector('[data-socialite-comment-input]');
                        const submit = form.querySelector('[data-socialite-comment-submit]');
                        const article = form.closest('[data-hnt-socialite-post]');
                        const list = article?.querySelector('[data-socialite-comments-list]');
                        const countNode = article?.querySelector('[data-socialite-comment-count]');
                        const emptyNode = list?.querySelector('[data-socialite-empty-comments]');
                        const body = input?.value.trim() || '';

                        if (!body) {
                            return;
                        }

                        setButtonBusy(submit, true);

                        try {
                            const response = await fetch(form.action, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                body: new FormData(form)
                            });

                            if (!response.ok) {
                                throw new Error(socialiteI18n.commentRequestFailed);
                            }

                            const payload = await response.json();
                            if (emptyNode) {
                                emptyNode.remove();
                            }
                            if (list && payload.comment) {
                                insertSocialiteComment(list, payload.comment);
                            }
                            if (countNode) {
                                const nextCount = Number.parseInt(countNode.textContent.replace(/\D/g, ''), 10) + 1;
                                countNode.textContent = formatCount(nextCount || 1);
                            }
                            if (input) {
                                input.value = '';
                                autoSizeSocialiteCommentInput(input);
                            }
                            clearSocialiteReplyState(form);
                            if (list) {
                                bindPostInteractions(list);
                                if (window.UIkit && typeof window.UIkit.update === 'function') {
                                    window.UIkit.update(list);
                                }
                            }
                        } catch (error) {
                            form.submit();
                        } finally {
                            setButtonBusy(submit, false);
                        }
                    });
                });
            };

            document.querySelectorAll('[data-socialite-report-close]').forEach((button) => {
                button.addEventListener('click', closeReportModal);
            });

            reportForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const submit = reportForm.querySelector('[data-socialite-report-submit]');
                setButtonBusy(submit, true);

                try {
                    const response = await fetch(reportForm.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: new FormData(reportForm)
                    });
                    const payload = await response.json();
                    if (!response.ok) {
                        throw new Error(payload?.message || socialiteI18n.reportFailed);
                    }
                    if (reportStatus) {
                        reportStatus.textContent = payload?.message || socialiteI18n.reportSent;
                        reportStatus.classList.remove('hidden');
                    }
                    window.setTimeout(closeReportModal, 1200);
                } catch (error) {
                    if (reportStatus) {
                        reportStatus.textContent = error.message || socialiteI18n.reportCouldNotBeSent;
                        reportStatus.classList.remove('hidden');
                    } else {
                        reportForm.submit();
                    }
                } finally {
                    setButtonBusy(submit, false);
                }
            });

            window.hntSocialiteBindPostInteractions = bindPostInteractions;
            bindPostInteractions(document);
        })();
    </script>

    <!-- Javascript  -->
    <script src="/assets/socialite/js/uikit.min.js"></script>
    <script src="/assets/socialite/js/simplebar.js"></script>
    <script src="/assets/socialite/js/script.js"></script>
    @auth
        <script>
            window.hntHeaderLiveBadges = {
                endpoint: @json(route('socialite.header.live-badges')),
                interval: 15000,
            };
        </script>
        <script src="/assets/socialite/js/hnt-header-live-badges.js?v=445"></script>
        <script src="/assets/socialite/js/hnt-socialite-chat-tabs.js?v=489"></script>
    @endauth
 
 
    <!-- Ion icon -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
 


    <script>
        document.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-socialite-load-more]');
            if (!button) return;

            event.preventDefault();

            const stream = document.querySelector('[data-socialite-post-stream]');
            const wrap = button.closest('[data-socialite-load-more-wrap]');
            const label = button.querySelector('[data-socialite-load-more-label]');
            const nextUrl = button.dataset.nextUrl;

            if (!stream || !nextUrl) return;

            const i18n = window.socialiteI18n || {};
            const originalLabel = label ? label.textContent : (i18n.feedLoadMorePosts || 'Mehr laden');
            const loadingLabel = button.dataset.loadingLabel || i18n.loading || 'Loading...';
            const tryAgainLabel = button.dataset.tryAgainLabel || i18n.tryAgain || 'Try again';
            button.disabled = true;
            if (label) label.textContent = loadingLabel;

            try {
                const url = new URL(nextUrl, window.location.origin);
                url.searchParams.set('fragment', '1');

                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) throw new Error('Load more failed');

                const payload = await response.json();
                const template = document.createElement('template');
                template.innerHTML = payload.html || '';
                stream.appendChild(template.content);

                if (typeof window.hntSocialiteBindPostInteractions === 'function') {
                    window.hntSocialiteBindPostInteractions(stream);
                }

                if (payload.hasMorePages && payload.nextPageUrl) {
                    button.dataset.nextUrl = payload.nextPageUrl;
                    button.disabled = false;
                    if (label) label.textContent = originalLabel;
                } else if (wrap) {
                    wrap.remove();
                }

                if (window.UIkit && typeof window.UIkit.update === 'function') {
                    window.UIkit.update(document.body);
                }
            } catch (error) {
                console.error(error);
                button.disabled = false;
                if (label) label.textContent = tryAgainLabel;
            }
        });
    </script>
