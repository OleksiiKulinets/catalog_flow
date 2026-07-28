<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('batches.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    {{ __('app.batch.back') }}
                </a>
                <h2 class="mt-1 font-bold text-2xl text-gray-900 tracking-tight truncate">
                    {{ $batch['name'] }}
                </h2>
            </div>

            <x-status-badge :status="$batch['status']" class="!text-sm !px-3 !py-1.5" />
        </div>
    </x-slot>

    <x-container class="py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-2 space-y-6">
                <!-- Prompt -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 sm:p-8">
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('app.batch.prompt_used') }}</h3>
                    <p class="mt-4 text-sm text-gray-700 leading-relaxed">
                        {{ __('app.batch.prompt_example') }}
                    </p>
                </div>

                <!-- Results -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 sm:p-8">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">{{ __('app.batch.results') }}</h3>

                        @if ($batch['status'] === 'completed')
                            <x-secondary-button type="button" class="gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 10.5L12 15m0 0l4.5-4.5M12 15V3" />
                                </svg>
                                {{ __('app.batch.download') }}
                            </x-secondary-button>
                        @endif
                    </div>

                    <div class="mt-4 flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-gray-200 bg-gray-50/60 px-6 py-10 text-center">
                        <p class="text-sm text-gray-500">{{ __('app.batch.results_placeholder') }}</p>
                    </div>
                </div>
            </div>

            <!-- Overview -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 sm:p-8">
                <h3 class="text-sm font-semibold text-gray-900">{{ __('app.batch.overview') }}</h3>

                <dl class="mt-4 space-y-4">
                    <div>
                        <dt class="text-xs text-gray-500">{{ __('app.batch.model') }}</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $batch['model'] }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs text-gray-500">{{ __('app.batch.dataset') }}</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900 truncate">{{ $batch['name'] }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs text-gray-500">{{ __('app.batch.created') }}</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $batch['time'] }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs text-gray-500">{{ __('app.batch.progress') }}</dt>
                        <dd class="mt-2">
                            <div class="flex items-center gap-2">
                                <div class="h-2 flex-1 rounded-full bg-gray-200 overflow-hidden">
                                    <div class="h-full rounded-full bg-navy-800" style="width: {{ $batch['total'] > 0 ? round($batch['done'] / $batch['total'] * 100) : 0 }}%"></div>
                                </div>
                                <span class="shrink-0 text-xs text-gray-500 tabular-nums">{{ $batch['done'] }}/{{ $batch['total'] }}</span>
                            </div>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </x-container>
</x-app-layout>
