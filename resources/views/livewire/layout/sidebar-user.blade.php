@php
    $localeId = ($mobile ?? false) ? 'mobile_sidebar_locale' : 'sidebar_locale';
@endphp

<div class="border-t border-gray-200 p-4">
    <form method="POST" action="{{ route('language.update') }}" class="mb-3">
        @csrf
        <label for="{{ $localeId }}" class="sr-only">{{ __('messages.language') }}</label>
        <select id="{{ $localeId }}" name="locale" onchange="this.form.submit()" class="block w-full rounded-md border-gray-200 bg-white text-sm shadow-sm focus:border-red-500 focus:ring-red-500">
            <option value="sw" @selected(app()->getLocale() === 'sw')>{{ __('messages.swahili') }}</option>
            <option value="en" @selected(app()->getLocale() === 'en')>{{ __('messages.english') }}</option>
        </select>
    </form>

    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('messages.signed_in_as') }}</p>
        <p class="mt-1 truncate text-sm font-semibold text-gray-950" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></p>
        <p class="truncate text-xs text-gray-500">{{ auth()->user()->email ?: auth()->user()->phone_number }}</p>

        <div class="mt-3 flex gap-2">
            <a href="{{ route('profile') }}" wire:navigate class="inline-flex flex-1 items-center justify-center rounded-md border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                {{ __('messages.profile') }}
            </a>
            <button wire:click="logout" type="button" class="inline-flex flex-1 items-center justify-center rounded-md bg-red-700 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-red-800">
                {{ __('messages.logout') }}
            </button>
        </div>
    </div>
</div>
