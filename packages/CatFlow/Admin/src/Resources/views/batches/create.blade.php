<x-app-layout>
    <x-slot name="header">
        <x-page-heading>{{ __('batches.batches.create.title') }}</x-page-heading>
    </x-slot>

    <x-container class="py-8">
        <p class="text-sm text-gray-500 mb-6">{{ __('batches.batches.create.subtitle') }}</p>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100 overflow-hidden">
            <!-- Data & Model -->
            <div class="p-6 sm:p-8">
                <h3 class="text-sm font-semibold text-gray-900">{{ __('batches.batches.create.data_model_heading') }}</h3>

                <div class="mt-4 max-w-sm">
                    <x-input-label for="model" :value="__('batches.batches.create.model')" />
                    <select id="model" class="mt-2 block w-full rounded-lg border-gray-300 bg-white text-sm focus:border-navy-500 focus:ring-navy-500">
                        <option>GPT-4.1</option>
                        <option>GPT-4.1 mini</option>
                        <option>GPT-4o</option>
                        <option>GPT-4o mini</option>
                        <option>o3</option>
                        <option>o3-mini</option>
                    </select>
                </div>

                <div class="mt-6">
                    <x-input-label :value="__('batches.batches.create.dataset')" />

                    <div
                        x-data="{ dragging: false }"
                        @dragover.prevent="dragging = true"
                        @dragleave.prevent="dragging = false"
                        @drop.prevent="dragging = false"
                        @click="$refs.fileInput.click()"
                        :class="dragging ? 'border-navy-500 bg-navy-50' : 'border-navy-200 bg-navy-50/30 hover:border-navy-400 hover:bg-navy-50/60'"
                        class="mt-2 flex flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed px-6 py-10 text-center cursor-pointer transition"
                    >
                        <input type="file" x-ref="fileInput" class="hidden" accept=".xlsx,.xls,.csv,.json">

                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-sm">
                            <svg class="h-6 w-6 text-navy-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 8.25L12 3.75m0 0L7.5 8.25M12 3.75v12" />
                            </svg>
                        </span>

                        <p class="text-sm font-medium text-gray-700">
                            {{ __('batches.batches.create.dropzone_text') }} <span class="text-navy-700 underline">{{ __('batches.batches.create.browse') }}</span>
                        </p>
                        <p class="text-xs text-gray-400">{{ __('batches.batches.create.dropzone_hint') }}</p>
                    </div>

                    <div class="mt-4 max-w-sm flex items-center gap-4">
                        <div class="h-px flex-1 bg-gray-200"></div>
                        <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ __('batches.batches.create.or') }}</span>
                        <div class="h-px flex-1 bg-gray-200"></div>
                    </div>

                    <x-text-input type="url" class="mt-4 block w-full max-w-sm" placeholder="{{ __('batches.batches.create.google_sheets_placeholder') }}" />
                </div>
            </div>

            <!-- Prompt -->
            <div class="p-6 sm:p-8">
                <h3 class="text-sm font-semibold text-gray-900">{{ __('batches.batches.create.prompt') }}</h3>

                <textarea
                    id="prompt"
                    rows="5"
                    class="mt-4 block w-full rounded-lg border-gray-300 bg-white text-sm placeholder-gray-400 focus:border-navy-500 focus:ring-navy-500"
                    placeholder="{{ __('batches.batches.create.prompt_placeholder') }}"
                ></textarea>
            </div>

            <!-- Send -->
            <div class="px-6 sm:px-8 py-5 flex items-center justify-between bg-gray-50/60">
                <p class="text-xs text-gray-500">{{ __('batches.batches.create.uses_api_key') }}</p>

                <x-primary-button type="button" class="gap-2">
                    {{ __('batches.batches.create.run_batch') }}
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                </x-primary-button>
            </div>
        </div>
    </x-container>
</x-app-layout>
