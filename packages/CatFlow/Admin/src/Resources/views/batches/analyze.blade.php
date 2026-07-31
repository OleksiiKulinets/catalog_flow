<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
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

                <x-page-heading class="truncate">{{ __('app.batches.batches.analyze.title') }}</x-page-heading>
            </div>

            <x-status-badge :status="$batch->status" class="!text-sm !px-3 !py-1.5 shrink-0" />
        </div>
    </x-slot>

    <x-container class="py-8">
        <p class="text-sm text-gray-500 mb-6">{{ __('app.batches.batches.analyze.subtitle') }}</p>

        @if (session('created_dataset'))
            <p class="mb-6 text-sm font-medium text-green-600">
                {{ __('app.batches.batches.create.created', [
                    'rows' => session('created_dataset.rows_count', 0),
                    'name' => session('created_dataset.name', ''),
                ]) }}
            </p>
        @endif

        @if (! $schema || $schema->status === \CatFlow\Analysis\Models\DatasetSchema::STATUS_FAILED)
            @php
                $failureReason = $schema?->error_reason;
                $failureMessage = $failureReason && \Illuminate\Support\Facades\Lang::has('app.batches.batches.analyze.failed.reasons.'.$failureReason)
                    ? __('app.batches.batches.analyze.failed.reasons.'.$failureReason)
                    : __('app.batches.batches.analyze.failed.subtitle');
            @endphp

            <div class="bg-white border border-red-200 rounded-xl shadow-sm p-6 sm:p-8">
                <h3 class="text-sm font-semibold text-red-700">{{ __('app.batches.batches.analyze.failed.heading') }}</h3>
                <p class="mt-2 text-sm text-gray-600">{{ $failureMessage }}</p>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    @if ($failureReason === 'missing_api_key' || $failureReason === 'invalid_api_key')
                        <a
                            href="{{ route('profile.edit') }}"
                            class="inline-flex items-center rounded-lg bg-navy-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-navy-800 transition"
                        >
                            {{ __('app.batches.batches.analyze.failed.add_api_key') }}
                        </a>
                    @endif

                    <a
                        href="{{ route('batches.analyze', $batch) }}"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                    >
                        {{ __('app.batches.batches.analyze.failed.retry') }}
                    </a>
                </div>
            </div>
        @else
            <div x-data="{ editing: {{ $schema->needsReview() ? 'true' : 'false' }} }">
                @if ($schema->isConfirmed())
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 sm:p-8">
                        <h3 class="text-sm font-semibold text-gray-900">{{ __('app.batches.batches.analyze.preview_heading') }}</h3>

                        @if (count($preview))
                            <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach ($preview as $field => $value)
                                    <div>
                                        <dt class="text-xs text-gray-500">{{ __('app.batches.batches.analyze.fields.'.$field) }}</dt>
                                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $value === null || $value === '' ? '—' : $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @else
                            <p class="mt-4 text-sm text-gray-500">{{ __('app.batches.batches.analyze.preview_empty') }}</p>
                        @endif

                        <button
                            type="button"
                            @click="editing = !editing"
                            class="mt-6 text-sm font-medium text-navy-700 hover:text-navy-900 hover:underline"
                        >
                            {{ __('app.batches.batches.analyze.edit_toggle') }}
                        </button>
                    </div>
                @else
                    <div class="bg-white border border-amber-200 rounded-xl shadow-sm p-6 sm:p-8">
                        <h3 class="text-sm font-semibold text-amber-700">{{ __('app.batches.batches.analyze.needs_review.heading') }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ __('app.batches.batches.analyze.needs_review.subtitle') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('batches.confirm', $batch) }}" class="mt-6">
                    @csrf

                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden" x-show="editing" x-cloak>
                        <div class="divide-y divide-gray-100">
                            @foreach ($schema->columns as $column)
                                <div
                                    class="p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-4 gap-4 items-start {{ $column->confidence !== null && $column->confidence < $confidenceThreshold ? 'bg-amber-50/60' : '' }}"
                                >
                                    <div class="min-w-0">
                                        <p class="text-xs text-gray-500">{{ __('app.batches.batches.analyze.table.column') }}</p>
                                        <p class="mt-1 text-sm font-medium text-gray-900 truncate">{{ $column->source_column }}</p>
                                        @if ($column->example_value !== null)
                                            <p class="mt-1 text-xs text-gray-400 truncate">{{ $column->example_value }}</p>
                                        @endif
                                    </div>

                                    <div>
                                        <label class="text-xs text-gray-500">{{ __('app.batches.batches.analyze.table.field') }}</label>
                                        <select name="columns[{{ $column->id }}][canonical_field]" class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm focus:border-navy-500 focus:ring-navy-500">
                                            <option value="" @selected($column->canonical_field === null)>{{ __('app.batches.batches.analyze.table.ignore_option') }}</option>
                                            @foreach ($canonicalFields as $field)
                                                <option value="{{ $field }}" @selected($column->canonical_field === $field)>{{ __('app.batches.batches.analyze.fields.'.$field) }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="text-xs text-gray-500">{{ __('app.batches.batches.analyze.table.type') }}</label>
                                        <select name="columns[{{ $column->id }}][data_type]" class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm focus:border-navy-500 focus:ring-navy-500">
                                            @foreach ($dataTypes as $type)
                                                <option value="{{ $type }}" @selected($column->data_type === $type)>{{ __('app.batches.batches.analyze.data_types.'.$type) }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <p class="text-xs text-gray-500">{{ __('app.batches.batches.analyze.table.confidence') }}</p>
                                        <p class="mt-1 text-sm font-medium text-gray-900">
                                            {{ $column->confidence !== null ? round($column->confidence * 100).'%' : '—' }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-primary-button type="submit">
                            {{ __('app.batches.batches.analyze.continue') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        @endif
    </x-container>
</x-app-layout>
