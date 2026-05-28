<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'TAG-CICC') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-sans text-slate-900">
        <main class="min-h-screen bg-slate-50">
            <section class="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-6 py-6">
                <header class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <x-application-logo class="h-12 w-12" />
                        <div>
                            <p class="text-sm font-medium uppercase text-emerald-700">TAG-CICC</p>
                            <p class="text-sm text-slate-500">{{ __('messages.church_management_system') }}</p>
                        </div>
                    </div>

                    @if (Route::has('login'))
                        <nav class="flex items-center gap-3">
                            <form method="POST" action="{{ route('language.update') }}">
                                @csrf
                                <label for="locale" class="sr-only">{{ __('messages.language') }}</label>
                                <select id="locale" name="locale" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="sw" @selected(app()->getLocale() === 'sw')>{{ __('messages.swahili') }}</option>
                                    <option value="en" @selected(app()->getLocale() === 'en')>{{ __('messages.english') }}</option>
                                </select>
                            </form>
                            @auth
                                <a href="{{ url('/dashboard') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">
                                    {{ __('messages.dashboard') }}
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">
                                    {{ __('messages.login') }}
                                </a>
                            @endauth
                        </nav>
                    @endif
                </header>

                <div class="grid flex-1 items-center gap-10 py-12 lg:grid-cols-[1fr_0.85fr]">
                    <div>
                        <p class="mb-4 text-sm font-semibold uppercase text-emerald-700">www.tag-cicc.or.tz</p>
                        <h1 class="max-w-3xl text-4xl font-bold leading-tight text-slate-950 sm:text-5xl">
                            {{ __('messages.welcome_title') }}
                        </h1>
                        <p class="mt-6 max-w-2xl text-base leading-7 text-slate-600">
                            {{ __('messages.welcome_summary') }}
                        </p>
                    </div>

                    <div class="grid gap-3">
                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-sm font-semibold text-slate-500">{{ __('messages.phase') }} 1</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-950">{{ __('messages.membership_foundation') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('messages.member_form_help') }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-sm font-semibold text-slate-500">{{ __('messages.phase') }} 2</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-950">{{ __('messages.finance_foundation') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('messages.finance_summary') }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-sm font-semibold text-slate-500">{{ __('messages.phase') }} 3</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-950">{{ __('messages.calendar_foundation') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('messages.calendar_summary') }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
