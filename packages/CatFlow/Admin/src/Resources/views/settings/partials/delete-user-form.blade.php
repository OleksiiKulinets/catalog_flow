<section class="space-y-6">
    <header>
        <h2 class="text-base font-semibold text-red-700">
            {{ __('settings.danger.title') }}
        </h2>

        <p class="mt-2 text-sm text-gray-500">
            {{ __('settings.danger.subtitle') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('settings.danger.title') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                {{ __('settings.danger.confirm_title') }}
            </h2>

            <p class="mt-2 text-sm text-gray-600">
                {{ __('settings.danger.confirm_subtitle') }}
            </p>

            @if ($user->hasUsablePassword())
                <div class="mt-6">
                    <x-input-label for="password" value="{{ __('admin.common.password') }}" class="sr-only" />

                    <x-text-input
                        id="password"
                        name="password"
                        type="password"
                        class="mt-2 block w-3/4"
                        placeholder="{{ __('admin.common.password') }}"
                    />

                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                </div>
            @endif

            <div class="mt-6 flex justify-end gap-4">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('admin.common.cancel') }}
                </x-secondary-button>

                <x-danger-button>
                    {{ __('settings.danger.title') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
