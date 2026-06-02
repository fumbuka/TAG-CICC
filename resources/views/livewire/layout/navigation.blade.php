<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    $user = auth()->user();
    $can = fn (string|array $abilities): bool => collect((array) $abilities)->contains(fn (string $ability): bool => $user?->can($ability) ?? false);

    $navGroups = [
        [
            'label' => __('messages.overview'),
            'items' => [
                [
                    'label' => __('messages.dashboard'),
                    'href' => route('dashboard'),
                    'active' => request()->routeIs('dashboard'),
                    'visible' => true,
                    'icon' => 'dashboard',
                    'children' => [],
                ],
            ],
        ],
        [
            'label' => __('messages.membership'),
            'items' => [
                [
                    'label' => __('messages.members'),
                    'href' => route('members.index'),
                    'active' => request()->routeIs('members.*'),
                    'visible' => $can('members.view'),
                    'icon' => 'people',
                    'children' => [
                        ['label' => __('messages.register_member'), 'href' => route('members.index').'#register-member'],
                        ['label' => __('messages.members_list'), 'href' => route('members.index').'#members-list'],
                        ['label' => __('messages.bulk_import_members'), 'href' => route('members.index').'#bulk-import-members'],
                        ['label' => __('messages.upload_history'), 'href' => route('members.index').'#members-upload-history'],
                    ],
                ],
                [
                    'label' => __('messages.visitors'),
                    'href' => route('visitors.index'),
                    'active' => request()->routeIs('visitors.*'),
                    'visible' => $can('visitors.manage'),
                    'icon' => 'visitors',
                    'children' => [
                        ['label' => __('messages.register_visitor'), 'href' => route('visitors.index').'#visitor-form'],
                        ['label' => __('messages.visitors_list'), 'href' => route('visitors.index').'#visitors-list'],
                    ],
                ],
                [
                    'label' => __('messages.departments'),
                    'href' => route('departments.index'),
                    'active' => request()->routeIs('departments.*'),
                    'visible' => $can('departments.manage'),
                    'icon' => 'departments',
                    'children' => [
                        ['label' => __('messages.add_department'), 'href' => route('departments.index').'#department-form'],
                        ['label' => __('messages.existing_departments'), 'href' => route('departments.index').'#departments-list'],
                        ['label' => __('messages.bulk_import_departments'), 'href' => route('departments.index').'#bulk-import-departments'],
                        ['label' => __('messages.upload_history'), 'href' => route('departments.index').'#departments-upload-history'],
                    ],
                ],
                [
                    'label' => __('messages.zones'),
                    'href' => route('zones.index'),
                    'active' => request()->routeIs('zones.*'),
                    'visible' => $can('zones.manage'),
                    'icon' => 'zones',
                    'children' => [
                        ['label' => __('messages.add_zone'), 'href' => route('zones.index').'#zone-form'],
                        ['label' => __('messages.existing_zones'), 'href' => route('zones.index').'#zones-list'],
                        ['label' => __('messages.bulk_import_zones'), 'href' => route('zones.index').'#bulk-import-zones'],
                        ['label' => __('messages.upload_history'), 'href' => route('zones.index').'#zones-upload-history'],
                    ],
                ],
            ],
        ],
        [
            'label' => __('messages.operations'),
            'items' => [
                [
                    'label' => __('messages.services'),
                    'href' => route('services.index'),
                    'active' => request()->routeIs('services.*'),
                    'visible' => $can('services.manage'),
                    'icon' => 'services',
                    'children' => [
                        ['label' => __('messages.record_service'), 'href' => route('services.index').'#record-service'],
                        ['label' => __('messages.services_list'), 'href' => route('services.index').'#services-list'],
                    ],
                ],
                [
                    'label' => __('messages.calendar'),
                    'href' => route('calendar.index'),
                    'active' => request()->routeIs('calendar.*'),
                    'visible' => $can(['calendar.manage', 'calendar.submit']),
                    'icon' => 'calendar',
                    'children' => [
                        ['label' => __('messages.add_event'), 'href' => route('calendar.index').'#calendar-event-form'],
                        ['label' => __('messages.calendar_events'), 'href' => route('calendar.index').'#calendar-events-list'],
                        ['label' => __('messages.weekly_duties'), 'href' => route('calendar.index').'#weekly-duties'],
                    ],
                ],
            ],
        ],
        [
            'label' => __('messages.finance'),
            'items' => [
                [
                    'label' => __('messages.finance'),
                    'href' => route('finance.index'),
                    'active' => request()->routeIs('finance.*'),
                    'visible' => $can(['finance.view', 'finance.record']),
                    'icon' => 'finance',
                    'children' => [
                        ['label' => __('messages.income_category'), 'href' => route('finance.index').'#income-categories'],
                        ['label' => __('messages.expense_category'), 'href' => route('finance.index').'#expense-categories'],
                        ['label' => __('messages.expenses'), 'href' => route('finance.index').'#expenses'],
                        ['label' => __('messages.pledges'), 'href' => route('finance.index').'#pledges'],
                        ['label' => __('messages.transactions'), 'href' => route('finance.index').'#transactions'],
                    ],
                ],
            ],
        ],
        [
            'label' => __('messages.reports'),
            'items' => [
                [
                    'label' => __('messages.reports'),
                    'href' => route('reports.index'),
                    'active' => request()->routeIs('reports.*'),
                    'visible' => $can(['reports.view', 'reports.submit', 'reports.approve']),
                    'icon' => 'reports',
                    'children' => [
                        ['label' => __('messages.submit_event_report'), 'href' => route('reports.index').'#submit-report'],
                        ['label' => __('messages.event_reports'), 'href' => route('reports.index').'#event-reports'],
                    ],
                ],
            ],
        ],
        [
            'label' => __('messages.administration'),
            'items' => [
                [
                    'label' => __('messages.leadership'),
                    'href' => route('leadership.index'),
                    'active' => request()->routeIs('leadership.*'),
                    'visible' => $can('leadership.manage'),
                    'icon' => 'leadership',
                    'children' => [
                        ['label' => __('messages.leadership_titles'), 'href' => route('leadership.index').'#leadership-titles'],
                        ['label' => __('messages.assign_leadership'), 'href' => route('leadership.index').'#assign-leadership'],
                        ['label' => __('messages.leadership_assignments'), 'href' => route('leadership.index').'#leadership-assignments'],
                    ],
                ],
                [
                    'label' => __('messages.users'),
                    'href' => route('users.index'),
                    'active' => request()->routeIs('users.*'),
                    'visible' => $can('users.manage'),
                    'icon' => 'users',
                    'children' => [
                        ['label' => __('messages.add_user'), 'href' => route('users.index').'#grant-access'],
                        ['label' => __('messages.users_list'), 'href' => route('users.index').'#users-list'],
                    ],
                ],
            ],
        ],
    ];

    $navGroups = collect($navGroups)
        ->map(fn (array $group): array => [
            ...$group,
            'items' => collect($group['items'])->filter(fn (array $item): bool => $item['visible'])->values()->all(),
        ])
        ->filter(fn (array $group): bool => count($group['items']) > 0)
        ->values()
        ->all();
