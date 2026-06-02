@props(['uploads'])

<section {{ $attributes->merge(['class' => 'rounded-lg border border-gray-200 bg-white shadow-sm']) }}>
    <div class="border-b border-gray-200 p-5">
        <h2 class="text-lg font-semibold text-gray-950">{{ __('messages.upload_history') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('messages.upload_history_help') }}</p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-5 py-3">{{ __('messages.uploaded_file') }}</th>
                    <th class="px-5 py-3">{{ __('messages.uploaded_by') }}</th>
                    <th class="px-5 py-3">{{ __('messages.uploaded_at') }}</th>
                    <th class="px-5 py-3">{{ __('messages.import_total_rows') }}</th>
                    <th class="px-5 py-3">{{ __('messages.import_successful_rows') }}</th>
                    <th class="px-5 py-3">{{ __('messages.import_rejected_rows') }}</th>
                    <th class="px-5 py-3">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($uploads as $upload)
                    <tr>
                        <td class="px-5 py-4 font-medium text-gray-950">{{ $upload->original_filename ?: $upload->report_filename }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $upload->uploadedBy?->name ?: '-' }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $upload->completed_at?->format('Y-m-d H:i') ?: $upload->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $upload->total_rows }}</td>
                        <td class="px-5 py-4 text-emerald-700">{{ $upload->imported_count }}</td>
                        <td class="px-5 py-4 text-red-700">{{ $upload->rejected_count }}</td>
                        <td class="px-5 py-4">
                            <a href="{{ route('import-uploads.report', $upload) }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">
                                {{ __('messages.download_report') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-gray-500">{{ __('messages.no_upload_history') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
