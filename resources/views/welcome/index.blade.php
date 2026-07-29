<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased text-gray-900 bg-app-grid overflow-x-hidden">
    <div class="relative min-h-screen">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-24 right-32 h-72 w-72 rounded-full bg-blue-100/40 blur-3xl"></div>
            <div class="absolute bottom-20 left-10 h-60 w-60 rounded-full bg-slate-100/50 blur-3xl"></div>
        </div>

        <header class="relative z-20">
            <x-container class="flex items-center justify-between py-7">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-navy-900 text-white font-bold">
                        CF
                    </div>

                    <div>
                        <h2 class="font-semibold tracking-tight text-navy-900">
                            {{ config('app.name') }}
                        </h2>

                        <p class="text-xs text-gray-500">
                            AI Catalog Processing
                        </p>
                    </div>
                </div>
                <nav class="flex items-center gap-3">

                    <a
                        href="{{ route('login') }}"
                        class="rounded-lg border border-transparent px-4 py-2 text-sm font-medium text-gray-600 transition hover:text-navy-900">
                        {{ __('app.welcome.nav_login') }}
                    </a>

                    <a
                        href="{{ route('register') }}"
                        class="rounded-lg bg-navy-900 px-5 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-navy-800">
                        {{ __('app.welcome.nav_register') }}
                    </a>

                </nav>
            </x-container>
        </header>

        <main class="relative z-10">
            <x-container>
                <section class="grid min-h-[78vh] items-center gap-20 py-12 lg:grid-cols-2">
                    <div class="max-w-xl">
                        <span class="inline-flex items-center rounded-full border border-gray-200 bg-white px-4 py-1 text-xs font-medium uppercase tracking-[0.2em] text-gray-500 shadow-sm">
                            {{ __('app.welcome.eyebrow') }}
                        </span>

                        <h1 class="mt-8 text-5xl font-semibold leading-tight tracking-tight text-navy-900 lg:text-6xl">
                            Process Product
                            <span class="block">
                                Catalogs with AI
                            </span>
                        </h1>

                        <p class="mt-7 text-lg leading-8 text-gray-500">
                            Upload Excel, CSV, JSON or connect your Google Sheets.
                            Configure one prompt, let AI process thousands of products
                            through OpenAI Batch API and receive a ready-to-use catalog
                            in the format you need.
                        </p>

                        <div class="mt-10 flex flex-wrap gap-4">
                            <a
                                href="{{ route('register') }}"
                                class="inline-flex items-center justify-center rounded-xl bg-navy-900 px-7 py-3 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-navy-800">
                                {{ __('app.welcome.cta_primary') }}
                            </a>

                            <a
                                href="{{ route('login') }}"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-7 py-3 text-sm font-semibold text-gray-700 transition hover:border-gray-400 hover:bg-gray-50">
                                Continue with Google
                            </a>
                        </div>

                        <div class="mt-12">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">
                                Supported formats
                            </p>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <span class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700">
                                    Excel
                                </span>

                                <span class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700">
                                    CSV
                                </span>

                                <span class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700">
                                    JSON
                                </span>

                                <span class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700">
                                    Google Sheets
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="relative flex items-center justify-center">
                        <div class="absolute h-96 w-96 rounded-full bg-blue-100/40 blur-3xl"></div>
                        <div class="relative w-full max-w-xl rounded-3xl border border-gray-200 bg-white/90 p-8 shadow-2xl backdrop-blur">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">
                                        AI Processing Flow
                                    </p>

                                    <p class="mt-1 text-sm text-gray-500">
                                        Your catalog is transformed automatically.
                                    </p>
                                </div>
                                <div class="rounded-xl bg-navy-900 px-3 py-2 text-xs font-semibold tracking-wide text-white animate-pulse">
                                    PROCESSING
                                </div>
                            </div>

                            <div class="mt-12 flex items-center justify-between">
                                <div class="flex flex-col items-center">
                                    <div class="animate-bounce rounded-2xl border border-gray-200 bg-gray-50 p-5 shadow-sm">
                                        <svg class="h-10 w-10 text-navy-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                                d="M8 3h6l5 5v13H5V3h3z" />

                                        </svg>
                                    </div>

                                    <span class="mt-4 text-sm font-medium text-gray-700">
                                        Catalog
                                    </span>
                                </div>

                                <div class="flex flex-col items-center">
                                    <div class="h-1 w-20 overflow-hidden rounded-full bg-gray-200">

                                        <div class="h-full w-1/2 animate-pulse rounded-full bg-navy-900"></div>

                                    </div>
                                    <span class="mt-4 text-xs uppercase tracking-widest text-gray-400">
                                        Upload
                                    </span>
                                </div>

                                <div class="flex flex-col items-center">
                                    <div class="relative">
                                        <div class="absolute inset-0 animate-ping rounded-full bg-blue-300 opacity-30"></div>

                                        <div class="relative flex h-24 w-24 items-center justify-center rounded-full bg-navy-900 shadow-xl">

                                            <svg class="h-11 w-11 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                    d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.364-6.364-2.121 2.121M8.757 15.243l-2.121 2.121m0-12.728 2.121 2.121m9.9 9.9 2.121 2.121" />

                                            </svg>

                                        </div>

                                    </div>

                                    <span class="mt-4 text-sm font-semibold text-navy-900">
                                        AI Engine
                                    </span>
                                </div>

                                <div class="flex flex-col items-center">
                                    <div class="h-1 w-20 overflow-hidden rounded-full bg-gray-200">

                                        <div class="h-full w-1/2 animate-pulse rounded-full bg-green-600"></div>

                                    </div>

                                    <span class="mt-4 text-xs uppercase tracking-widest text-gray-400">
                                        Export
                                    </span>
                                </div>

                                <div class="flex flex-col items-center">
                                    <div class="rounded-2xl border border-green-200 bg-green-50 p-5 shadow-sm">

                                        <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0" />

                                        </svg>

                                    </div>

                                    <span class="mt-4 text-sm font-medium text-gray-700">
                                        Updated File
                                    </span>
                                </div>
                            </div>

                            <div class="mt-14 rounded-2xl border border-gray-200 bg-gray-50 p-6">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-gray-900">
                                        Current Pipeline
                                    </span>

                                    <span class="text-xs font-medium text-green-600">
                                        Ready
                                    </span>
                                </div>

                                <div class="mt-6 space-y-4">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">
                                            Upload File
                                        </span>

                                        <span class="font-semibold text-green-600">
                                            ✓
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">
                                            AI Analysis
                                        </span>

                                        <span class="font-semibold text-green-600">
                                            ✓
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">
                                            Batch Processing
                                        </span>

                                        <span class="font-semibold text-navy-900 animate-pulse">
                                            Running...
                                        </span>
                                    </div>

                                    <div class="h-2 overflow-hidden rounded-full bg-gray-200">
                                        <div class="h-full w-2/3 animate-pulse rounded-full bg-navy-900"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="pb-24">
                    <div class="rounded-3xl border border-gray-200 bg-white p-10 shadow-sm">
                        <div class="text-center">
                            <span class="text-xs font-semibold uppercase tracking-[0.25em] text-gray-400">
                                Workflow
                            </span>

                            <h2 class="mt-4 text-3xl font-semibold tracking-tight text-navy-900">
                                From Catalog to AI in Four Simple Steps
                            </h2>

                            <p class="mt-3 text-gray-500">
                                Connect your data, configure AI once and receive an updated catalog automatically.
                            </p>
                        </div>

                        <div class="mt-14 grid gap-8 md:grid-cols-4">
                            <div class="text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-navy-900 text-xl font-bold text-white">
                                    1
                                </div>

                                <h3 class="mt-6 text-lg font-semibold text-gray-900">
                                    Upload
                                </h3>

                                <p class="mt-2 text-sm leading-6 text-gray-500">
                                    Import Excel, CSV, JSON or connect Google Sheets.
                                </p>
                            </div>

                            <div class="text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-navy-900 text-xl font-bold text-white">
                                    2
                                </div>

                                <h3 class="mt-6 text-lg font-semibold text-gray-900">
                                    Configure
                                </h3>

                                <p class="mt-2 text-sm leading-6 text-gray-500">
                                    AI detects your columns and lets you review them before processing.
                                </p>
                            </div>

                            <div class="text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-navy-900 text-xl font-bold text-white">
                                    3
                                </div>

                                <h3 class="mt-6 text-lg font-semibold text-gray-900">
                                    Process
                                </h3>

                                <p class="mt-2 text-sm leading-6 text-gray-500">
                                    OpenAI Batch processes thousands of products efficiently.
                                </p>
                            </div>

                            <div class="text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-green-600 text-xl font-bold text-white">
                                    4
                                </div>

                                <h3 class="mt-6 text-lg font-semibold text-gray-900">
                                    Export
                                </h3>

                                <p class="mt-2 text-sm leading-6 text-gray-500">
                                    Download the updated catalog or save it directly back to Google Sheets.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </x-container>
        </main>

        <footer class="border-t border-gray-200 bg-white">
            <x-container class="flex flex-col items-center justify-between gap-4 py-8 text-sm text-gray-500 md:flex-row">
                <div>
                    © {{ date('Y') }} {{ config('app.name') }}
                </div>
                <div class="flex items-center gap-6">
                    <span>Excel</span>

                    <span>CSV</span>

                    <span>JSON</span>

                    <span>Google Sheets</span>

                    <span>OpenAI Batch API</span>
                </div>
            </x-container>
        </footer>
    </div>
</body>
</html>