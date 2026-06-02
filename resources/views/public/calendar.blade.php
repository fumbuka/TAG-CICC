<x-public-layout>
    <section class="bg-slate-950 py-16 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="text-sm font-bold uppercase tracking-[0.22em] text-yellow-300">{{ __('messages.calendar') }}</p>
            <h1 class="mt-4 text-4xl font-extrabold">{{ __('messages.public_calendar_title') }}</h1>
            <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-300">{{ __('messages.public_calendar_summary') }}</p>
        </div>
    </section>

    <section class="bg-slate-50 py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($events as $event)
                    <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start gap-4">
                            <div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-md bg-red-50 text-red-800">
                                <span class="text-xl font-extrabold">{{ $event->event_date?->format('d') }}</span>
                                <span class="text-xs font-bold uppercase">{{ $event->event_date?->format('M') }}</span>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-slate-950">{{ $event->title }}</h2>
                                <p class="mt-2 text-sm text-slate-600">
                                    {{ $event->department?->name ?: $event->zone?->name ?: __('messages.church_scope') }}
                                    @if ($event->starts_at)
                                        <span class="mx-1">|</span>{{ substr((string) $event->starts_at, 0, 5) }}
                                    @endif
                                    @if ($event->ends_at)
                                        - {{ substr((string) $event->ends_at, 0, 5) }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        @if ($event->description)
                            <p class="mt-4 text-sm leading-6 text-slate-600">{{ $event->description }}</p>
                        @endif
                    </article>
                @empty
                    <div class="rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-600">
                        {{ __('messages.no_calendar_events') }}
                    </div>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $events->links() }}
            </div>
        </div>
    </section>
</x-public-layout>
