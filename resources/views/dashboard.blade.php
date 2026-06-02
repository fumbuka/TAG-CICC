<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.dashboard') }}
        </h2>
    </x-slot>

    @php
        $formatMemberName = fn ($member) => $member
            ? collect([$member->first_name, $member->middle_name, $member->last_name])->filter()->join(' ')
            : __('messages.not_assigned');

        $statCardClass = 'group rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-red-200 hover:shadow-md';
    @endphp

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="grid lg:grid-cols-[1fr_18rem]">
                    <div class="p-6 sm:p-8">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <x-application-logo class="h-20 w-20" />
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-red-700">{{ __('messages.church_management_system') }}</p>
                                <h1 class="mt-2 text-2xl font-semibold text-gray-950 sm:text-3xl">{{ __('messages.dashboard_welcome', ['name' => auth()->user()->name]) }}</h1>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600">{{ __('messages.dashboard_summary') }}</p>
                                <p class="mt-4 inline-flex rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">{{ $dashboardScopeLabel }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="border-t border-gray-200 bg-gray-950 p-6 text-white lg:border-l lg:border-t-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-yellow-300">{{ __('messages.today') }}</p>
                        <p class="mt-3 text-3xl font-semibold">{{ $today->translatedFormat('d') }}</p>
                        <p class="mt-1 text-sm text-gray-300">{{ $today->translatedFormat('F Y') }}</p>
                        <div class="mt-6 h-1 rounded-full bg-gradient-to-r from-red-600 via-yellow-300 to-red-600"></div>
                    </div>
                </div>
            </section>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @can('members.view')
                    <a href="{{ route('members.index') }}" wire:navigate class="{{ $statCardClass }}">
                @else
                    <div class="{{ $statCardClass }}">
                @endcan
                    <p class="text-sm font-medium text-red-700">{{ __('messages.members') }}</p>
                    <p class="mt-3 text-4xl font-semibold text-gray-950">{{ number_format($memberCount) }}</p>
                    <p class="mt-2 text-sm text-gray-600">{{ __('messages.total_registered_members') }}</p>
                @can('members.view')
                    </a>
                @else
                    </div>
                @endcan

                @can('departments.manage')
                    <a href="{{ route('departments.index') }}" wire:navigate class="{{ $statCardClass }}">
                @else
                    <div class="{{ $statCardClass }}">
                @endcan
                    <p class="text-sm font-medium text-red-700">{{ __('messages.departments') }}</p>
                    <p class="mt-3 text-4xl font-semibold text-gray-950">{{ number_format($departmentCount) }}</p>
                    <p class="mt-2 text-sm text-gray-600">{{ __('messages.active_departments_count') }}</p>
                @can('departments.manage')
                    </a>
                @else
                    </div>
                @endcan

                @can('services.manage')
                    <a href="{{ route('services.index') }}" wire:navigate class="{{ $statCardClass }}">
                @else
                    <div class="{{ $statCardClass }}">
                @endcan
                    <p class="text-sm font-medium text-red-700">{{ __('messages.services') }}</p>
                    <p class="mt-3 text-4xl font-semibold text-gray-950">{{ number_format($serviceCount) }}</p>
                    <p class="mt-2 text-sm text-gray-600">{{ __('messages.recorded_services_count') }}</p>
                @can('services.manage')
                    </a>
                @else
                    </div>
                @endcan

                @canany(['finance.view', 'finance.record'])
                    <a href="{{ route('finance.index') }}" wire:navigate class="{{ $statCardClass }}">
                        <p class="text-sm font-medium text-red-700">{{ __('messages.cash_on_hand') }}</p>
                        <p class="mt-3 text-3xl font-semibold text-gray-950">{{ __('messages.currency_tzs') }} {{ number_format((float) $cashTotal, 2) }}</p>
                        <p class="mt-2 text-sm text-gray-600">{{ __('messages.recorded_income_total') }}</p>
                    </a>
                @endcanany

                @can('zones.manage')
                    <a href="{{ route('zones.index') }}" wire:navigate class="{{ $statCardClass }}">
                @else
                    <div class="{{ $statCardClass }}">
                @endcan
                    <p class="text-sm font-medium text-red-700">{{ __('messages.zones') }}</p>
                    <p class="mt-3 text-4xl font-semibold text-gray-950">{{ number_format($zoneCount) }}</p>
                    <p class="mt-2 text-sm text-gray-600">{{ __('messages.active_zones_count') }}</p>
                @can('zones.manage')
                    </a>
                @else
                    </div>
                @endcan

                @can('users.manage')
                    <a href="{{ route('users.index') }}" wire:navigate class="{{ $statCardClass }}">
                        <p class="text-sm font-medium text-red-700">{{ __('messages.users') }}</p>
                        <p class="mt-3 text-4xl font-semibold text-gray-950">{{ number_format($systemAccessCount) }}</p>
                        <p class="mt-2 text-sm text-gray-600">{{ __('messages.members_with_system_access') }}</p>
                    </a>
                @endcan
            </div>

            @canany(['users.manage', 'reports.view'])
                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-sm font-medium text-red-700">{{ __('messages.site_visitors') }}</p>
                            <h3 class="mt-1 text-2xl font-semibold text-gray-950">{{ __('messages.site_visitors_overview') }}</h3>
                        </div>
                        <p class="max-w-xl text-sm text-gray-600">{{ __('messages.site_visitors_help') }}</p>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-3">
                        <div class="rounded-lg bg-gray-50 p-4">
                            <p class="text-sm font-medium text-gray-500">{{ __('messages.site_visitors_today') }}</p>
                            <p class="mt-2 text-3xl font-semibold text-gray-950">{{ number_format($siteVisitorStats['today']) }}</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4">
                            <p class="text-sm font-medium text-gray-500">{{ __('messages.site_visitors_month') }}</p>
                            <p class="mt-2 text-3xl font-semibold text-gray-950">{{ number_format($siteVisitorStats['month']) }}</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4">
                            <p class="text-sm font-medium text-gray-500">{{ __('messages.site_visitors_year') }}</p>
                            <p class="mt-2 text-3xl font-semibold text-gray-950">{{ number_format($siteVisitorStats['year']) }}</p>
                        </div>
                    </div>
                </section>
            @endcanany

            <div class="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">{{ __('messages.calendar') }}</p>
                            <h3 class="mt-2 text-2xl font-semibold text-gray-950">{{ $today->translatedFormat('d F Y') }}</h3>
                        </div>
                        @canany(['calendar.manage', 'calendar.submit'])
                            <a href="{{ route('calendar.index') }}" wire:navigate class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                                {{ __('messages.manage_calendar') }}
                            </a>
                        @else
                            <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">
                                {{ __('messages.today') }}
                            </span>
                        @endcanany
                    </div>

                    <div class="mt-5">
                        <p class="text-sm font-semibold text-gray-700">{{ __('messages.next_important_events') }}</p>
                        <div class="mt-3 space-y-3">
                            @forelse ($upcomingEvents as $event)
                                <div class="flex items-start justify-between gap-4 border-t border-gray-100 pt-3 first:border-t-0 first:pt-0">
                                    <div>
                                        <p class="font-medium text-gray-950">{{ $event->title }}</p>
                                        <p class="text-sm text-gray-500">
                                            {{ $event->event_date?->translatedFormat('d F Y') }}
                                            @if ($event->starts_at)
                                                , {{ substr((string) $event->starts_at, 0, 5) }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">
                                        {{ $event->department?->name ?: $event->zone?->name ?: __('messages.church_scope') }}
                                    </span>
                                </div>
                            @empty
                                <p class="rounded-md bg-gray-50 px-3 py-3 text-sm text-gray-600">{{ __('messages.no_upcoming_important_events') }}</p>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-red-200 hover:shadow-md">
                    <div class="flex items-start justify-between gap-4">
                        <p class="text-sm font-medium text-gray-500">{{ __('messages.weekly_leadership_duty') }}</p>
                        @can('calendar.manage')
                            <a href="{{ route('calendar.index') }}" wire:navigate class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                                {{ __('messages.calendar') }}
                            </a>
                        @endcan
                    </div>
                    <div class="mt-5 space-y-4">
                        <div>
                            <p class="text-xs font-semibold uppercase text-gray-500">{{ __('messages.elder_on_duty') }}</p>
                            <p class="mt-1 text-xl font-semibold text-gray-950">{{ $formatMemberName($weeklyDuty?->elder) }}</p>
                        </div>
                        <div class="border-t border-gray-100 pt-4">
                            <p class="text-xs font-semibold uppercase text-gray-500">{{ __('messages.deacon_on_duty') }}</p>
                            <p class="mt-1 text-xl font-semibold text-gray-950">{{ $formatMemberName($weeklyDuty?->deacon) }}</p>
                        </div>
                        <p class="text-sm text-gray-500">
                            @if ($weeklyDuty)
                                @unless ($weeklyDutyIsCurrent)
                                    <span class="font-medium text-red-700">{{ __('messages.next_weekly_duty') }}:</span>
                                @endunless
                                {{ $weeklyDuty->week_start?->translatedFormat('d M') }} - {{ $weeklyDuty->week_end?->translatedFormat('d M Y') }}
                            @else
                                {{ __('messages.no_weekly_duty_assigned') }}
                            @endif
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
