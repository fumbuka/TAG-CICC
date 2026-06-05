<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/tag-cicc-icon.png') }}">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <meta name="theme-color" content="#b91c1c">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="TAG-CICC">
        <link rel="apple-touch-icon" href="{{ asset('images/tag-cicc-logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="fixed end-4 top-4 z-10">
            <form method="POST" action="{{ route('language.update') }}">
                @csrf
                <label for="guest_locale" class="sr-only">{{ __('messages.language') }}</label>
                <select id="guest_locale" name="locale" onchange="this.form.submit()" class="rounded-md border-gray-200 bg-white/90 text-sm shadow-sm backdrop-blur focus:border-red-500 focus:ring-red-500">
                    <option value="sw" @selected(app()->getLocale() === 'sw')>{{ __('messages.swahili') }}</option>
                    <option value="en" @selected(app()->getLocale() === 'en')>{{ __('messages.english') }}</option>
                </select>
            </form>
        </div>

        <div class="flex min-h-screen items-center justify-center bg-[radial-gradient(circle_at_top_left,rgba(220,38,38,0.12),transparent_34rem),radial-gradient(circle_at_bottom_right,rgba(250,204,21,0.18),transparent_28rem),linear-gradient(135deg,#ffffff_0%,#f8fafc_55%,#f3f4f6_100%)] px-4 py-10">
            <div class="grid w-full max-w-5xl overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl lg:grid-cols-[0.9fr_1.1fr]">
                <section class="relative hidden bg-gray-950 p-10 text-white lg:block">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-red-600 via-yellow-400 to-red-600"></div>
                    <a href="/" wire:navigate class="inline-flex items-center gap-3">
                        <x-application-logo class="h-16 w-16" />
                        <span class="text-lg font-semibold tracking-wide">{{ __('messages.app_name') }}</span>
                    </a>
                    <div class="mt-14">
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-yellow-300">{{ __('messages.church_management_system') }}</p>
                        <h1 class="mt-4 text-4xl font-semibold leading-tight">{{ __('messages.city_impact_christian_centre') }}</h1>
                        <p class="mt-5 max-w-sm text-sm leading-6 text-gray-300">{{ __('messages.login_brand_statement') }}</p>
                    </div>
                    <div class="absolute bottom-10 left-10 right-10 grid grid-cols-3 gap-3 text-xs text-gray-300">
                        <div class="rounded-md border border-white/10 bg-white/5 p-3">{{ __('messages.members') }}</div>
                        <div class="rounded-md border border-white/10 bg-white/5 p-3">{{ __('messages.finance') }}</div>
                        <div class="rounded-md border border-white/10 bg-white/5 p-3">{{ __('messages.reports') }}</div>
                    </div>
                </section>

                <section class="p-6 sm:p-10">
                    <div class="mb-8 flex items-center gap-3 lg:hidden">
                        <a href="/" wire:navigate>
                            <x-application-logo class="h-14 w-14" />
                        </a>
                        <div>
                            <p class="text-lg font-semibold text-gray-950">{{ __('messages.app_name') }}</p>
                            <p class="text-sm text-gray-500">{{ __('messages.church_management_system') }}</p>
                        </div>
                    </div>

                    <div class="mx-auto w-full max-w-md">
                        {{ $slot }}
                    </div>
                </section>
            </div>
        </div>
    </body>
</html>
