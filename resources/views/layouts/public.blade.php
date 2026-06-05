<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'TAG-CICC') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/tag-cicc-icon.png') }}">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <meta name="theme-color" content="#b91c1c">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="TAG-CICC">
        <link rel="apple-touch-icon" href="{{ asset('images/tag-cicc-logo.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-white font-sans text-slate-900 antialiased">
        <div class="min-h-screen">
            <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
                <div class="h-1 bg-gradient-to-r from-red-700 via-yellow-400 to-red-700"></div>
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <a href="{{ route('home') }}" class="flex items-center gap-3">
                        <x-application-logo class="h-12 w-12" />
                        <div class="leading-tight">
                            <p class="text-sm font-extrabold tracking-wide text-slate-950">{{ __('messages.app_name') }}</p>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-red-700">City Impact</p>
                        </div>
                    </a>

                    <nav class="hidden items-center gap-6 text-sm font-semibold text-slate-700 lg:flex">
                        <a href="{{ route('home') }}" class="hover:text-red-700">{{ __('messages.home') }}</a>
                        <a href="{{ route('public.about') }}" class="hover:text-red-700">{{ __('messages.about_church') }}</a>
                        <a href="{{ route('public.ministries') }}" class="hover:text-red-700">{{ __('messages.ministries') }}</a>
                        <a href="{{ route('public.calendar') }}" class="hover:text-red-700">{{ __('messages.calendar') }}</a>
                        <a href="{{ route('public.weekly-leadership') }}" class="hover:text-red-700">{{ __('messages.weekly_duties') }}</a>
                    </nav>

                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('language.update') }}">
                            @csrf
                            <label for="locale" class="sr-only">{{ __('messages.language') }}</label>
                            <select id="locale" name="locale" onchange="this.form.submit()" class="rounded-md border-slate-200 bg-white text-sm shadow-sm focus:border-red-500 focus:ring-red-500">
                                <option value="sw" @selected(app()->getLocale() === 'sw')>{{ __('messages.swahili') }}</option>
                                <option value="en" @selected(app()->getLocale() === 'en')>{{ __('messages.english') }}</option>
                            </select>
                        </form>

                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-800">
                                {{ __('messages.dashboard') }}
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-800">
                                {{ __('messages.login') }}
                            </a>
                        @endauth
                    </div>
                </div>
            </header>

            <main>
                {{ $slot }}
            </main>

            <footer class="border-t border-slate-200 bg-slate-950 text-white">
                <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-[1.2fr_0.8fr_0.8fr] lg:px-8">
                    <div>
                        <div class="flex items-center gap-3">
                            <img src="{{ asset('images/tag-cicc-logo.png') }}" alt="TAG-CICC" class="h-14 w-14 rounded-full bg-white object-contain p-1">
                            <div>
                                <p class="font-bold">{{ __('messages.city_impact_christian_centre') }}</p>
                                <p class="text-sm text-slate-300">{{ __('messages.mbwanga_dodoma') }}</p>
                            </div>
                        </div>
                        <p class="mt-4 max-w-xl text-sm leading-6 text-slate-300">{{ __('messages.public_footer_summary') }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-yellow-300">{{ __('messages.quick_links') }}</p>
                        <div class="mt-4 space-y-2 text-sm text-slate-300">
                            <a href="{{ route('public.calendar') }}" class="block hover:text-white">{{ __('messages.calendar') }}</a>
                            <a href="{{ route('public.weekly-leadership') }}" class="block hover:text-white">{{ __('messages.weekly_leadership_duty') }}</a>
                            <a href="{{ route('login') }}" class="block hover:text-white">{{ __('messages.login') }}</a>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-yellow-300">{{ __('messages.contact') }}</p>
                        <p class="mt-4 text-sm leading-6 text-slate-300">{{ __('messages.public_contact_text') }}</p>
                    </div>
                </div>
                <div class="border-t border-white/10 py-4 text-center text-xs text-slate-400">
                    &copy; {{ now()->year }} TAG-CICC. {{ __('messages.all_rights_reserved') }}
                </div>
            </footer>
        </div>
    </body>
</html>
