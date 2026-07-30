<section>
    <header>
        <h2 class="text-base font-semibold text-gray-900">
            {{ $user->hasUsablePassword() ? __('settings.password_section.title') : __('settings.password_section.set_title') }}
        </h2>

        <p class="mt-2 text-sm text-gray-500">
            {{ $user->hasUsablePassword() ? __('settings.password_section.subtitle') : __('settings.password_section.set_subtitle') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        @if ($user->hasUsablePassword())
            <div>
                <x-input-label for="update_password_current_password" :value="__('settings.password_section.current')" />
                <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-2 block w-full" autocomplete="current-password" />
                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
            </div>
        @endif

        <div>
            <x-input-label for="update_password_password" :value="__('settings.password_section.new')" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-2 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('admin.common.confirm_password')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-2 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('admin.common.save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('admin.common.saved') }}</p>
            @endif
        </div>
    </form>
</section>
