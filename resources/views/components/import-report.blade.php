@props(['report' => []])

@php
    $successfulRows = $report['successful_rows'] ?? [];
    $failedRows = $report['failed_rows'] ?? [];
@endphp

@if (! empty($report))
    <div class="mt-5 border-t border-gray-200 pt-5">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-950">{{ __('messages.import_report') }}</h3>
                <p class="text-sm text-gray-600">{{ __('messages.import_report_help') }}</p>
            </div>
        </div>

        <dl class="mt-4 grid gap-3 sm:grid-cols-3">
            <div class="rounded-md bg-gray-50 px-4 py-3">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('messages.import_total_rows') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-950">{{ $report['total_rows'] ?? 0 }}</dd>
            </div>
            <div class="rounded-md bg-emerald-50 px-4 py-3">
                <dt class="text-xs font-semibold uppercase tracking-wide text-emerald-700">{{ __('messages.import_successful_rows') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-emerald-900">{{ $report['imported_count'] ?? 0 }}</dd>
            </div>
            <div class="rounded-md bg-red-50 px-4 py-3">
                <dt class="text-xs font-semibold uppercase tracking-wide text-red-700">{{ __('messages.import_rejected_rows') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-red-900">{{ $report['rejected_count'] ?? 0 }}</dd>
            </div>
        </dl>

        @if ($failedRows !== [])
            <div class="mt-5">
                <h4 class="text-sm font-semibold text-red-800">{{ __('messages.rejected_records') }}</h4>
                <div class="mt-3 max-h-80 overflow-auto rounded-md border border-red-100">
                    <table class="min-w-full divide-y divide-red-100 text-sm">
                        <thead class="sticky top-0 bg-red-50 text-left text-xs font-semibold uppercase tracking-wide text-red-700">
                            <tr>
                                <th class="px-4 py-3">{{ __('messages.row_number') }}</th>
                                <th class="px-4 py-3">{{ __('messages.record') }}</th>
                                <th class="px-4 py-3">{{ __('messages.reason') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-red-50 bg-white">
                            @foreach ($failedRows as $row)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-950">{{ $row['row_number'] }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $row['record'] }}</td>
                                    <td class="px-4 py-3 text-gray-700">
                                        <ul class="space-y-1">
                                            @foreach ($row['reasons'] as $reason)
                                                <li>{{ $reason }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <p class="mt-4 rounded-md bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ __('messages.no_rejected_rows') }}</p>
        @endif

        @if ($successfulRows !== [])
            <div class="mt-5">
                <h4 class="text-sm font-semibold text-emerald-800">{{ __('messages.accepted_records') }}</h4>
                <div class="mt-3 max-h-80 overflow-auto rounded-md border border-emerald-100">
                    <table class="min-w-full divide-y divide-emerald-100 text-sm">
                        <thead class="sticky top-0 bg-emerald-50 text-left text-xs font-semibold uppercase tracking-wide text-emerald-700">
                            <tr>
                                <th class="px-4 py-3">{{ __('messages.row_number') }}</th>
                                <th class="px-4 py-3">{{ __('messages.record') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-emerald-50 bg-white">
                            @foreach ($successfulRows as $row)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-950">{{ $row['row_number'] }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $row['record'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endif
