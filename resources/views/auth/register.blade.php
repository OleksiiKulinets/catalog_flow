<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">{{ __('app.auth.register.title') }}</h1>
        <p class="mt-2 text-sm text-gray-500">{{ __('app.auth.register.subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-6">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('app.common.name')" />
            <x-text-input id="name" class="block mt-2 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('app.common.email')" />
            <x-text-input id="email" class="block mt-2 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('app.common.password')" />

            <x-text-input id="password" class="block mt-2 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('app.common.confirm_password')" />

            <x-text-input id="password_confirmation" class="block mt-2 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between pt-2">
            <a class="text-sm text-navy-700 hover:text-navy-900 hover:underline rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-navy-500" href="{{ route('login') }}">
                {{ __('app.auth.register.already_registered') }}
            </a>

            <x-primary-button>
                {{ __('app.auth.register.submit') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
