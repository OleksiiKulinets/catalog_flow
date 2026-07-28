<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">{{ __('app.auth.login.title') }}</h1>
        <p class="mt-2 text-sm text-gray-500">{{ __('app.auth.login.subtitle') }}</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('app.common.email')" />
            <x-text-input id="email" class="block mt-2 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('app.common.password')" />

            <x-text-input id="password" class="block mt-2 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-navy-800 shadow-sm focus:ring-navy-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('app.auth.login.remember_me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-navy-700 hover:text-navy-900 hover:underline rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-navy-500" href="{{ route('password.request') }}">
                    {{ __('app.auth.login.forgot_password') }}
                </a>
            @endif
        </div>

        <x-primary-button class="w-full justify-center">
            {{ __('app.auth.login.submit') }}
        </x-primary-button>
    </form>
</x-guest-layout>
