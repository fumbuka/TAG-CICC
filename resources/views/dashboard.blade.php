<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.dashboard') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('members.index') }}" wire:navigate class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow">
                    <p class="text-sm font-medium text-gray-500">{{ __('messages.membership') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-950">{{ __('messages.members') }}</p>
                    <p class="mt-2 text-sm text-gray-600">{{ __('messages.member_form_help') }}</p>
                </a>

                <a href="{{ route('departments.index') }}" wire:navigate class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow">
                    <p class="text-sm font-medium text-gray-500">{{ __('messages.departments') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-950">{{ __('messages.departments') }}</p>
                    <p class="mt-2 text-sm text-gray-600">{{ __('messages.department_help') }}</p>
                </a>

                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">{{ __('messages.finance') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-950">Sadaka & Zaka</p>
                    <p class="mt-2 text-sm text-gray-600">{{ __('messages.finance_summary') }}</p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">{{ __('messages.reports') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-950">{{ __('messages.calendar') }}</p>
                    <p class="mt-2 text-sm text-gray-600">{{ __('messages.reports_summary') }}</p>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('zones.index') }}" wire:navigate class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                    {{ __('messages.zones') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
