<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('messages.report_pdf_title') }}</title>
    <style>
        @page {
            margin: 22mm 14mm 20mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            color: #1f2937;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11px;
            line-height: 1.45;
            margin: 0;
        }

        .footer {
            border-top: 1px solid #d1d5db;
            bottom: -12mm;
            color: #6b7280;
            font-size: 9px;
            left: 0;
            position: fixed;
            right: 0;
            text-align: center;
        }

        .letterhead {
            border-bottom: 3px solid #b91c1c;
            margin-bottom: 16px;
            padding-bottom: 12px;
            width: 100%;
        }

        .letterhead td {
            vertical-align: middle;
        }

        .logo {
            height: 74px;
            width: 74px;
        }

        .church-name {
            color: #111827;
            font-weight: 800;
            letter-spacing: .5px;
            text-align: center;
            text-transform: uppercase;
        }

        .church-name .h1 {
            color: #b91c1c;
            font-size: 18px;
            margin: 0;
        }

        .church-name .h2 {
            font-size: 15px;
            margin: 2px 0 0;
        }

        .church-name .h3 {
            color: #4b5563;
            font-size: 11px;
            margin: 2px 0 0;
        }

        .title-band {
            background: #111827;
            border-radius: 8px;
            color: #ffffff;
            margin-bottom: 14px;
            padding: 13px 16px;
        }

        .title-band h1 {
            font-size: 18px;
            margin: 0;
        }

        .title-band p {
            color: #fef3c7;
            margin: 4px 0 0;
        }

        .grid {
            border-collapse: separate;
            border-spacing: 10px;
            margin: 0 0 12px;
            width: 100%;
        }

        .metric,
        .panel {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
        }

        .metric-label {
            color: #6b7280;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .4px;
            text-transform: uppercase;
        }

        .metric-value {
            color: #111827;
            font-size: 17px;
            font-weight: 800;
            margin-top: 4px;
        }

        .section-title {
            border-left: 4px solid #b91c1c;
            color: #111827;
            font-size: 13px;
            font-weight: 800;
            margin: 16px 0 8px;
            padding-left: 8px;
            text-transform: uppercase;
        }

        .details {
            border-collapse: collapse;
            width: 100%;
        }

        .details th {
            background: #f9fafb;
            color: #6b7280;
            font-size: 9px;
            letter-spacing: .3px;
            padding: 8px;
            text-align: left;
            text-transform: uppercase;
            width: 28%;
        }

        .details td {
            border-bottom: 1px solid #f3f4f6;
            padding: 8px;
        }

        .status {
            border-radius: 999px;
            display: inline-block;
            font-size: 10px;
            font-weight: 800;
            padding: 4px 9px;
            text-transform: uppercase;
        }

        .status-submitted {
            background: #fef3c7;
            color: #92400e;
        }

        .status-approved {
            background: #dcfce7;
            color: #166534;
        }

        .status-returned {
            background: #fee2e2;
            color: #991b1b;
        }

        .text-block {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 9px;
            padding: 10px;
        }

        .text-block h3 {
            color: #111827;
            font-size: 12px;
            margin: 0 0 5px;
        }

        .muted {
            color: #6b7280;
        }

        .bar-row {
            margin-bottom: 11px;
        }

        .bar-label {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .bar-track {
            background: #f3f4f6;
            border-radius: 999px;
            height: 14px;
            overflow: hidden;
            width: 100%;
        }

        .bar-fill {
            background: #b91c1c;
            height: 14px;
        }

        .bar-fill.gold {
            background: #f59e0b;
        }

        .chart-note {
            color: #6b7280;
            font-size: 10px;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="footer">
        {{ __('messages.report_downloaded_by') }}: {{ $downloadedByName }}
        &nbsp;|&nbsp;
        {{ __('messages.report_downloaded_at') }}: {{ $downloadedAt->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}
    </div>

    <table class="letterhead">
        <tr>
            <td style="width: 18%; text-align: left;">
                @if ($motherChurchLogo)
                    <img class="logo" src="{{ $motherChurchLogo }}" alt="TAG">
                @endif
            </td>
            <td class="church-name">
                <div class="h1">{{ __('messages.parent_church_name') }}</div>
                <div class="h2">{{ __('messages.local_church_name') }}</div>
                <div class="h3">{{ __('messages.church_location') }}</div>
            </td>
            <td style="width: 18%; text-align: right;">
                @if ($localChurchLogo)
                    <img class="logo" src="{{ $localChurchLogo }}" alt="TAG-CICC">
                @endif
            </td>
        </tr>
    </table>

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
        <div class="bar-row">
            <div class="bar-label">{{ __('messages.report_completeness') }} - {{ $metrics['completion_rate'] }}%</div>
            <div class="bar-track"><div class="bar-fill" style="width: {{ $metrics['completion_rate'] }}%;"></div></div>
        </div>
        <div class="bar-row">
            <div class="bar-label">{{ __('messages.review_progress') }} - {{ $metrics['review_rate'] }}%</div>
            <div class="bar-track"><div class="bar-fill gold" style="width: {{ $metrics['review_rate'] }}%;"></div></div>
        </div>
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
