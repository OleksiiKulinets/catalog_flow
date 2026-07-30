@php
    $paletteItems = [
        ['key' => '1', 'label' => __('admin.nav.dashboard'), 'url' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
        ['key' => '2', 'label' => __('admin.nav.batches'), 'url' => route('batches.index'), 'active' => request()->routeIs('batches.*')],
        ['key' => '3', 'label' => __('admin.nav.settings'), 'url' => route('profile.edit'), 'active' => request()->routeIs('profile.edit')],
    ];
@endphp

<div
    x-data="{
        items: @js($paletteItems),
        go(url) { window.location.href = url; },
    }"
    x-init="
        window.addEventListener('keydown', (event) => {
            const meta = event.ctrlKey || event.metaKey;
            const isShortcut = meta && (event.key === '/' || event.key.toLowerCase() === 'k');

            if (isShortcut) {
                event.preventDefault();
                $store.commandPalette.open = ! $store.commandPalette.open;
                return;
            }

            if (! $store.commandPalette.open) {
                return;
            }

            const match = items.find((item) => item.key === event.key);

            if (match) {
                event.preventDefault();
                go(match.url);
            }
        }, true);
    "
    x-show="$store.commandPalette.open"
    style="display: none;"
    class="fixed inset-0 z-50 px-4 py-6 sm:px-0"
    x-on:keydown.escape.window="$store.commandPalette.open = false"
>
    <div
        x-show="$store.commandPalette.open"
        x-transition:enter="ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-500/75"
        x-on:click="$store.commandPalette.open = false"
    ></div>

    <div
        x-show="$store.commandPalette.open"
        x-transition:enter="ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative mx-auto mt-24 max-w-md bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden"
    >
        <div class="px-4 py-3 border-b border-gray-100">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('admin.palette.title') }}</p>
        </div>

        <div class="p-2">
            <template x-for="item in items" :key="item.key">
                <button
                    type="button"
                    @click="go(item.url)"
                    class="w-full flex items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-sm transition"
                    :class="item.active ? 'bg-navy-50 text-navy-900 font-medium' : 'text-gray-700 hover:bg-gray-50'"
                >
                    <span x-text="item.label"></span>
                    <span class="flex h-5 w-5 items-center justify-center rounded border border-gray-300 bg-gray-50 text-xs font-medium text-gray-500" x-text="item.key"></span>
                </button>
            </template>
        </div>

        <div class="px-4 py-2.5 border-t border-gray-100 bg-gray-50/60 flex items-center justify-between text-xs text-gray-400">
            <span>{{ __('admin.palette.hint_select') }}</span>
            <span>{{ __('admin.palette.hint_close') }}</span>
        </div>
    </div>
</div>
