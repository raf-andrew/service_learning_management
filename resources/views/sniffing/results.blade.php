@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Filters -->
    <div class="mb-8 bg-white rounded-lg shadow p-4">
        <form action="{{ route('sniffing.results') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="file" class="block text-sm font-medium text-gray-700">File</label>
                <select name="file" id="file" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Files</option>
                    @foreach($files as $file)
                    <option value="{{ $file }}" {{ request('file') == $file ? 'selected' : '' }}>{{ $file }}</option>
                    @endforeach
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

            <div>
                <label for="days" class="block text-sm font-medium text-gray-700">Time Range</label>
                <select name="days" id="days" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="1" {{ request('days') == 1 ? 'selected' : '' }}>Last 24 hours</option>
                    <option value="7" {{ request('days') == 7 ? 'selected' : '' }}>Last 7 days</option>
                    <option value="30" {{ request('days') == 30 ? 'selected' : '' }}>Last 30 days</option>
                    <option value="90" {{ request('days') == 90 ? 'selected' : '' }}>Last 90 days</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Summary -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-4">Summary</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold mb-2">Total Issues</h3>
                <p class="text-3xl font-bold">{{ $summary['total'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold mb-2">Errors</h3>
                <p class="text-3xl font-bold text-red-600">{{ $summary['errors'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold mb-2">Warnings</h3>
                <p class="text-3xl font-bold text-yellow-600">{{ $summary['warnings'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold mb-2">Info</h3>
                <p class="text-3xl font-bold text-blue-600">{{ $summary['info'] }}</p>
            </div>
        </div>
    </div>

    <!-- Results Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Results</h3>
            <div class="flex space-x-2">
                <button onclick="exportResults('csv')" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Export CSV
                </button>
                <button onclick="exportResults('json')" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Export JSON
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Line</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($results as $result)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $result->file_path }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $result->line }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($result->type === 'error') bg-red-100 text-red-800
                                @elseif($result->type === 'warning') bg-yellow-100 text-yellow-800
                                @else bg-blue-100 text-blue-800
                                @endif">
                                {{ $result->type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $result->message }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $result->source }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $result->created_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="showDetails({{ $result->id }})" class="text-indigo-600 hover:text-indigo-900">View</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 sm:px-6">
            {{ $results->links() }}
        </div>
    </div>
</div>

<!-- Details Modal -->
<div id="detailsModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Issue Details
                        </h3>
                        <div class="mt-4">
                            <div id="detailsContent"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showDetails(id) {
    fetch(`/api/sniffing/results/${id}`)
        .then(response => response.json())
        .then(data => {
            const content = document.getElementById('detailsContent');
            content.innerHTML = `
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">File</dt>
                        <dd class="mt-1 text-sm text-gray-900">${data.file_path}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Line</dt>
                        <dd class="mt-1 text-sm text-gray-900">${data.line}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Type</dt>
                        <dd class="mt-1 text-sm text-gray-900">${data.type}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Message</dt>
                        <dd class="mt-1 text-sm text-gray-900">${data.message}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Source</dt>
                        <dd class="mt-1 text-sm text-gray-900">${data.source}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Code Context</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <pre class="bg-gray-50 p-2 rounded">${data.code_context}</pre>
                        </dd>
                    </div>
                </dl>
            `;
            document.getElementById('detailsModal').classList.remove('hidden');
        });
}

function closeModal() {
    document.getElementById('detailsModal').classList.add('hidden');
}

function exportResults(format) {
    const params = new URLSearchParams(window.location.search);
    params.append('format', format);
    window.location.href = `/api/sniffing/results/export?${params.toString()}`;
}
</script>
@endpush
@endsection 