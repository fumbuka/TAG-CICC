<div class="footer">
    {{ __('messages.report_downloaded_by') }}: {{ $downloadedByName }}
    &nbsp;|&nbsp;
    {{ __('messages.report_downloaded_at') }}: {{ $downloadedAt->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}
</div>
