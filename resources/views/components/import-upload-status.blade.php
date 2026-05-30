@props(['target' => 'import'])

<div class="mt-4 space-y-3" aria-live="polite">
    <div
        x-show="uploading"
        x-cloak
        class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
    >
        <div class="flex items-center justify-between gap-4 font-semibold">
            <span>{{ __('messages.uploading_file') }}</span>
            <span x-text="`${progress}%`">0%</span>
        </div>
        <div class="mt-2 h-2 overflow-hidden rounded-full bg-emerald-100">
            <div class="h-full rounded-full bg-emerald-600 transition-all duration-200" :style="`width: ${progress}%`"></div>
        </div>
    </div>

    <div
        wire:loading.flex
        wire:target="{{ $target }}"
        class="items-center gap-3 rounded-md border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-medium text-indigo-800"
    >
        <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        <span>{{ __('messages.processing_import') }}</span>
    </div>
</div>
