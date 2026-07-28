@php
    $initials = collect(explode(' ', Auth::user()->name))
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->join('')
        ?: '?';
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-200 shadow-sm relative z-10">
    <!-- Primary Navigation Menu -->
    <x-container>
        <div class="flex justify-between h-14">
            <div class="flex items-center gap-2">
                <!-- Sections Menu -->
                <x-dropdown align="left" width="w-56">
                    <x-slot name="trigger">
                        <button type="button" class="inline-flex items-center justify-center p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150" aria-label="{{ __('app.nav.menu') }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('dashboard')" class="{{ request()->routeIs('dashboard') ? 'bg-gray-50 font-medium' : '' }}">
                            {{ __('app.nav.dashboard') }}
                        </x-dropdown-link>
                        <x-dropdown-link :href="route('batches.index')" class="{{ request()->routeIs('batches.*') ? 'bg-gray-50 font-medium' : '' }}">
                            {{ __('app.nav.batches') }}
                        </x-dropdown-link>
                    </x-slot>
                </x-dropdown>

                <!-- Brand -->
                <a href="{{ route('dashboard') }}" class="shrink-0 text-sm font-semibold text-navy-900 tracking-tight">
                    {{ config('app.name') }}
                </a>
            </div>

            <!-- User Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:gap-3">
                <!-- Command Palette Trigger -->
                <button
                    type="button"
                    @click="$store.commandPalette.open = true"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                    </svg>
                    <span class="hidden lg:inline">{{ __('app.palette.title') }}</span>
                    <span class="inline-flex items-center gap-0.5 rounded border border-gray-300 bg-white px-1.5 py-0.5 font-mono text-[10px] text-gray-400">
                        <span>Ctrl</span><span>/</span>
                    </span>
                </button>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 px-2 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition ease-in-out duration-150">
                            <span class="flex items-center justify-center h-8 w-8 rounded-full bg-navy-900 text-white text-xs font-semibold uppercase">
                                {{ $initials }}
                            </span>

                            <svg class="fill-current h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                        </div>

                        <div class="border-t border-gray-100"></div>

                        <x-dropdown-link :href="route('profile.edit')" class="{{ request()->routeIs('profile.edit') ? 'bg-gray-50 font-medium' : '' }}">
                            {{ __('app.nav.settings') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('app.nav.log_out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-lg text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </x-container>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('app.nav.dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('batches.index')" :active="request()->routeIs('batches.*')">
                {{ __('app.nav.batches') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive User Options -->
        <div class="pt-4 pb-2 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-4 space-y-2">
                <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">
                    {{ __('app.nav.settings') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('app.nav.log_out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
