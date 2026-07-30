<x-app-layout>
    <x-slot name="header">
        <x-page-heading>{{ __('app.settings.title') }}</x-page-heading>
    </x-slot>

    <x-container class="py-8">
        <div class="flex flex-col md:flex-row gap-8 lg:gap-12">
            <!-- Settings Nav -->
            <nav class="md:w-52 shrink-0">
                <ul class="flex md:flex-col gap-2 overflow-x-auto md:overflow-visible">
                    <li>
                        <a href="#profile" class="block whitespace-nowrap rounded-lg px-4 py-2 text-sm font-medium text-gray-900 bg-gray-100">
                            {{ __('app.settings.nav.profile') }}
                        </a>
                    </li>
                    <li>
                        <a href="#api-key" class="block whitespace-nowrap rounded-lg px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900">
                            {{ __('app.settings.nav.api_key') }}
                        </a>
                    </li>
                    <li>
                        <a href="#language" class="block whitespace-nowrap rounded-lg px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900">
                            {{ __('app.settings.nav.language') }}
                        </a>
                    </li>
                    <li>
                        <a href="#password" class="block whitespace-nowrap rounded-lg px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900">
                            {{ __('app.settings.nav.password') }}
                        </a>
                    </li>
                    <li>
                        <a href="#danger" class="block whitespace-nowrap rounded-lg px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                            {{ __('app.settings.nav.danger_zone') }}
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Settings Content -->
            <div class="flex-1 min-w-0 max-w-3xl space-y-6">
                <section id="profile" class="scroll-mt-20 bg-white border border-gray-200 rounded-xl shadow-sm p-6 sm:p-8">
                    @include('settings.partials.update-profile-information-form')
                </section>

                <section id="api-key" class="scroll-mt-20 bg-white border border-gray-200 rounded-xl shadow-sm p-6 sm:p-8">
                    @include('settings.partials.update-api-key-form')
                </section>

                <section id="language" class="scroll-mt-20 bg-white border border-gray-200 rounded-xl shadow-sm p-6 sm:p-8">
                    @include('settings.partials.update-language-form')
                </section>

                <section id="password" class="scroll-mt-20 bg-white border border-gray-200 rounded-xl shadow-sm p-6 sm:p-8">
                    @include('settings.partials.update-password-form')
                </section>

                <section id="danger" class="scroll-mt-20 bg-white border border-red-300 rounded-xl shadow-sm p-6 sm:p-8">
                    @include('settings.partials.delete-user-form')
                </section>
            </div>
        </div>
    </x-container>
</x-app-layout>
