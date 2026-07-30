<x-app-layout>
    <x-slot name="header">
        <x-page-heading>{{ __('app.batches.batches.create.title') }}</x-page-heading>
    </x-slot>

    <x-container class="py-8">
        <p class="text-sm text-gray-500 mb-6">{{ __('app.batches.batches.create.subtitle') }}</p>

        <form method="POST" action="{{ route('batches.store') }}" enctype="multipart/form-data">
            @csrf

            <div
                class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-stretch"
                x-data="{ source: '{{ old('google_sheet_url') ? 'sheets' : 'file' }}', dragging: false, fileName: null }"
            >
                <!-- Data & Model -->
                <div class="lg:col-span-2 bg-white border border-gray-200 rounded-xl shadow-sm p-6 sm:p-8 space-y-6 h-full">
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('app.batches.batches.create.data_model_heading') }}</h3>

                    <div>
                        <x-input-label for="model" :value="__('app.batches.batches.create.model')" />
                        <select id="model" name="model" class="mt-2 block w-full rounded-lg border-gray-300 bg-white text-sm focus:border-navy-500 focus:ring-navy-500">
                            <option value="gpt-4.1" @selected(old('model') === 'gpt-4.1')>GPT-4.1</option>
                            <option value="gpt-4.1-mini" @selected(old('model') === 'gpt-4.1-mini')>GPT-4.1 mini</option>
                            <option value="gpt-4o" @selected(old('model') === 'gpt-4o')>GPT-4o</option>
                            <option value="gpt-4o-mini" @selected(old('model') === 'gpt-4o-mini')>GPT-4o mini</option>
                            <option value="o3" @selected(old('model') === 'o3')>o3</option>
                            <option value="o3-mini" @selected(old('model') === 'o3-mini')>o3-mini</option>
                        </select>
                        <x-input-error :messages="$errors->get('model')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="output_format" :value="__('app.batches.batches.create.output_format')" />
                        <select id="output_format" name="output_format" class="mt-2 block w-full rounded-lg border-gray-300 bg-white text-sm focus:border-navy-500 focus:ring-navy-500">
                            @foreach (['csv', 'xlsx', 'json', 'google_sheet'] as $format)
                                <option value="{{ $format }}" @selected(old('output_format') === $format)>{{ __("app.batches.batches.create.output_formats.$format") }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('output_format')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label :value="__('app.batches.batches.create.dataset')" />

                        <!-- Source tabs -->
                        <div class="mt-2 grid grid-cols-2 rounded-lg border border-gray-200 bg-gray-50 p-1">
                            <button
                                type="button"
                                @click="source = 'file'; $refs.sheetUrlInput.value = ''"
                                :class="source === 'file' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                                class="px-4 py-1.5 rounded-md text-sm font-medium transition"
                            >
                                {{ __('app.batches.batches.create.tab_upload_file') }}
                            </button>
                            <button
                                type="button"
                                @click="source = 'sheets'; $refs.fileInput.value = ''; fileName = null"
                                :class="source === 'sheets' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                                class="px-4 py-1.5 rounded-md text-sm font-medium transition"
                            >
                                {{ __('app.batches.batches.create.tab_google_sheets') }}
                            </button>
                        </div>

                        <!-- Upload file -->
                        <div x-show="source === 'file'" class="mt-4">
                            <div
                                @dragover.prevent="dragging = true"
                                @dragleave.prevent="dragging = false"
                                @drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; fileName = $event.dataTransfer.files[0]?.name"
                                @click="$refs.fileInput.click()"
                                :class="dragging ? 'border-navy-500 bg-navy-50' : 'border-navy-200 bg-navy-50/30 hover:border-navy-400 hover:bg-navy-50/60'"
                                class="flex flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed px-6 py-10 text-center cursor-pointer transition"
                            >
                                <input type="file" name="dataset" x-ref="fileInput" class="hidden" accept=".xlsx,.xls,.csv,.json" @change="fileName = $event.target.files[0]?.name">

                                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-sm">
                                    <svg class="h-6 w-6 text-navy-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 8.25L12 3.75m0 0L7.5 8.25M12 3.75v12" />
                                    </svg>
                                </span>

                                <p class="text-sm font-medium text-gray-900" x-show="fileName" x-text="fileName" x-cloak></p>
                                <p class="text-sm font-medium text-gray-700" x-show="!fileName">
                                    {{ __('app.batches.batches.create.dropzone_text') }} <span class="text-navy-700 underline">{{ __('app.batches.batches.create.browse') }}</span>
                                </p>
                                <p class="text-xs text-gray-400" x-show="!fileName">{{ __('app.batches.batches.create.dropzone_hint') }}</p>
                            </div>
                            <x-input-error :messages="$errors->get('dataset')" class="mt-2" />
                        </div>

                        <!-- Google Sheets -->
                        <div x-show="source === 'sheets'" class="mt-4" x-cloak>
                            <x-text-input
                                type="url"
                                name="google_sheet_url"
                                x-ref="sheetUrlInput"
                                class="block w-full"
                                :value="old('google_sheet_url')"
                                placeholder="{{ __('app.batches.batches.create.google_sheets_placeholder') }}"
                            />
                            <x-input-error :messages="$errors->get('google_sheet_url')" class="mt-2" />
                            <p class="mt-2 text-xs text-gray-400">{{ __('app.batches.batches.create.google_sheets_shared_hint') }}</p>

                            @if (auth()->user()->googleAccount)
                                <div class="mt-3 flex items-center gap-2 rounded-lg bg-green-50 px-3 py-2 text-xs text-green-700">
                                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    {{ __('app.batches.batches.create.google_connected', ['email' => auth()->user()->googleAccount->email]) }}
                                </div>
                            @else
                                <div class="mt-3 flex flex-col items-start gap-2 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500">
                                    <span>{{ __('app.batches.batches.create.google_not_connected') }}</span>
                                    <a href="{{ route('google.redirect') }}" class="font-medium text-navy-700 hover:text-navy-900 hover:underline">
                                        {{ __('app.batches.batches.create.google_connect_cta') }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Prompt + Send -->
                <div class="lg:col-span-3 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden flex flex-col h-full">
                    <div class="p-6 sm:p-8 flex-1 flex flex-col">
                        <h3 class="text-sm font-semibold text-gray-900">{{ __('app.batches.batches.create.prompt') }}</h3>

                        <textarea
                            id="prompt"
                            name="prompt"
                            class="mt-4 flex-1 min-h-[16rem] block w-full rounded-lg border-gray-300 bg-white text-sm placeholder-gray-400 focus:border-navy-500 focus:ring-navy-500"
                            placeholder="{{ __('app.batches.batches.create.prompt_placeholder') }}"
                        >{{ old('prompt') }}</textarea>
                        <x-input-error :messages="$errors->get('prompt')" class="mt-2" />
                    </div>

                    <div class="px-6 sm:px-8 py-5 flex items-center justify-between gap-4 bg-gray-50/60 border-t border-gray-100">
                        <p class="text-xs text-gray-500">
                            {{ __('app.batches.batches.create.uses_api_key') }}

                            @if (session('status') === 'batch-created')
                                <span class="block mt-1 font-medium text-green-600">
                                    {{ __('app.batches.batches.create.created', [
                                        'rows' => session('created_dataset.rows_count', 0),
                                        'name' => session('created_dataset.name', ''),
                                    ]) }}
                                </span>
                            @elseif (session('status') === 'google-sheets-error')
                                <span class="block mt-1 font-medium text-amber-600">
                                    {{ __('app.batches.batches.create.google_sheets_errors.'.session('google_sheets_error_reason', 'fetch_failed')) }}
                                </span>
                            @endif
                        </p>

                        <x-primary-button type="submit" class="gap-2 shrink-0">
                            {{ __('app.batches.batches.create.run_batch') }}
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                        </x-primary-button>
                    </div>
                </div>
            </div>
        </form>
    </x-container>
</x-app-layout>
