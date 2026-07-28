<section>
    <header>
        <h2 class="text-base font-semibold text-gray-900">
            {{ __('app.profile.api_key.title') }}
        </h2>

        <p class="mt-2 text-sm text-gray-500">
            {{ __('app.profile.api_key.subtitle') }}
        </p>
    </header>

    @if ($apiKey)
        <div class="mt-6 flex items-center justify-between gap-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-4">
            <div class="flex items-center gap-2">
                <span class="flex h-2 w-2 rounded-full bg-green-500"></span>
                <div>
                    <p class="text-sm font-medium text-gray-900">
                        sk-&hellip;{{ $apiKey->last_four }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ __('app.profile.api_key.connected_on', ['date' => $apiKey->created_at->format('M j, Y')]) }}
                    </p>
                </div>
            </div>

            <form method="post" action="{{ route('api-key.destroy') }}">
                @csrf
                @method('delete')

                <x-danger-button type="submit">
                    {{ __('app.profile.api_key.remove') }}
                </x-danger-button>
            </form>
        </div>

        @if (session('status') === 'api-key-deleted')
            <p class="mt-2 text-sm text-gray-600">{{ __('app.profile.api_key.removed') }}</p>
        @endif
    @else
        <form method="post" action="{{ route('api-key.store') }}" class="mt-6 space-y-6">
            @csrf

            <div class="max-w-xl">
                <x-input-label for="openai_api_key" :value="__('app.profile.api_key.label')" />
                <x-text-input id="openai_api_key" name="openai_api_key" type="password" class="mt-2 block w-full" placeholder="sk-..." autocomplete="off" required />
                <x-input-error :messages="$errors->get('openai_api_key')" class="mt-2" />
            </div>

            <div class="flex items-center gap-4">
                <x-primary-button>{{ __('app.profile.api_key.save') }}</x-primary-button>

                @if (session('status') === 'api-key-updated')
                    <p
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition
                        x-init="setTimeout(() => show = false, 2000)"
                        class="text-sm text-gray-600"
                    >{{ __('app.common.saved') }}</p>
                @endif
            </div>
        </form>
    @endif
</section>