@endphp

<nav
    x-data="{ mobileOpen: false, openGroups: @js(collect($navGroups)->mapWithKeys(fn ($group, $index) => ['group'.$index => collect($group['items'])->contains('active', true)])->all()) }"
    class="relative z-50"
>
    <div class="lg:hidden">
        <div class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 shadow-sm backdrop-blur">
            <div class="h-1 bg-gradient-to-r from-red-700 via-yellow-400 to-red-700"></div>
            <div class="flex h-16 items-center justify-between px-4">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3">
                    <x-application-logo class="h-11 w-11" />
                    <div class="leading-tight">
                        <p class="text-sm font-bold tracking-wide text-gray-950">{{ __('messages.app_name') }}</p>
                        <p class="text-[11px] font-medium uppercase tracking-wide text-red-700">City Impact</p>
                    </div>
                </a>

                <button @click="mobileOpen = true" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-700 shadow-sm hover:bg-gray-50">
                    <span class="sr-only">{{ __('messages.open_menu') }}</span>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <div x-show="mobileOpen" x-cloak class="fixed inset-0 z-50">
            <div x-show="mobileOpen" x-transition.opacity @click="mobileOpen = false" class="absolute inset-0 bg-gray-950/45"></div>
            <aside x-show="mobileOpen" x-transition class="absolute inset-y-0 left-0 flex w-80 max-w-[85vw] flex-col border-r border-gray-200 bg-white shadow-xl">
                <div class="flex h-16 items-center justify-between border-b border-gray-200 px-5">
                    <div class="flex items-center gap-3">
                        <x-application-logo class="h-10 w-10" />
                        <div>
                            <p class="text-sm font-bold text-gray-950">{{ __('messages.app_name') }}</p>
                            <p class="text-xs font-medium text-red-700">TAG-CICC</p>
                        </div>
                    </div>
                    <button @click="mobileOpen = false" type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100">
                        <span class="sr-only">{{ __('messages.close_menu') }}</span>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-4 py-5">
                    @include('livewire.layout.sidebar-menu', ['navGroups' => $navGroups, 'mobile' => true])
                </div>

                @include('livewire.layout.sidebar-user', ['mobile' => true])
            </aside>
        </div>
    </div>

    <aside class="fixed inset-y-0 left-0 hidden w-72 flex-col border-r border-gray-200 bg-white/95 shadow-sm backdrop-blur lg:flex">
        <div class="h-1 bg-gradient-to-r from-red-700 via-yellow-400 to-red-700"></div>
        <div class="flex h-20 items-center border-b border-gray-200 px-6">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3">
                <x-application-logo class="h-12 w-12" />
                <div class="leading-tight">
                    <p class="text-sm font-bold tracking-wide text-gray-950">{{ __('messages.app_name') }}</p>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-red-700">City Impact</p>
                </div>
            </a>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-5">
            @include('livewire.layout.sidebar-menu', ['navGroups' => $navGroups, 'mobile' => false])
        </div>

        @include('livewire.layout.sidebar-user', ['mobile' => false])
    </aside>
</nav>
