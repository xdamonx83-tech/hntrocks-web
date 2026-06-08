@auth
    @php
        $hhCrownsClaim = [
            'enabled' => false,
            'total' => 0,
            'count' => 0,
            'transactions' => collect(),
        ];

        try {
            $hhCrownsClaim = app(\App\Services\Economy\CrownsService::class)->pendingCollection(auth()->user(), 5);
        } catch (\Throwable $exception) {
            report($exception);
        }

        $hhCrownsClaimTotal = (int) ($hhCrownsClaim['total'] ?? 0);
        $hhCrownsClaimCount = (int) ($hhCrownsClaim['count'] ?? 0);
        $hhCrownsClaimTransactions = $hhCrownsClaim['transactions'] ?? collect();
    @endphp

    @if($hhCrownsClaimTotal > 0)
        <div class="hh-crowns-claim-modal fixed inset-0 z-[10050] flex items-center justify-center p-4" data-hh-crowns-claim-modal data-hh-crowns-dismiss-url="{{ route('crowns.dismiss') }}" data-hh-crowns-csrf="{{ csrf_token() }}" aria-modal="true" role="dialog" aria-labelledby="hh-crowns-claim-title">
            <div class="absolute inset-0 backdrop-blur-[3px]" style="background: rgba(0,0,0,.86);" data-hh-crowns-claim-close></div>

            <div class="relative w-full max-w-md overflow-hidden rounded-3xl border border-amber-400/30 text-white shadow-[0_24px_80px_rgba(0,0,0,.78)]" style="background: linear-gradient(180deg, rgba(20,16,11,.98), rgba(12,10,8,.98));">
                <div class="absolute inset-x-0 top-0 h-28 pointer-events-none" style="background: linear-gradient(180deg, rgba(245,158,11,.18), rgba(127,29,29,.16), rgba(12,10,8,0));"></div>

                <button type="button" class="absolute right-4 top-4 z-10 grid h-9 w-9 place-items-center rounded-full border border-white/15 text-white/70 hover:text-white" style="background: rgba(0,0,0,.68);" data-hh-crowns-claim-close aria-label="{{ __('ui.close') }}">
                    <i class="ph ph-x text-lg" aria-hidden="true"></i>
                </button>

                <div class="relative px-6 pb-6 pt-8 text-center">
                    <div class="mx-auto grid h-20 w-20 place-items-center rounded-full border border-amber-300/35 bg-gradient-to-br from-amber-300 via-amber-600 to-red-950 text-black shadow-[0_0_35px_rgba(245,158,11,.25)]">
                        <i class="ph ph-crown text-4xl" aria-hidden="true"></i>
                    </div>

                    <div class="mt-5 inline-flex items-center rounded-2xl bg-emerald-500 px-8 py-3 text-4xl font-black text-white shadow-[0_12px_34px_rgba(16,185,129,.25)]">
                        +{{ number_format($hhCrownsClaimTotal, 0, ',', '.') }}
                    </div>

                    <h2 id="hh-crowns-claim-title" class="mt-6 text-2xl font-black text-white">{{ __('ui.crowns_collect_modal_title') }}</h2>
                    <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-white/78">
                        {{ trans_choice('ui.crowns_collect_modal_intro', $hhCrownsClaimCount, ['count' => $hhCrownsClaimCount]) }}
                    </p>

                    @if($hhCrownsClaimTransactions->isNotEmpty())
                        <div class="mt-5 space-y-2 text-left">
                            @foreach($hhCrownsClaimTransactions as $transaction)
                                <div class="flex items-center justify-between gap-3 rounded-2xl border border-white/12 px-4 py-3" style="background: rgba(255,255,255,.075);">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-bold text-white">{{ $transaction->description ?: __('ui.crowns_collect_reward_fallback') }}</div>
                                        <div class="mt-0.5 text-xs text-white/58">{{ optional($transaction->created_at)->diffForHumans() }}</div>
                                    </div>
                                    <strong class="shrink-0 rounded-full bg-amber-400/10 px-3 py-1 text-sm text-amber-200">+{{ number_format((int) $transaction->amount, 0, ',', '.') }}</strong>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('crowns.collect') }}" class="mt-6">
                        @csrf
                        <button type="submit" class="w-full rounded-2xl bg-gradient-to-r from-amber-500 to-red-700 px-5 py-4 text-base font-black text-white shadow-[0_14px_36px_rgba(127,29,29,.35)] hover:brightness-110">
                            {{ __('ui.crowns_collect_button') }}
                        </button>
                    </form>

                    <button type="button" class="mt-4 text-sm font-bold text-amber-100/75 hover:text-amber-100" data-hh-crowns-claim-close>
                        {{ __('ui.crowns_collect_later') }}
                    </button>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modal = document.querySelector('[data-hh-crowns-claim-modal]');
                if (! modal) {
                    return;
                }

                var dismissed = false;
                var dismissUrl = modal.getAttribute('data-hh-crowns-dismiss-url');
                var csrfToken = modal.getAttribute('data-hh-crowns-csrf');

                var hide = function () {
                    modal.classList.add('hidden');
                    modal.setAttribute('aria-hidden', 'true');
                };

                var close = function () {
                    if (dismissed) {
                        hide();
                        return;
                    }

                    dismissed = true;

                    if (! dismissUrl || ! window.fetch) {
                        hide();
                        return;
                    }

                    fetch(dismissUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || '',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({})
                    }).finally(hide);
                };

                modal.querySelectorAll('[data-hh-crowns-claim-close]').forEach(function (button) {
                    button.addEventListener('click', close);
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        close();
                    }
                });
            });
        </script>
    @endif
@endauth
