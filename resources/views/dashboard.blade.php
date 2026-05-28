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
    @endphp

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('members.index') }}" wire:navigate class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow">
                    <p class="text-sm font-medium text-gray-500">{{ __('messages.members') }}</p>
                    <p class="mt-3 text-4xl font-semibold text-gray-950">{{ number_format($memberCount) }}</p>
                    <p class="mt-2 text-sm text-gray-600">{{ __('messages.total_registered_members') }}</p>
                </a>

                <a href="{{ route('departments.index') }}" wire:navigate class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow">
                    <p class="text-sm font-medium text-gray-500">{{ __('messages.departments') }}</p>
                    <p class="mt-3 text-4xl font-semibold text-gray-950">{{ number_format($departmentCount) }}</p>
                    <p class="mt-2 text-sm text-gray-600">{{ __('messages.active_departments_count') }}</p>
                </a>

                <a href="{{ route('services.index') }}" wire:navigate class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow">
                    <p class="text-sm font-medium text-gray-500">{{ __('messages.services') }}</p>
                    <p class="mt-3 text-4xl font-semibold text-gray-950">{{ number_format($serviceCount) }}</p>
                    <p class="mt-2 text-sm text-gray-600">{{ __('messages.recorded_services_count') }}</p>
                </a>

                <a href="{{ route('finance.index') }}" wire:navigate class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow">
                    <p class="text-sm font-medium text-gray-500">{{ __('messages.cash_on_hand') }}</p>
                    <p class="mt-3 text-3xl font-semibold text-gray-950">{{ __('messages.currency_tzs') }} {{ number_format((float) $cashTotal, 2) }}</p>
                    <p class="mt-2 text-sm text-gray-600">{{ __('messages.recorded_income_total') }}</p>
                </a>

                <a href="{{ route('zones.index') }}" wire:navigate class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow">
                    <p class="text-sm font-medium text-gray-500">{{ __('messages.zones') }}</p>
                    <p class="mt-3 text-4xl font-semibold text-gray-950">{{ number_format($zoneCount) }}</p>
                    <p class="mt-2 text-sm text-gray-600">{{ __('messages.active_zones_count') }}</p>
                </a>

                @can('users.manage')
                    <a href="{{ route('users.index') }}" wire:navigate class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow">
                        <p class="text-sm font-medium text-gray-500">{{ __('messages.users') }}</p>
                        <p class="mt-3 text-4xl font-semibold text-gray-950">{{ number_format($systemAccessCount) }}</p>
                        <p class="mt-2 text-sm text-gray-600">{{ __('messages.members_with_system_access') }}</p>
                    </a>
                @endcan
            </div>

            <div class="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">{{ __('messages.calendar') }}</p>
                            <h3 class="mt-2 text-2xl font-semibold text-gray-950">{{ $today->translatedFormat('d F Y') }}</h3>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                            {{ __('messages.today') }}
                        </span>
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

                @can('leadership.manage')
                    <a href="{{ route('leadership.index') }}" wire:navigate class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow">
                        <p class="text-sm font-medium text-gray-500">{{ __('messages.weekly_leadership_duty') }}</p>
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
                                    {{ $weeklyDuty->week_start?->translatedFormat('d M') }} - {{ $weeklyDuty->week_end?->translatedFormat('d M Y') }}
                                @else
                                    {{ __('messages.no_weekly_duty_assigned') }}
                                @endif
                            </p>
                        </div>
                    </a>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
