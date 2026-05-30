<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div>
            <p class="text-sm font-medium text-emerald-700">{{ __('messages.reports') }}</p>
            <h1 class="text-2xl font-semibold text-gray-950">{{ __('messages.reports') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('messages.reports_help') }}</p>
        </div>

        <div x-data="{ show: false, message: '' }"
            x-on:report-created.window="message = '{{ __('messages.report_created') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:report-updated.window="message = '{{ __('messages.report_updated') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:report-deleted.window="message = '{{ __('messages.report_deleted') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:report-approved.window="message = '{{ __('messages.report_approved') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-on:report-returned.window="message = '{{ __('messages.report_returned') }}'; show = true; setTimeout(() => show = false, 3500)"
            x-show="show"
            x-cloak
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            x-text="message"></div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ __('messages.planned_department_events') }}</p>
                <p class="mt-3 text-4xl font-semibold text-gray-950">{{ number_format($plannedEventsCount) }}</p>
                <p class="mt-2 text-sm text-gray-600">{{ __('messages.from_calendar') }}</p>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ __('messages.submitted_reports') }}</p>
                <p class="mt-3 text-4xl font-semibold text-gray-950">{{ number_format($submittedReportsCount) }}</p>
                <p class="mt-2 text-sm text-gray-600">{{ __('messages.department_execution_reports') }}</p>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ __('messages.implementation_rate') }}</p>
                <p class="mt-3 text-4xl font-semibold text-gray-950">{{ $implementationRate }}%</p>
                <p class="mt-2 text-sm text-gray-600">{{ __('messages.reported_against_calendar') }}</p>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ __('messages.approved_reports') }}</p>
                <p class="mt-3 text-4xl font-semibold text-gray-950">{{ $approvalRate }}%</p>
                <p class="mt-2 text-sm text-gray-600">{{ number_format($approvedReportsCount) }} {{ __('messages.approved') }}</p>
            </section>
        </div>

        @if ($canSubmitReports)
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-950">
                    {{ $editingReportId ? __('messages.edit_event_report') : __('messages.submit_event_report') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">{{ __('messages.report_form_help') }}</p>

                <form wire:submit="saveReport" class="mt-5 space-y-4">
                    <div>
                        <x-input-label for="calendar_event_id" :value="__('messages.calendar_event')" />
                        <select wire:model="calendar_event_id" id="calendar_event_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('messages.select_calendar_event') }}</option>
                            @foreach ($reportableEvents as $event)
                                <option value="{{ $event->id }}">
                                    {{ $event->event_date?->translatedFormat('d M Y') }} - {{ $event->title }} ({{ $event->department?->name }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('calendar_event_id')" class="mt-2" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="report_date" :value="__('messages.report_date')" />
                            <x-text-input wire:model="report_date" id="report_date" class="mt-1 block w-full" type="date" />
                            <x-input-error :messages="$errors->get('report_date')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="attendance_count" :value="__('messages.attendance')" />
                            <x-text-input wire:model="attendance_count" id="attendance_count" class="mt-1 block w-full" type="number" min="0" />
                            <x-input-error :messages="$errors->get('attendance_count')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="summary" :value="__('messages.report_summary')" />
                        <textarea wire:model="summary" id="summary" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        <x-input-error :messages="$errors->get('summary')" class="mt-2" />
                    </div>

                    <div class="grid gap-4 lg:grid-cols-3">
                        <div>
                            <x-input-label for="achievements" :value="__('messages.achievements')" />
                            <textarea wire:model="achievements" id="achievements" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            <x-input-error :messages="$errors->get('achievements')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="challenges" :value="__('messages.challenges')" />
                            <textarea wire:model="challenges" id="challenges" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            <x-input-error :messages="$errors->get('challenges')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="recommendations" :value="__('messages.recommendations')" />
                            <textarea wire:model="recommendations" id="recommendations" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            <x-input-error :messages="$errors->get('recommendations')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        @if ($editingReportId)
                            <x-secondary-button type="button" wire:click="cancelReportEdit">{{ __('messages.cancel') }}</x-secondary-button>
                        @endif
                        <x-primary-button>{{ __('messages.save') }}</x-primary-button>
                    </div>
                </form>
            </section>
        @endif

        <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 p-5">
                <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.event_reports') }}</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-5 py-3">{{ __('messages.calendar_event') }}</th>
                            <th class="px-5 py-3">{{ __('messages.department') }}</th>
                            <th class="px-5 py-3">{{ __('messages.report_date') }}</th>
                            <th class="px-5 py-3">{{ __('messages.status') }}</th>
                            <th class="px-5 py-3">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($reports as $report)
                            @php
                                $canEditReport = $report->status !== 'approved'
                                    && $canSubmitReports
                                    && ($canApproveReports || in_array($report->department_id, $submissionDepartmentIds, true));
                            @endphp
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-medium text-gray-950">{{ $report->calendarEvent?->title }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $report->calendarEvent?->event_date?->translatedFormat('d M Y') }}</p>
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ $report->department?->name }}</td>
                                <td class="px-5 py-4 text-gray-600">{{ $report->report_date?->translatedFormat('d M Y') }}</td>
                                <td class="px-5 py-4">
                                    <span @class([
                                        'rounded-full px-2 py-1 text-xs font-medium',
                                        'bg-amber-50 text-amber-700' => $report->status === 'submitted',
                                        'bg-emerald-50 text-emerald-700' => $report->status === 'approved',
                                        'bg-red-50 text-red-700' => $report->status === 'returned',
                                    ])>
                                        {{ __('messages.report_status_'.$report->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-3">
                                        <button wire:click="downloadReport({{ $report->id }})" wire:loading.attr="disabled" wire:target="downloadReport({{ $report->id }})" type="button" class="text-sm font-medium text-red-700 hover:text-red-900 disabled:cursor-wait disabled:opacity-60">{{ __('messages.download_pdf') }}</button>

                                        @if ($canEditReport)
                                            <button wire:click="editReport({{ $report->id }})" type="button" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">{{ __('messages.edit') }}</button>
                                            <button wire:click="deleteReport({{ $report->id }})" wire:confirm="{{ __('messages.confirm_delete_report') }}" type="button" class="text-sm font-medium text-red-600 hover:text-red-800">{{ __('messages.delete') }}</button>
                                        @endif

                                        @if ($canApproveReports)
                                            @if ($report->status !== 'approved')
                                                <button wire:click="approveReport({{ $report->id }})" type="button" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">{{ __('messages.approve') }}</button>
                                            @endif
                                            @if ($report->status !== 'returned')
                                                <button wire:click="returnReport({{ $report->id }})" type="button" class="text-sm font-medium text-gray-700 hover:text-gray-950">{{ __('messages.return_report') }}</button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-gray-500">{{ __('messages.no_event_reports') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
