<x-public-layout>
    @php
        $formatMemberName = fn ($member) => $member
            ? collect([$member->first_name, $member->middle_name, $member->last_name])->filter()->join(' ')
            : __('messages.not_assigned');
    @endphp

    <section class="relative overflow-hidden bg-slate-950 text-white">
        <img src="{{ asset('images/tag-cicc-logo.png') }}" alt="" class="absolute right-[-5rem] top-8 hidden w-[34rem] opacity-10 lg:block">
        <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-red-700 via-yellow-400 to-red-700"></div>
        <div class="mx-auto grid min-h-[calc(100vh-5rem)] max-w-7xl items-center gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:px-8">
            <div class="relative z-10">
                <p class="text-sm font-bold uppercase tracking-[0.22em] text-yellow-300">{{ __('messages.parent_church_name') }}</p>
                <h1 class="mt-5 max-w-3xl text-4xl font-extrabold leading-tight sm:text-6xl">
                    {{ __('messages.public_hero_title') }}
                </h1>
                <p class="mt-6 max-w-2xl text-base leading-8 text-slate-200">
                    {{ __('messages.public_hero_summary') }}
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('public.calendar') }}" class="rounded-md bg-red-700 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-red-800">
                        {{ __('messages.view_calendar') }}
                    </a>
                    <a href="{{ route('public.about') }}" class="rounded-md border border-white/25 bg-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/15">
                        {{ __('messages.about_church') }}
                    </a>
                </div>
            </div>

            <div class="relative z-10 rounded-lg border border-white/10 bg-white p-6 text-slate-950 shadow-2xl">
                <div class="flex items-center gap-4">
                    <img src="{{ asset('images/tag-mother-logo.png') }}" alt="Tanzania Assemblies of God" class="h-20 w-20 object-contain">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-red-700">{{ __('messages.local_church_name') }}</p>
                        <h2 class="mt-1 text-2xl font-bold">{{ __('messages.mbwanga_dodoma') }}</h2>
                    </div>
                </div>
                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-md bg-red-50 p-4">
                        <p class="text-sm font-semibold text-red-700">{{ __('messages.members') }}</p>
                        <p class="mt-2 text-3xl font-extrabold">{{ number_format($memberCount) }}</p>
                    </div>
                    <div class="rounded-md bg-yellow-50 p-4">
                        <p class="text-sm font-semibold text-yellow-700">{{ __('messages.departments') }}</p>
                        <p class="mt-2 text-3xl font-extrabold">{{ number_format($departmentCount) }}</p>
                    </div>
                </div>
                <div class="mt-6 rounded-md border border-slate-200 p-4">
                    <p class="text-sm font-bold text-slate-500">{{ __('messages.weekly_leadership_duty') }}</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-bold uppercase text-slate-500">{{ __('messages.elder') }}</p>
                            <p class="mt-1 font-semibold">{{ $formatMemberName($weeklyDuty?->elder) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase text-slate-500">{{ __('messages.deacon') }}</p>
                            <p class="mt-1 font-semibold">{{ $formatMemberName($weeklyDuty?->deacon) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white py-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-[0.75fr_1.25fr]">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-red-700">{{ __('messages.about_church') }}</p>
                    <h2 class="mt-3 text-3xl font-extrabold text-slate-950">{{ __('messages.public_about_title') }}</h2>
                    <p class="mt-4 text-sm leading-7 text-slate-600">{{ __('messages.public_about_summary') }}</p>
                </div>
                <div class="grid gap-4 md:grid-cols-3">
                    @foreach ([
                        ['title' => __('messages.worship_and_prayer'), 'body' => __('messages.worship_and_prayer_body')],
                        ['title' => __('messages.discipleship'), 'body' => __('messages.discipleship_body')],
                        ['title' => __('messages.community_impact'), 'body' => __('messages.community_impact_body')],
                    ] as $item)
                        <article class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                            <h3 class="font-bold text-slate-950">{{ $item['title'] }}</h3>
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $item['body'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="bg-slate-50 py-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-red-700">{{ __('messages.services') }}</p>
                    <h2 class="mt-3 text-3xl font-extrabold text-slate-950">{{ __('messages.public_service_times') }}</h2>
                </div>
                <a href="{{ route('public.calendar') }}" class="text-sm font-bold text-red-700 hover:text-red-900">{{ __('messages.view_calendar') }}</a>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($serviceRoutines as $routine)
                    <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-red-700">{{ __('messages.day_'.$routine->day_of_week) }}</p>
                        <h3 class="mt-2 text-lg font-bold text-slate-950">{{ $routine->title }}</h3>
                        <p class="mt-2 text-sm text-slate-600">
                            {{ $routine->starts_at ? substr((string) $routine->starts_at, 0, 5) : '--:--' }}
                            @if ($routine->department)
                                <span class="mx-1">|</span>{{ $routine->department->name }}
                            @endif
                            @if ($routine->zone)
                                <span class="mx-1">|</span>{{ $routine->zone->name }}
                            @endif
                        </p>
                    </article>
                @empty
                    <p class="rounded-lg border border-slate-200 bg-white p-5 text-sm text-slate-600">{{ __('messages.no_services') }}</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="bg-white py-14">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:px-8">
            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.18em] text-red-700">{{ __('messages.calendar') }}</p>
                        <h2 class="mt-2 text-2xl font-extrabold text-slate-950">{{ __('messages.next_important_events') }}</h2>
                    </div>
                    <a href="{{ route('public.calendar') }}" class="rounded-md bg-red-700 px-4 py-2 text-sm font-bold text-white hover:bg-red-800">{{ __('messages.view_all') }}</a>
                </div>
                <div class="mt-6 space-y-4">
                    @forelse ($upcomingEvents as $event)
                        <div class="flex gap-4 border-t border-slate-100 pt-4 first:border-t-0 first:pt-0">
                            <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-md bg-red-50 text-red-800">
                                <span class="text-lg font-extrabold">{{ $event->event_date?->format('d') }}</span>
                                <span class="text-xs font-bold uppercase">{{ $event->event_date?->format('M') }}</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-950">{{ $event->title }}</h3>
                                <p class="mt-1 text-sm text-slate-600">
                                    {{ $event->department?->name ?: $event->zone?->name ?: __('messages.church_scope') }}
                                    @if ($event->starts_at)
                                        <span class="mx-1">|</span>{{ substr((string) $event->starts_at, 0, 5) }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-md bg-slate-50 px-4 py-3 text-sm text-slate-600">{{ __('messages.no_upcoming_important_events') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-950 p-6 text-white shadow-sm">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-yellow-300">{{ __('messages.contact') }}</p>
                <h2 class="mt-3 text-2xl font-extrabold">{{ __('messages.public_visit_us') }}</h2>
                <p class="mt-4 text-sm leading-7 text-slate-300">{{ __('messages.public_visit_us_body') }}</p>
                <div class="mt-6 rounded-md border border-white/10 bg-white/5 p-4">
                    <p class="text-sm font-bold">{{ __('messages.mbwanga_dodoma') }}</p>
                    <p class="mt-2 text-sm text-slate-300">{{ __('messages.public_contact_text') }}</p>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
