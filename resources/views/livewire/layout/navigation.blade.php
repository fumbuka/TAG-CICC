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
                    'visible' => $can(['members.view', 'members.list', 'members.create', 'members.import', 'members.relationships']),
                    'icon' => 'people',
                    'children' => [
                        ['label' => __('messages.members_list'), 'href' => route('members.index'), 'visible' => $can(['members.view', 'members.list'])],
                        ['label' => __('messages.register_member'), 'href' => route('members.index', 'create'), 'visible' => $can('members.create')],
                        ['label' => __('messages.bulk_import_members'), 'href' => route('members.index', 'import'), 'visible' => $can('members.import')],
                        ['label' => __('messages.member_relationships'), 'href' => route('members.index', 'relationships'), 'visible' => $can(['members.relationships', 'members.update'])],
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
                    'visible' => $can(['departments.manage', 'departments.list', 'departments.create', 'departments.import']),
                    'icon' => 'departments',
                    'children' => [
                        ['label' => __('messages.existing_departments'), 'href' => route('departments.index'), 'visible' => $can(['departments.manage', 'departments.list'])],
                        ['label' => __('messages.add_department'), 'href' => route('departments.index', 'create'), 'visible' => $can(['departments.manage', 'departments.create'])],
                        ['label' => __('messages.bulk_import_departments'), 'href' => route('departments.index', 'import'), 'visible' => $can(['departments.manage', 'departments.import'])],
                    ],
                ],
                [
                    'label' => __('messages.zones'),
                    'href' => route('zones.index'),
                    'active' => request()->routeIs('zones.*'),
                    'visible' => $can(['zones.manage', 'zones.list', 'zones.create', 'zones.import']),
                    'icon' => 'zones',
                    'children' => [
                        ['label' => __('messages.existing_zones'), 'href' => route('zones.index'), 'visible' => $can(['zones.manage', 'zones.list'])],
                        ['label' => __('messages.add_zone'), 'href' => route('zones.index', 'create'), 'visible' => $can(['zones.manage', 'zones.create'])],
                        ['label' => __('messages.bulk_import_zones'), 'href' => route('zones.index', 'import'), 'visible' => $can(['zones.manage', 'zones.import'])],
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
                    'visible' => $can(['services.manage', 'services.list', 'services.record']),
                    'icon' => 'services',
                    'children' => [
                        ['label' => __('messages.services_list'), 'href' => route('services.index'), 'visible' => $can(['services.manage', 'services.list'])],
                        ['label' => __('messages.record_service'), 'href' => route('services.index', 'create'), 'visible' => $can(['services.manage', 'services.record'])],
                    ],
                ],
                [
                    'label' => __('messages.calendar'),
                    'href' => route('calendar.index'),
                    'active' => request()->routeIs('calendar.*'),
                    'visible' => $can(['calendar.manage', 'calendar.submit', 'calendar.events', 'calendar.create', 'calendar.weekly-duties']),
                    'icon' => 'calendar',
                    'children' => [
                        ['label' => __('messages.calendar_events'), 'href' => route('calendar.index'), 'visible' => $can(['calendar.manage', 'calendar.submit', 'calendar.events'])],
                        ['label' => __('messages.add_event'), 'href' => route('calendar.index', 'create'), 'visible' => $can(['calendar.manage', 'calendar.submit', 'calendar.create'])],
                        ['label' => __('messages.weekly_duties'), 'href' => route('calendar.index', 'weekly-duties'), 'visible' => $can(['calendar.manage', 'calendar.weekly-duties'])],
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
                    'visible' => $can(['finance.view', 'finance.record', 'finance.summary', 'finance.income-categories', 'finance.expense-categories', 'finance.expenses', 'finance.pledges', 'finance.transactions']),
                    'icon' => 'finance',
                    'children' => [
                        ['label' => __('messages.overview'), 'href' => route('finance.index'), 'visible' => $can(['finance.view', 'finance.record', 'finance.summary'])],
                        ['label' => __('messages.income_category'), 'href' => route('finance.index', 'income-categories'), 'visible' => $can(['finance.record', 'finance.income-categories'])],
                        ['label' => __('messages.expense_category'), 'href' => route('finance.index', 'expense-categories'), 'visible' => $can(['finance.record', 'finance.expense-categories'])],
                        ['label' => __('messages.expenses'), 'href' => route('finance.index', 'expenses'), 'visible' => $can(['finance.record', 'finance.expenses'])],
                        ['label' => __('messages.pledges'), 'href' => route('finance.index', 'pledges'), 'visible' => $can(['finance.record', 'finance.pledges'])],
                        ['label' => __('messages.transactions'), 'href' => route('finance.index', 'transactions'), 'visible' => $can(['finance.view', 'finance.record', 'finance.transactions'])],
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
                    'visible' => $can(['leadership.manage', 'leadership.titles', 'leadership.assign', 'leadership.assignments']),
                    'icon' => 'leadership',
                    'children' => [
                        ['label' => __('messages.leadership_titles'), 'href' => route('leadership.index', 'titles'), 'visible' => $can(['leadership.manage', 'leadership.titles'])],
                        ['label' => __('messages.assign_leadership'), 'href' => route('leadership.index', 'assign'), 'visible' => $can(['leadership.manage', 'leadership.assign'])],
                        ['label' => __('messages.leadership_assignments'), 'href' => route('leadership.index'), 'visible' => $can(['leadership.manage', 'leadership.assignments'])],
                    ],
                ],
                [
                    'label' => __('messages.users'),
                    'href' => route('users.index'),
                    'active' => request()->routeIs('users.*'),
                    'visible' => $can(['users.manage', 'users.list', 'users.access', 'users.role-matrix']),
                    'icon' => 'users',
                    'children' => [
                        ['label' => __('messages.users_list'), 'href' => route('users.index'), 'visible' => $can(['users.manage', 'users.list'])],
                        ['label' => __('messages.add_user'), 'href' => route('users.index', 'access'), 'visible' => $can(['users.manage', 'users.access'])],
                        ['label' => __('messages.role_matrix'), 'href' => route('users.index', 'role-matrix'), 'visible' => $can('users.role-matrix')],
                    ],
                ],
            ],
        ],
    ];

    $navGroups = collect($navGroups)
        ->map(fn (array $group): array => [
            ...$group,
            'items' => collect($group['items'])
                ->filter(fn (array $item): bool => $item['visible'])
                ->map(fn (array $item): array => [
                    ...$item,
                    'children' => collect($item['children'])->filter(fn (array $child): bool => $child['visible'] ?? true)->values()->all(),
                ])
                ->values()
                ->all(),
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
