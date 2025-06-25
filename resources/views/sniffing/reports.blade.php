@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8 flex justify-between items-center">
        <h1 class="text-2xl font-bold">Sniffing Reports</h1>
        <button onclick="showGenerateReportModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            Generate Report
        </button>
    </div>

    <!-- Report Filters -->
    <div class="mb-8 bg-white rounded-lg shadow p-4">
        <form action="{{ route('sniffing.reports') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="format" class="block text-sm font-medium text-gray-700">Format</label>
                <select name="format" id="format" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Formats</option>
                    <option value="html" {{ request('format') == 'html' ? 'selected' : '' }}>HTML</option>
                    <option value="markdown" {{ request('format') == 'markdown' ? 'selected' : '' }}>Markdown</option>
                    <option value="json" {{ request('format') == 'json' ? 'selected' : '' }}>JSON</option>
                </select>
            </div>

            <div>
                <label for="days" class="block text-sm font-medium text-gray-700">Time Range</label>
                <select name="days" id="days" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="1" {{ request('days') == 1 ? 'selected' : '' }}>Last 24 hours</option>
                    <option value="7" {{ request('days') == 7 ? 'selected' : '' }}>Last 7 days</option>
                    <option value="30" {{ request('days') == 30 ? 'selected' : '' }}>Last 30 days</option>
                    <option value="90" {{ request('days') == 90 ? 'selected' : '' }}>Last 90 days</option>
                </select>
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                <select name="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Types</option>
                    <option value="error" {{ request('type') == 'error' ? 'selected' : '' }}>Error</option>
                    <option value="warning" {{ request('type') == 'warning' ? 'selected' : '' }}>Warning</option>
                    <option value="info" {{ request('type') == 'info' ? 'selected' : '' }}>Info</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Reports Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Generated Reports</h3>
            <div class="flex space-x-2">
                <button onclick="exportReports('csv')" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Export CSV
                </button>
                <button onclick="exportReports('json')" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Export JSON
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Format</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Generated</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($reports as $report)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $report->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ strtoupper($report->format) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $report->created_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $report->size }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($report->status === 'completed') bg-green-100 text-green-800
                                @elseif($report->status === 'failed') bg-red-100 text-red-800
                                @else bg-yellow-100 text-yellow-800
                                @endif">
                                {{ $report->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="{{ route('sniffing.reports.download', $report->id) }}" class="text-indigo-600 hover:text-indigo-900">Download</a>
                                <button onclick="deleteReport({{ $report->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 sm:px-6">
            {{ $reports->links() }}
        </div>
    </div>
</div>

<!-- Generate Report Modal -->
<div id="reportModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="reportForm" onsubmit="generateReport(event)">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Generate Report
                            </h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="reportName" class="block text-sm font-medium text-gray-700">Name</label>
                                    <input type="text" name="name" id="reportName" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label for="reportFormat" class="block text-sm font-medium text-gray-700">Format</label>
                                    <select name="format" id="reportFormat" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="html">HTML</option>
                                        <option value="markdown">Markdown</option>
                                        <option value="json">JSON</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="reportDays" class="block text-sm font-medium text-gray-700">Time Range</label>
                                    <select name="days" id="reportDays" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="1">Last 24 hours</option>
                                        <option value="7">Last 7 days</option>
                                        <option value="30">Last 30 days</option>
                                        <option value="90">Last 90 days</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="reportType" class="block text-sm font-medium text-gray-700">Type</label>
                                    <select name="type" id="reportType"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">All Types</option>
                                        <option value="error">Error</option>
                                        <option value="warning">Warning</option>
                                        <option value="info">Info</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Generate
                    </button>
                    <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showGenerateReportModal() {
    document.getElementById('reportForm').reset();
    document.getElementById('reportModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('reportModal').classList.add('hidden');
}

function generateReport(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());

    fetch('/api/sniffing/reports', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(() => {
        closeModal();
        window.location.reload();
    });
}

function deleteReport(id) {
    if (confirm('Are you sure you want to delete this report?')) {
        fetch(`/api/sniffing/reports/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(() => {
            window.location.reload();
        });
    }
}

function exportReports(format) {
    const params = new URLSearchParams(window.location.search);
    params.append('format', format);
    window.location.href = `/api/sniffing/reports/export?${params.toString()}`;
}
</script>
@endpush
@endsection 