<section>
    <header>
        <h2 class="text-base font-semibold text-gray-900">
            {{ __('app.profile.language.title') }}
        </h2>

        <p class="mt-2 text-sm text-gray-500">
            {{ __('app.profile.language.subtitle') }}
        </p>
    </header>

    <form method="post" action="{{ route('locale.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="max-w-xs">
            <x-input-label for="locale" :value="__('app.profile.language.title')" />
            <select id="locale" name="locale" class="mt-2 block w-full rounded-lg border-gray-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                <option value="en" @selected(app()->getLocale() === 'en')>{{ __('app.profile.language.english') }}</option>
                <option value="uk" @selected(app()->getLocale() === 'uk')>{{ __('app.profile.language.ukrainian') }}</option>
            </select>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('app.profile.language.save') }}</x-primary-button>

            @if (session('status') === 'locale-updated')
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
</section>
