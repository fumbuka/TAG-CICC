<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'TAG-CICC') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/tag-cicc-icon.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <main class="min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top_right,rgba(250,204,21,0.18),transparent_28rem),radial-gradient(circle_at_bottom_left,rgba(220,38,38,0.10),transparent_34rem),linear-gradient(180deg,#ffffff_0%,#f8fafc_58%,#f3f4f6_100%)]">
            <section class="relative mx-auto flex min-h-screen w-full max-w-6xl flex-col px-6 py-6">
                <img src="{{ asset('images/tag-cicc-logo.png') }}" alt="" class="pointer-events-none absolute -right-24 top-28 hidden w-[34rem] opacity-[0.07] lg:block">

                <header class="relative z-10 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <x-application-logo class="h-12 w-12" />
                        <div>
                            <p class="text-sm font-bold uppercase tracking-wide text-red-700">TAG-CICC</p>
                            <p class="text-sm text-slate-500">{{ __('messages.church_management_system') }}</p>
                        </div>
                    </div>

                    @if (Route::has('login'))
                        <nav class="flex items-center gap-3">
                            <form method="POST" action="{{ route('language.update') }}">
                                @csrf
                                <label for="locale" class="sr-only">{{ __('messages.language') }}</label>
                                <select id="locale" name="locale" onchange="this.form.submit()" class="rounded-md border-gray-200 bg-white text-sm shadow-sm focus:border-red-500 focus:ring-red-500">
                                    <option value="sw" @selected(app()->getLocale() === 'sw')>{{ __('messages.swahili') }}</option>
                                    <option value="en" @selected(app()->getLocale() === 'en')>{{ __('messages.english') }}</option>
                                </select>
                            </form>
                            @auth
                                <a href="{{ url('/dashboard') }}" class="rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-800">
                                    {{ __('messages.dashboard') }}
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-amber-400 hover:bg-amber-50">
                                        {{ __('messages.logout') }}
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-800">
                                    {{ __('messages.login') }}
                                </a>
                            @endauth
                        </nav>
                    @endif
                </header>

                <div class="relative z-10 flex flex-1 flex-col justify-center gap-10 py-12">
                    <div class="max-w-4xl">
                        <p class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-red-700">www.tag-cicc.or.tz</p>
                        <h1 class="max-w-3xl text-4xl font-bold leading-tight text-slate-950 sm:text-6xl">
                            {{ __('messages.welcome_title') }}
                        </h1>
                        <p class="mt-6 max-w-2xl text-base leading-7 text-slate-600">
                            {{ __('messages.welcome_summary') }}
                        </p>
                        <div class="mt-8 h-1 w-56 rounded-full bg-gradient-to-r from-red-700 via-yellow-400 to-red-700"></div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-3">
                        <div class="rounded-lg border border-slate-200 bg-white/90 p-5 shadow-sm backdrop-blur">
                            <p class="text-sm font-semibold text-red-700">{{ __('messages.phase') }} 1</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-950">{{ __('messages.membership_foundation') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('messages.member_form_help') }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white/90 p-5 shadow-sm backdrop-blur">
                            <p class="text-sm font-semibold text-red-700">{{ __('messages.phase') }} 2</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-950">{{ __('messages.finance_foundation') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('messages.finance_summary') }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white/90 p-5 shadow-sm backdrop-blur">
                            <p class="text-sm font-semibold text-red-700">{{ __('messages.phase') }} 3</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-950">{{ __('messages.calendar_foundation') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('messages.calendar_summary') }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
