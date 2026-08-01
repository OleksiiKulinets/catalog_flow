<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <x-page-heading>{{ __('app.admin.dashboard.title') }}</x-page-heading>

            <a
                href="{{ route('batches.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-navy-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-navy-800 transition"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('app.admin.dashboard.create_project') }}
            </a>
        </div>
    </x-slot>

    <x-container class="py-5 space-y-3">
        <!-- Row 1 — Overview strip (Priority 4): deliberately slim, just numbers -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm px-4 py-2.5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('app.admin.dashboard.stats.projects') }}</p>
                <p class="mt-0.5 text-xl font-bold text-gray-900">{{ $stats['projects'] }}</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm px-4 py-2.5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('app.admin.dashboard.stats.running') }}</p>
                <p class="mt-0.5 text-xl font-bold text-gray-900">{{ $stats['running'] }}</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm px-4 py-2.5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('app.admin.dashboard.stats.failed') }}</p>
                <p class="mt-0.5 text-xl font-bold {{ $stats['failed'] > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $stats['failed'] }}</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm px-4 py-2.5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('app.admin.dashboard.stats.completed_today') }}</p>
                <p class="mt-0.5 text-xl font-bold text-gray-900">{{ $stats['completedToday'] }}</p>
            </div>
        </div>

        <!-- Row 2 — Attention (Priority 1): the tallest, most prominent row -->
        <div class="grid grid-cols-1 lg:grid-cols-[2fr_1fr] gap-3 items-stretch">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('app.admin.dashboard.running.title') }}</h3>
                    <a href="{{ route('batches.index', ['status' => 'in_progress']) }}" class="text-xs font-medium text-navy-700 hover:text-navy-900 hover:underline">
                        {{ __('app.admin.dashboard.running.view_all') }}
                    </a>
                </div>

                <div class="h-52 overflow-y-auto divide-y divide-gray-100 [scrollbar-width:thin] [scrollbar-color:#e5e7eb_transparent] [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-gray-200 [&::-webkit-scrollbar-thumb]:rounded-full">
                    @forelse ($runningBatches as $batch)
                        @php
                            $progress = $batch['total'] > 0 ? round($batch['done'] / $batch['total'] * 100) : 0;
                        @endphp
                        <a href="{{ route('batches.show', $batch['id']) }}" class="flex items-center gap-3 px-4 py-2 hover:bg-gray-50 transition">
                            <p class="w-2/5 shrink-0 text-sm font-medium text-gray-900 truncate">{{ $batch['name'] }}</p>

                            <div class="flex flex-1 items-center gap-2">
                                <div class="h-1.5 flex-1 rounded-full bg-gray-200 overflow-hidden">
                                    <div class="h-full rounded-full bg-navy-800" style="width: {{ $progress }}%"></div>
                                </div>
                                <span class="shrink-0 text-xs text-gray-500 tabular-nums w-9 text-right">{{ $progress }}%</span>
                            </div>
                        </a>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-gray-500">
                            {{ __('app.admin.dashboard.running.empty') }}
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('app.admin.dashboard.failed_batches.title') }}</h3>
                    <a href="{{ route('batches.index', ['status' => 'failed']) }}" class="text-xs font-medium text-navy-700 hover:text-navy-900 hover:underline">
                        {{ __('app.admin.dashboard.failed_batches.view_all') }}
                    </a>
                </div>

                <div class="h-52 overflow-y-auto divide-y divide-gray-100 [scrollbar-width:thin] [scrollbar-color:#e5e7eb_transparent] [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-gray-200 [&::-webkit-scrollbar-thumb]:rounded-full">
                    @forelse ($failedBatches as $batch)
                        <a href="{{ route('batches.show', $batch['id']) }}" class="flex items-center gap-3 px-4 py-2 hover:bg-gray-50 transition">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $batch['name'] }}</p>
                            </div>
                            <span class="shrink-0 text-xs text-gray-500">{{ $batch['time'] }}</span>
                        </a>
                    @empty
                        <div class="h-full flex flex-col items-center justify-center gap-1.5 px-4 text-center">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-green-50 text-green-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </span>
                            <p class="text-sm text-gray-500">{{ __('app.admin.dashboard.failed_batches.empty') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Row 3 — Recent work (P2) + Analytics (P3): secondary row -->
        <div class="grid grid-cols-1 lg:grid-cols-[2fr_1fr] gap-3 items-stretch">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('app.admin.dashboard.recent.title') }}</h3>
                    <a href="{{ route('batches.index') }}" class="text-xs font-medium text-navy-700 hover:text-navy-900 hover:underline">
                        {{ __('app.admin.dashboard.recent.more') }}
                    </a>
                </div>

                <div class="h-52 overflow-y-auto divide-y divide-gray-100 [scrollbar-width:thin] [scrollbar-color:#e5e7eb_transparent] [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-gray-200 [&::-webkit-scrollbar-thumb]:rounded-full">
                    @forelse ($recentActivity as $batch)
                        <a href="{{ route('batches.show', $batch['id']) }}" class="flex items-center gap-3 px-4 py-2 hover:bg-gray-50 transition">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $batch['name'] }}</p>
                            </div>

                            <x-status-badge :status="$batch['status']" />

                            <div class="shrink-0 text-xs text-gray-500">{{ $batch['time'] }}</div>
                        </a>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-gray-500">
                            {{ __('app.admin.dashboard.recent.empty') }}
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Analytics (P3): heatmap anchored to the top, metrics anchored
                 to the bottom so content fills the stretched card evenly. -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('app.admin.dashboard.ai_activity.title') }}</h3>

                    <div class="mt-3 flex justify-between">
                        @foreach (array_chunk($activityByDay, 7) as $week)
                            <div class="flex flex-col gap-1">
                                @foreach ($week as $day)
                                    <div
                                        class="h-4 w-4 rounded-sm {{ match (true) {
                                            $day['count'] === 0 => 'bg-gray-100',
                                            $day['count'] === 1 => 'bg-navy-300',
                                            $day['count'] === 2 => 'bg-navy-500',
                                            default => 'bg-navy-700',
                                        } }}"
                                        title="{{ $day['label'] }}: {{ $day['count'] }}"
                                    ></div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-2 flex items-center justify-end gap-1.5 text-xs text-gray-400">
                        <span>{{ __('app.admin.dashboard.ai_activity.less') }}</span>
                        <span class="h-2.5 w-2.5 rounded-sm bg-gray-100"></span>
                        <span class="h-2.5 w-2.5 rounded-sm bg-navy-300"></span>
                        <span class="h-2.5 w-2.5 rounded-sm bg-navy-500"></span>
                        <span class="h-2.5 w-2.5 rounded-sm bg-navy-700"></span>
                        <span>{{ __('app.admin.dashboard.ai_activity.more') }}</span>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-gray-100 grid grid-cols-2 gap-x-3 gap-y-2.5">
                    <div>
                        <p class="text-xs text-gray-500">{{ __('app.admin.dashboard.ai_activity.total_requests') }}</p>
                        <p class="text-sm font-semibold text-gray-900 tabular-nums">{{ number_format($aiActivityStats['totalRequests']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">{{ __('app.admin.dashboard.ai_activity.success_rate') }}</p>
                        <p class="text-sm font-semibold text-gray-900 tabular-nums">{{ $aiActivityStats['successRate'] }}%</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">{{ __('app.admin.dashboard.ai_activity.batches_completed') }}</p>
                        <p class="text-sm font-semibold text-green-600 tabular-nums">{{ $aiActivityStats['batchesCompleted'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">{{ __('app.admin.dashboard.ai_activity.batches_failed') }}</p>
                        <p class="text-sm font-semibold {{ $aiActivityStats['batchesFailed'] > 0 ? 'text-red-600' : 'text-gray-900' }} tabular-nums">{{ $aiActivityStats['batchesFailed'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </x-container>
</x-app-layout>
