<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <x-page-heading>{{ __('app.dashboard.title') }}</x-page-heading>

            <a
                href="{{ route('batches.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-navy-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-navy-800 transition"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('app.dashboard.create_project') }}
            </a>
        </div>
    </x-slot>

    <x-container class="py-6 space-y-4">
        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('app.dashboard.stats.projects') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $stats['projects'] }}</p>
                <p class="mt-0.5 text-xs text-gray-400">{{ __('app.dashboard.stats.projects_subtitle') }}</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('app.dashboard.stats.running') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $stats['running'] }}</p>
                <p class="mt-0.5 text-xs text-gray-400">{{ __('app.dashboard.stats.running_subtitle') }}</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('app.dashboard.stats.completed_today') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $stats['completedToday'] }}</p>
                <p class="mt-0.5 text-xs text-gray-400">{{ __('app.dashboard.stats.completed_today_subtitle') }}</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('app.dashboard.stats.failed') }}</p>
                <p class="mt-1 text-2xl font-bold {{ $stats['failed'] > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $stats['failed'] }}</p>
                <p class="mt-0.5 text-xs text-gray-400">{{ __('app.dashboard.stats.failed_subtitle') }}</p>
            </div>
        </div>

        <!-- Activity Feed + AI Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-stretch">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('app.dashboard.activity_feed.title') }}</h3>
                </div>

                <div class="divide-y divide-gray-100 flex-1">
                    @forelse ($activityFeed as $event)
                        @php
                            $iconStyle = match (true) {
                                $event['status'] === 'completed' => ['bg' => 'bg-green-50', 'text' => 'text-green-600', 'path' => 'M4.5 12.75l6 6 9-13.5'],
                                in_array($event['status'], ['failed', 'expired', 'cancelled', 'cancelling'], true) => ['bg' => 'bg-red-50', 'text' => 'text-red-600', 'path' => 'M6 18L18 6M6 6l12 12'],
                                default => ['bg' => 'bg-navy-50', 'text' => 'text-navy-700', 'path' => 'M12 6v6l4 2'],
                            };
                        @endphp
                        <div class="flex items-center gap-3 px-5 py-2.5">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $iconStyle['bg'] }} {{ $iconStyle['text'] }}">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconStyle['path'] }}" />
                                </svg>
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-gray-900 truncate">
                                    <span class="font-medium">{{ __('app.dashboard.activity_labels.'.$event['status']) }}</span>
                                    <span class="text-gray-500">· {{ $event['name'] }}</span>
                                </p>
                            </div>

                            <span class="shrink-0 text-xs text-gray-400">{{ $event['time'] }}</span>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-gray-500">
                            {{ __('app.dashboard.activity_feed.empty') }}
                        </div>
                    @endforelse
                </div>

                <div class="px-5 py-3 border-t border-gray-100 text-center">
                    <a href="{{ route('batches.index') }}" class="text-sm font-medium text-navy-700 hover:text-navy-900 hover:underline">
                        {{ __('app.dashboard.activity_feed.view_all') }}
                    </a>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 flex flex-col">
                <h3 class="text-sm font-semibold text-gray-900">{{ __('app.dashboard.ai_activity.title') }}</h3>
                <p class="mt-0.5 text-xs text-gray-500">{{ __('app.dashboard.ai_activity.subtitle', ['weeks' => $activityWeeks]) }}</p>

                <div class="mt-4 flex flex-1 items-center gap-5">
                    <div class="flex gap-1">
                        @foreach (array_chunk($activityByDay, 7) as $week)
                            <div class="flex flex-col gap-1">
                                @foreach ($week as $day)
                                    <div
                                        class="h-2.5 w-2.5 rounded-sm {{ match (true) {
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

                    <div class="flex-1 min-w-0 space-y-2.5 border-l border-gray-100 pl-5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs text-gray-500">{{ __('app.dashboard.ai_activity.total_requests') }}</span>
                            <span class="text-sm font-semibold text-gray-900 tabular-nums">{{ number_format($aiActivityStats['totalRequests']) }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs text-gray-500">{{ __('app.dashboard.ai_activity.projects_created') }}</span>
                            <span class="text-sm font-semibold text-gray-900 tabular-nums">{{ $aiActivityStats['projectsCreated'] }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs text-gray-500">{{ __('app.dashboard.ai_activity.batches_completed') }}</span>
                            <span class="text-sm font-semibold text-green-600 tabular-nums">{{ $aiActivityStats['batchesCompleted'] }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs text-gray-500">{{ __('app.dashboard.ai_activity.batches_failed') }}</span>
                            <span class="text-sm font-semibold {{ $aiActivityStats['batchesFailed'] > 0 ? 'text-red-600' : 'text-gray-900' }} tabular-nums">{{ $aiActivityStats['batchesFailed'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-end gap-1.5 text-xs text-gray-400">
                    <span>{{ __('app.dashboard.ai_activity.less') }}</span>
                    <span class="h-2.5 w-2.5 rounded-sm bg-gray-100"></span>
                    <span class="h-2.5 w-2.5 rounded-sm bg-navy-300"></span>
                    <span class="h-2.5 w-2.5 rounded-sm bg-navy-500"></span>
                    <span class="h-2.5 w-2.5 rounded-sm bg-navy-700"></span>
                    <span>{{ __('app.dashboard.ai_activity.more') }}</span>
                </div>
            </div>
        </div>

        <!-- Running + Recent -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('app.dashboard.running.title') }}</h3>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse ($runningBatches as $batch)
                        <a href="{{ route('batches.show', $batch['id']) }}" class="block px-5 py-3 hover:bg-gray-50 transition">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $batch['name'] }}</p>
                                <span class="shrink-0 text-xs text-gray-500 tabular-nums">
                                    {{ $batch['total'] > 0 ? round($batch['done'] / $batch['total'] * 100) : 0 }}%
                                </span>
                            </div>
                            <div class="mt-2 h-1.5 rounded-full bg-gray-200 overflow-hidden">
                                <div class="h-full rounded-full bg-navy-800" style="width: {{ $batch['total'] > 0 ? round($batch['done'] / $batch['total'] * 100) : 0 }}%"></div>
                            </div>
                        </a>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-gray-500">
                            {{ __('app.dashboard.running.empty') }}
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('app.dashboard.recent.title') }}</h3>

                    <a href="{{ route('batches.index') }}" class="text-sm font-medium text-navy-700 hover:text-navy-900 hover:underline">
                        {{ __('app.dashboard.recent.more') }}
                    </a>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse ($recentBatches as $batch)
                        <a href="{{ route('batches.show', $batch['id']) }}" class="flex items-center gap-4 px-5 py-2.5 hover:bg-gray-50 transition">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $batch['name'] }}</p>
                            </div>

                            <x-status-badge :status="$batch['status']" />

                            <div class="shrink-0 text-sm text-gray-500">{{ $batch['time'] }}</div>
                        </a>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-gray-500">
                            {{ __('app.dashboard.recent.empty') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </x-container>
</x-app-layout>
