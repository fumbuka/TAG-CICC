<x-public-layout>
    @php
        $formatMemberName = fn ($member) => $member
            ? collect([$member->first_name, $member->middle_name, $member->last_name])->filter()->join(' ')
            : __('messages.not_assigned');
    @endphp

    <section class="bg-slate-950 py-16 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="text-sm font-bold uppercase tracking-[0.22em] text-yellow-300">{{ __('messages.weekly_duties') }}</p>
            <h1 class="mt-4 text-4xl font-extrabold">{{ __('messages.public_weekly_duty_title') }}</h1>
            <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-300">{{ __('messages.public_weekly_duty_summary') }}</p>
        </div>
    </section>

    <section class="bg-slate-50 py-12">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-red-700">{{ __('messages.weekly_leadership_duty') }}</p>
                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    <div>
                        <p class="text-xs font-bold uppercase text-slate-500">{{ __('messages.period') }}</p>
                        <p class="mt-1 text-lg font-bold text-slate-950">
                            @if ($currentDuty)
                                {{ $currentDuty->week_start?->translatedFormat('d M') }} - {{ $currentDuty->week_end?->translatedFormat('d M Y') }}
                            @else
                                {{ __('messages.not_assigned') }}
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase text-slate-500">{{ __('messages.elder') }}</p>
                        <p class="mt-1 text-lg font-bold text-slate-950">{{ $formatMemberName($currentDuty?->elder) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase text-slate-500">{{ __('messages.deacon') }}</p>
                        <p class="mt-1 text-lg font-bold text-slate-950">{{ $formatMemberName($currentDuty?->deacon) }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 p-5">
                    <h2 class="text-lg font-bold text-slate-950">{{ __('messages.upcoming_weekly_duties') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-bold uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3">{{ __('messages.period') }}</th>
                                <th class="px-5 py-3">{{ __('messages.elder') }}</th>
                                <th class="px-5 py-3">{{ __('messages.deacon') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($duties as $duty)
                                <tr>
                                    <td class="px-5 py-4 text-slate-600">{{ $duty->week_start?->translatedFormat('d M') }} - {{ $duty->week_end?->translatedFormat('d M Y') }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-950">{{ $formatMemberName($duty->elder) }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-950">{{ $formatMemberName($duty->deacon) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-8 text-center text-slate-500">{{ __('messages.no_weekly_duties') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
