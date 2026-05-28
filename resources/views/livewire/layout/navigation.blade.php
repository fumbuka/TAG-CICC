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

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
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
                    @can('leadership.manage')
                        <x-nav-link :href="route('leadership.index')" :active="request()->routeIs('leadership.*')" wire:navigate>
                            {{ __('messages.leadership') }}
                        </x-nav-link>
                    @endcan
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <form method="POST" action="{{ route('language.update') }}" class="me-4">
                    @csrf
                    <label for="locale" class="sr-only">{{ __('messages.language') }}</label>
                    <select id="locale" name="locale" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="sw" @selected(app()->getLocale() === 'sw')>{{ __('messages.swahili') }}</option>
                        <option value="en" @selected(app()->getLocale() === 'en')>{{ __('messages.english') }}</option>
                    </select>
                </form>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
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
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
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
            @can('leadership.manage')
                <x-responsive-nav-link :href="route('leadership.index')" :active="request()->routeIs('leadership.*')" wire:navigate>
                    {{ __('messages.leadership') }}
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
