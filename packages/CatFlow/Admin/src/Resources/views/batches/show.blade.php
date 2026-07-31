<x-app-layout>
    <x-slot name="header">
        <div
            class="flex items-center justify-between gap-4"
            x-init="$store.batchStatus.boot(@js([
                'url' => route('batches.status', $batch['id']),
                'status' => $batch['status'],
                'done' => $batch['done'],
                'total' => $batch['total'],
                'etaHuman' => $batch['eta_human'],
                'labels' => trans('app.batches.status'),
            ]))"
        >
            <div class="flex items-center gap-3 min-w-0">
                <a
                    href="{{ route('batches.index') }}"
                    class="shrink-0 inline-flex items-center justify-center h-8 w-8 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition"
                    aria-label="{{ __('app.batches.batch.back') }}"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>

                <x-page-heading class="truncate">{{ $batch['name'] }}</x-page-heading>
            </div>

            <span
                class="!text-sm !px-3 !py-1.5 shrink-0 inline-flex items-center gap-1 rounded-full font-medium ring-1 ring-inset ring-black/5"
                :class="[$store.batchStatus.color.bg, $store.batchStatus.color.text]"
            >
                <span class="h-2 w-2 rounded-full" :class="$store.batchStatus.color.dot"></span>
                <span x-text="$store.batchStatus.label"></span>
            </span>
        </div>
    </x-slot>

    <x-container class="py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-2 space-y-6">
                <!-- Prompt -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 sm:p-8">
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('app.batches.batch.prompt_used') }}</h3>
                    <p class="mt-4 text-sm text-gray-700 leading-relaxed whitespace-pre-line">
                        {{ $batch['prompt'] }}
                    </p>
                </div>

                <!-- Results -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 sm:p-8">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">{{ __('app.batches.batch.results') }}</h3>

                        @if ($batch['status'] === 'completed')
                            <x-secondary-button type="button" class="gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 10.5L12 15m0 0l4.5-4.5M12 15V3" />
                                </svg>
                                {{ __('app.batches.batch.download') }}
                            </x-secondary-button>
                        @endif
                    </div>

                    <div class="mt-4 flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-gray-200 bg-gray-50/60 px-6 py-10 text-center">
                        @if ($batch['status'] === 'draft')
                            <p class="text-sm text-gray-500">{{ __('app.batches.batch.draft_placeholder') }}</p>
                        @else
                            <p class="text-sm text-gray-500">{{ __('app.batches.batch.results_placeholder') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Overview -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 sm:p-8">
                <h3 class="text-sm font-semibold text-gray-900">{{ __('app.batches.batch.overview') }}</h3>

                <dl class="mt-4 space-y-4">
                    <div>
                        <dt class="text-xs text-gray-500">{{ __('app.batches.batch.model') }}</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $batch['model'] }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs text-gray-500">{{ __('app.batches.batch.dataset') }}</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900 truncate">{{ $batch['name'] }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs text-gray-500">{{ __('app.batches.batch.created') }}</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $batch['time'] }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs text-gray-500">{{ __('app.batches.batch.progress') }}</dt>
                        <dd class="mt-2">
                            <div class="flex items-center gap-2">
                                <div class="h-2 flex-1 rounded-full bg-gray-200 overflow-hidden">
                                    <div
                                        class="h-full rounded-full bg-navy-800 transition-all"
                                        :style="`width: ${$store.batchStatus.progressPercent}%`"
                                        style="width: {{ $batch['total'] > 0 ? round($batch['done'] / $batch['total'] * 100) : 0 }}%"
                                    ></div>
                                </div>
                                <span class="shrink-0 text-xs text-gray-500 tabular-nums">
                                    <span x-text="$store.batchStatus.done">{{ $batch['done'] }}</span>/<span x-text="$store.batchStatus.total">{{ $batch['total'] }}</span>
                                </span>
                            </div>
                        </dd>
                    </div>

                    <div x-show="!$store.batchStatus.isTerminal" x-cloak>
                        <dt class="text-xs text-gray-500">{{ __('app.batches.batch.eta_label') }}</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            <span x-show="$store.batchStatus.etaHuman" x-text="$store.batchStatus.etaHuman"></span>
                            <span x-show="!$store.batchStatus.etaHuman">{{ __('app.batches.batch.eta_calculating') }}</span>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </x-container>
</x-app-layout>
