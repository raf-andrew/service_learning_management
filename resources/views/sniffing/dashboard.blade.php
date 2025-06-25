@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Health Status -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-4">System Health</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($health as $component => $status)
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold capitalize">{{ $component }}</h3>
                    <span class="px-2 py-1 rounded text-sm
                        @if($status['status'] === 'healthy') bg-green-100 text-green-800
                        @elseif($status['status'] === 'warning') bg-yellow-100 text-yellow-800
                        @else bg-red-100 text-red-800
                        @endif">
                        {{ $status['status'] }}
                    </span>
                </div>
                <p class="mt-2 text-gray-600">{{ $status['message'] }}</p>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Alerts -->
    @if(!empty($alerts))
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-4">Active Alerts</h2>
        <div class="space-y-4">
            @foreach($alerts as $type => $typeAlerts)
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold mb-2 capitalize">{{ $type }} Alerts</h3>
                <div class="space-y-2">
                    @foreach($typeAlerts as $alert)
                    <div class="flex items-center justify-between p-2 rounded
                        @if($alert['severity'] === 'critical') bg-red-50
                        @elseif($alert['severity'] === 'warning') bg-yellow-50
                        @else bg-blue-50
                        @endif">
                        <div>
                            <p class="font-medium">{{ $alert['message'] }}</p>
                            @if(isset($alert['details']))
                            <p class="text-sm text-gray-600">{{ $alert['details'] }}</p>
                            @endif
                        </div>
                        <span class="px-2 py-1 rounded text-sm
                            @if($alert['severity'] === 'critical') bg-red-100 text-red-800
                            @elseif($alert['severity'] === 'warning') bg-yellow-100 text-yellow-800
                            @else bg-blue-100 text-blue-800
                            @endif">
                            {{ $alert['severity'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Performance Metrics -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-4">Performance Metrics</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($metrics['current'] as $metric => $value)
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold mb-2 capitalize">{{ str_replace('_', ' ', $metric) }}</h3>
                <p class="text-2xl font-bold">{{ $value }}</p>
                @if(isset($metrics['trends'][$metric]))
                <div class="mt-2 flex items-center">
                    <span class="text-sm
                        @if($metrics['trends'][$metric]['trend'] === 'increasing') text-red-600
                        @elseif($metrics['trends'][$metric]['trend'] === 'decreasing') text-green-600
                        @else text-gray-600
                        @endif">
                        {{ $metrics['trends'][$metric]['percentage'] }}%
                    </span>
                    <span class="ml-2 text-sm text-gray-600">
                        {{ $metrics['trends'][$metric]['trend'] }}
                    </span>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <!-- Historical Data -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-4">Historical Data</h2>
        <div class="bg-white rounded-lg shadow p-4">
            <canvas id="historicalChart" height="300"></canvas>
        </div>
    </div>

    <!-- Recent Activity -->
    <div>
        <h2 class="text-2xl font-bold mb-4">Recent Activity</h2>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operation</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($metrics['historical'] as $record)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $record->date }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            Sniffing Run
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Completed
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $record->total_results }} results
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('historicalChart').getContext('2d');
    const data = @json($metrics['historical']);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(item => item.date),
            datasets: [{
                label: 'Execution Time (s)',
                data: data.map(item => item.avg_execution_time),
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }, {
                label: 'Memory Usage (MB)',
                data: data.map(item => item.avg_memory_usage / (1024 * 1024)),
                borderColor: 'rgb(255, 99, 132)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
@endpush
@endsection 