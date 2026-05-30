<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('messages.report_pdf_title') }}</title>
    @include('pdf.partials.operational-report-styles')
</head>
<body>
    @include('pdf.partials.operational-report-footer')
    @include('pdf.partials.operational-report-letterhead')

    <section class="title-band">
        <h1>{{ __('messages.report_pdf_title') }}</h1>
        <p>{{ $report->calendarEvent?->title ?: __('messages.calendar_event') }} - {{ $report->department?->name ?: __('messages.department') }}</p>
    </section>

    <table class="grid">
        <tr>
            <td class="metric">
                <div class="metric-label">{{ __('messages.report_date') }}</div>
                <div class="metric-value">{{ $report->report_date?->translatedFormat('d M Y') ?: '-' }}</div>
            </td>
            <td class="metric">
                <div class="metric-label">{{ __('messages.status') }}</div>
                <div class="metric-value">
                    <span class="status status-{{ $report->status }}">{{ __('messages.report_status_'.$report->status) }}</span>
                </div>
            </td>
            <td class="metric">
                <div class="metric-label">{{ __('messages.attendance') }}</div>
                <div class="metric-value">{{ $report->attendance_count !== null ? number_format($report->attendance_count) : __('messages.not_recorded') }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">{{ __('messages.event_details') }}</div>
    <table class="details">
        <tr>
            <th>{{ __('messages.calendar_event') }}</th>
            <td>{{ $report->calendarEvent?->title ?: '-' }}</td>
        </tr>
        <tr>
            <th>{{ __('messages.department') }}</th>
            <td>{{ $report->department?->name ?: '-' }}</td>
        </tr>
        <tr>
            <th>{{ __('messages.event_date') }}</th>
            <td>{{ $report->calendarEvent?->event_date?->translatedFormat('d M Y') ?: '-' }}</td>
        </tr>
        <tr>
            <th>{{ __('messages.period') }}</th>
            <td>{{ $report->calendarEvent?->starts_at ?: '-' }} - {{ $report->calendarEvent?->ends_at ?: '-' }}</td>
        </tr>
    </table>

    <div class="section-title">{{ __('messages.report_details') }}</div>
    <div class="text-block">
        <h3>{{ __('messages.report_summary') }}</h3>
        <div>{!! nl2br(e($report->summary ?: __('messages.not_recorded'))) !!}</div>
    </div>
    <div class="text-block">
        <h3>{{ __('messages.achievements') }}</h3>
        <div>{!! nl2br(e($report->achievements ?: __('messages.not_recorded'))) !!}</div>
    </div>
    <div class="text-block">
        <h3>{{ __('messages.challenges') }}</h3>
        <div>{!! nl2br(e($report->challenges ?: __('messages.not_recorded'))) !!}</div>
    </div>
    <div class="text-block">
        <h3>{{ __('messages.recommendations') }}</h3>
        <div>{!! nl2br(e($report->recommendations ?: __('messages.not_recorded'))) !!}</div>
    </div>

    <div class="section-title">{{ __('messages.statistical_summary') }}</div>
    <section class="panel">
        <table class="chart-grid">
            <tr>
                <td class="donut-cell">
                    @include('pdf.partials.donut-chart', [
                        'label' => __('messages.report_completeness'),
                        'value' => $metrics['completion_rate'],
                        'color' => '#b91c1c',
                    ])
                </td>
                <td>
                    <div class="bar-row">
                        <div class="bar-label">{{ __('messages.report_completeness') }} - {{ $metrics['completion_rate'] }}%</div>
                        <div class="bar-track"><div class="bar-fill" style="width: {{ $metrics['completion_rate'] }}%;"></div></div>
                    </div>
                    <div class="bar-row">
                        <div class="bar-label">{{ __('messages.review_progress') }} - {{ $metrics['review_rate'] }}%</div>
                        <div class="bar-track"><div class="bar-fill gold" style="width: {{ $metrics['review_rate'] }}%;"></div></div>
                    </div>
                </td>
            </tr>
        </table>
        <div class="chart-note">
            {{ __('messages.sections_completed', ['filled' => $metrics['filled_sections'], 'total' => $metrics['total_sections']]) }}
        </div>
    </section>

    <div class="section-title">{{ __('messages.accountability') }}</div>
    <table class="details">
        <tr>
            <th>{{ __('messages.submitted_by') }}</th>
            <td>{{ $submittedByName }}</td>
        </tr>
        <tr>
            <th>{{ __('messages.reviewed_by') }}</th>
            <td>{{ $reviewedByName ?: __('messages.not_reviewed') }}</td>
        </tr>
        <tr>
            <th>{{ __('messages.downloaded_by') }}</th>
            <td>{{ $downloadedByName }}</td>
        </tr>
        <tr>
            <th>{{ __('messages.downloaded_at') }}</th>
            <td>{{ $downloadedAt->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}</td>
        </tr>
    </table>
</body>
</html>
