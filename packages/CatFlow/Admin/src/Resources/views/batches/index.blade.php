<x-app-layout>
    <x-slot name="header">
        <x-page-heading>{{ __('batches.batches.title') }}</x-page-heading>
    </x-slot>

    @php
        $statuses = ['queued', 'in_progress', 'completed', 'failed', 'cancelled'];
    @endphp

    <x-container class="py-8">
        <p class="text-sm text-gray-500 mb-6">{{ __('batches.batches.subtitle') }}</p>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <!-- Toolbar -->
            <form method="GET" action="{{ route('batches.index') }}" class="flex flex-wrap items-center gap-3 p-4 border-b border-gray-100 bg-gray-50/60">
                <div class="relative flex-1 min-w-[220px]">
                    <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                    </svg>
                    <x-text-input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="{{ __('batches.batches.search_placeholder') }}"
                        class="block w-full pl-9"
                    />
                </div>

                <select name="status" onchange="this.form.submit()" class="rounded-lg border-gray-300 bg-white text-sm focus:border-navy-500 focus:ring-navy-500">
                    <option value="">{{ __('batches.batches.all_statuses') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ __("batch::batch.status.$status") }}</option>
                    @endforeach
                </select>

                <select name="model" onchange="this.form.submit()" class="rounded-lg border-gray-300 bg-white text-sm focus:border-navy-500 focus:ring-navy-500">
                    <option value="">{{ __('batches.batches.all_models') }}</option>
                    @foreach ($allModels as $model)
                        <option value="{{ $model }}" @selected(($filters['model'] ?? '') === $model)>{{ $model }}</option>
                    @endforeach
                </select>

                <select name="date" onchange="this.form.submit()" class="rounded-lg border-gray-300 bg-white text-sm focus:border-navy-500 focus:ring-navy-500">
                    <option value="">{{ __('batches.batches.all_dates') }}</option>
                    <option value="today" @selected(($filters['date'] ?? '') === 'today')>{{ __('batches.batches.date_today') }}</option>
                    <option value="week" @selected(($filters['date'] ?? '') === 'week')>{{ __('batches.batches.date_week') }}</option>
                    <option value="month" @selected(($filters['date'] ?? '') === 'month')>{{ __('batches.batches.date_month') }}</option>
                </select>

                <select name="sort" onchange="this.form.submit()" class="rounded-lg border-gray-300 bg-white text-sm focus:border-navy-500 focus:ring-navy-500">
                    <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>{{ __('batches.batches.sort_newest') }}</option>
                    <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>{{ __('batches.batches.sort_oldest') }}</option>
                </select>
            </form>

            <!-- Column headers -->
            <div class="hidden sm:flex items-center gap-4 px-5 py-2.5 text-xs font-medium text-gray-500 uppercase tracking-wide border-b border-gray-100">
                <div class="flex-1 min-w-0">{{ __('batches.batches.column_name') }}</div>
                <div class="shrink-0 w-32">{{ __('batches.batches.column_status') }}</div>
                <div class="shrink-0 w-28">{{ __('batches.batches.column_model') }}</div>
                <div class="shrink-0 w-36">{{ __('batches.batches.column_progress') }}</div>
                <div class="shrink-0 w-24 text-right">{{ __('batches.batches.column_created') }}</div>
                <div class="shrink-0 w-5"></div>
            </div>

            <!-- Rows -->
            <div class="divide-y divide-gray-100">
                @forelse ($batches as $batch)
                    <a href="{{ route('batches.show', $batch['id']) }}" class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50 transition">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $batch['name'] }}</p>
                        </div>

                        <div class="shrink-0 w-32">
                            <x-status-badge :status="$batch['status']" />
                        </div>

                        <div class="shrink-0 w-28 text-sm text-gray-600 truncate">{{ $batch['model'] }}</div>

                        <div class="shrink-0 w-36 flex items-center gap-2">
                            <div class="h-2 flex-1 rounded-full bg-gray-200 overflow-hidden">
                                <div class="h-full rounded-full bg-navy-800" style="width: {{ $batch['total'] > 0 ? round($batch['done'] / $batch['total'] * 100) : 0 }}%"></div>
                            </div>
                            <span class="shrink-0 text-xs text-gray-400 tabular-nums">{{ $batch['done'] }}/{{ $batch['total'] }}</span>
                        </div>

                        <div class="shrink-0 w-24 text-sm text-gray-500 text-right">{{ $batch['time'] }}</div>

                        <div class="shrink-0 w-5 text-gray-300">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-gray-500">
                        {{ __('batches.batches.empty') }}
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if ($batches->total() > 0)
                <div class="flex items-center justify-between px-5 py-3 border-t border-gray-100 bg-gray-50/60">
                    <p class="text-xs text-gray-500">
                        {{ __('batches.batches.showing', ['from' => $batches->firstItem(), 'to' => $batches->lastItem(), 'total' => $batches->total()]) }}
                    </p>

                    <div class="flex items-center gap-2">
                        <a
                            href="{{ $batches->previousPageUrl() ?? '#' }}"
                            class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-300 bg-white text-gray-700 {{ $batches->onFirstPage() ? 'opacity-50 pointer-events-none' : 'hover:bg-gray-50' }}"
                        >
                            {{ __('batches.batches.previous') }}
                        </a>
                        <a
                            href="{{ $batches->nextPageUrl() ?? '#' }}"
                            class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-300 bg-white text-gray-700 {{ $batches->hasMorePages() ? 'hover:bg-gray-50' : 'opacity-50 pointer-events-none' }}"
                        >
                            {{ __('batches.batches.next') }}
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </x-container>
</x-app-layout>
