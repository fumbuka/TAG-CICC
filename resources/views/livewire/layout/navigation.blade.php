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

<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-gray-200 bg-white/90 shadow-sm backdrop-blur">
    <div class="h-1 bg-gradient-to-r from-red-700 via-yellow-400 to-red-700"></div>
    <!-- Primary Navigation Menu -->
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3">
                        <x-application-logo class="h-11 w-11" />
                        <div class="hidden leading-tight lg:block">
                            <p class="text-sm font-bold tracking-wide text-gray-950">{{ __('messages.app_name') }}</p>
                            <p class="text-[11px] font-medium uppercase tracking-wide text-red-700">City Impact</p>
                        </div>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-4 sm:-my-px sm:ms-6 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('messages.dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('members.index')" :active="request()->routeIs('members.*')" wire:navigate>
                        {{ __('messages.members') }}
                    </x-nav-link>
                    <x-nav-link :href="route('departments.index')" :active="request()->routeIs('departments.*')" wire:navigate>
                        {{ __('messages.departments') }}
                    </x-nav-link>
                    <x-nav-link :href="route('zones.index')" :active="request()->routeIs('zones.*')" wire:navigate>
                        {{ __('messages.zones') }}
                    </x-nav-link>
                    <x-nav-link :href="route('services.index')" :active="request()->routeIs('services.*')" wire:navigate>
                        {{ __('messages.services') }}
                    </x-nav-link>
                    <x-nav-link :href="route('finance.index')" :active="request()->routeIs('finance.*')" wire:navigate>
                        {{ __('messages.finance') }}
                    </x-nav-link>
                    @canany(['calendar.manage', 'calendar.submit'])
                        <x-nav-link :href="route('calendar.index')" :active="request()->routeIs('calendar.*')" wire:navigate>
                            {{ __('messages.calendar') }}
                        </x-nav-link>
                    @endcanany
                    @canany(['reports.view', 'reports.submit', 'reports.approve'])
                        <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')" wire:navigate>
                            {{ __('messages.reports') }}
                        </x-nav-link>
                    @endcanany
                    @can('leadership.manage')
                        <x-nav-link :href="route('leadership.index')" :active="request()->routeIs('leadership.*')" wire:navigate>
                            {{ __('messages.leadership') }}
                        </x-nav-link>
                    @endcan
                    @can('users.manage')
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" wire:navigate>
                            {{ __('messages.users') }}
                        </x-nav-link>
                    @endcan
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <form method="POST" action="{{ route('language.update') }}" class="me-4">
                    @csrf
                    <label for="locale" class="sr-only">{{ __('messages.language') }}</label>
                    <select id="locale" name="locale" onchange="this.form.submit()" class="rounded-md border-gray-200 bg-white text-sm shadow-sm focus:border-red-500 focus:ring-red-500">
                        <option value="sw" @selected(app()->getLocale() === 'sw')>{{ __('messages.swahili') }}</option>
                        <option value="en" @selected(app()->getLocale() === 'en')>{{ __('messages.english') }}</option>
                    </select>
                </form>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center whitespace-nowrap rounded-md border border-gray-200 bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-600 shadow-sm transition duration-150 ease-in-out hover:border-amber-300 hover:text-gray-900 focus:outline-none">
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('messages.profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('messages.logout') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-gray-500 transition duration-150 ease-in-out hover:bg-amber-50 hover:text-gray-900 focus:bg-amber-50 focus:text-gray-900 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                {{ __('messages.dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('members.index')" :active="request()->routeIs('members.*')" wire:navigate>
                {{ __('messages.members') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('departments.index')" :active="request()->routeIs('departments.*')" wire:navigate>
                {{ __('messages.departments') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('zones.index')" :active="request()->routeIs('zones.*')" wire:navigate>
                {{ __('messages.zones') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('services.index')" :active="request()->routeIs('services.*')" wire:navigate>
                {{ __('messages.services') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('finance.index')" :active="request()->routeIs('finance.*')" wire:navigate>
                {{ __('messages.finance') }}
            </x-responsive-nav-link>
            @canany(['calendar.manage', 'calendar.submit'])
                <x-responsive-nav-link :href="route('calendar.index')" :active="request()->routeIs('calendar.*')" wire:navigate>
                    {{ __('messages.calendar') }}
                </x-responsive-nav-link>
            @endcanany
            @canany(['reports.view', 'reports.submit', 'reports.approve'])
                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')" wire:navigate>
                    {{ __('messages.reports') }}
                </x-responsive-nav-link>
            @endcanany
            @can('leadership.manage')
                <x-responsive-nav-link :href="route('leadership.index')" :active="request()->routeIs('leadership.*')" wire:navigate>
                    {{ __('messages.leadership') }}
                </x-responsive-nav-link>
            @endcan
            @can('users.manage')
                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" wire:navigate>
                    {{ __('messages.users') }}
                </x-responsive-nav-link>
            @endcan
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('messages.profile') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('language.update') }}" class="px-4 py-2">
                    @csrf
                    <label for="mobile_locale" class="mb-1 block text-sm font-medium text-gray-700">{{ __('messages.language') }}</label>
                    <select id="mobile_locale" name="locale" onchange="this.form.submit()" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="sw" @selected(app()->getLocale() === 'sw')>{{ __('messages.swahili') }}</option>
                        <option value="en" @selected(app()->getLocale() === 'en')>{{ __('messages.english') }}</option>
                    </select>
                </form>

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('messages.logout') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
