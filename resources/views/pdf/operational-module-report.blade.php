<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    @include('pdf.partials.operational-report-styles')
    <style>
        .module-meta {
            color: #fef3c7;
            font-size: 10px;
            margin-top: 6px;
        }

        .summary-bars {
            margin-top: 2px;
        }

        .report-table {
            border-collapse: collapse;
            width: 100%;
        }

        .report-table th {
            background: #111827;
            color: #ffffff;
            font-size: 9px;
            letter-spacing: .3px;
            padding: 8px;
            text-align: left;
            text-transform: uppercase;
        }

        .report-table td {
            border-bottom: 1px solid #f3f4f6;
            padding: 8px;
            vertical-align: top;
        }

        .empty-state {
            border: 1px dashed #d1d5db;
            border-radius: 8px;
            color: #6b7280;
            padding: 14px;
            text-align: center;
        }
    </style>
</head>
<body>
    @include('pdf.partials.operational-report-footer')
    @include('pdf.partials.operational-report-letterhead')

    <section class="title-band">
        <h1>{{ $report['title'] }}</h1>
        <p>{{ $report['subtitle'] }}</p>
        <div class="module-meta">{{ __('messages.scope') }}: {{ $report['scopeLabel'] }}</div>
    </section>

    <table class="grid">
        @foreach (array_chunk($report['metrics'], 2) as $metricRow)
            <tr>
                @foreach ($metricRow as $metric)
                    <td class="metric" style="width: 50%;">
                        <div class="metric-label">{{ $metric['label'] }}</div>
                        <div class="metric-value">{{ $metric['value'] }}</div>
                        <div class="chart-note">{{ $metric['note'] }}</div>
                    </td>
                @endforeach
                @if (count($metricRow) === 1)
                    <td style="width: 50%;"></td>
                @endif
            </tr>
        @endforeach
    </table>

    <div class="section-title">{{ __('messages.statistical_summary') }}</div>
    <section class="panel">
        @if ($report['chartRows'] !== [])
            <div class="summary-bars">
                @foreach ($report['chartRows'] as $row)
                    <div class="bar-row">
                        <div class="bar-label">{{ $row['label'] }} - {{ $row['formatted'] }}</div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: {{ $row['percentage'] }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">{{ __('messages.no_report_data') }}</div>
        @endif
    </section>

    @foreach ($report['sections'] as $section)
        <div class="section-title">{{ $section['title'] }}</div>
        @if ($section['rows'] !== [])
            <table class="report-table">
                <thead>
                    <tr>
                        @foreach ($section['headers'] as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($section['rows'] as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">{{ __('messages.no_report_data') }}</div>
        @endif
    @endforeach

    <div class="section-title">{{ __('messages.accountability') }}</div>
    <table class="details">
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
