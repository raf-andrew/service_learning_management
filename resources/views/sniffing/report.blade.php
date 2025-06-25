<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Sniffing Report</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Code Sniffing Report</h1>
        
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Summary</h2>
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-blue-100 p-4 rounded">
                    <h3 class="font-semibold">Total Files</h3>
                    <p class="text-2xl">{{ $summary['total_files'] }}</p>
                </div>
                <div class="bg-red-100 p-4 rounded">
                    <h3 class="font-semibold">Violations</h3>
                    <p class="text-2xl">{{ $summary['total_violations'] }}</p>
                </div>
                <div class="bg-green-100 p-4 rounded">
                    <h3 class="font-semibold">Files Passed</h3>
                    <p class="text-2xl">{{ $summary['files_passed'] }}</p>
                </div>
            </div>
        </div>

        @foreach($violations as $file => $fileViolations)
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">{{ $file }}</h2>
            <div class="space-y-4">
                @foreach($fileViolations as $violation)
                <div class="border-l-4 border-red-500 pl-4 py-2">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold">{{ $violation['type'] }}</h3>
                            <p class="text-gray-600">{{ $violation['message'] }}</p>
                        </div>
                        <span class="text-sm text-gray-500">Line {{ $violation['line'] }}</span>
                    </div>
                    @if(isset($violation['suggestion']))
                    <div class="mt-2 bg-gray-50 p-3 rounded">
                        <p class="text-sm text-gray-700">
                            <span class="font-semibold">Suggestion:</span>
                            {{ $violation['suggestion'] }}
                        </p>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        @if(isset($trends))
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Trends</h2>
            <div class="space-y-4">
                @foreach($trends as $trend)
                <div class="flex justify-between items-center">
                    <span>{{ $trend['type'] }}</span>
                    <div class="flex items-center">
                        <div class="w-32 bg-gray-200 rounded-full h-2.5">
                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $trend['percentage'] }}%"></div>
                        </div>
                        <span class="ml-2 text-sm text-gray-600">{{ $trend['count'] }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <script>
        // Add any interactive features here
        document.addEventListener('DOMContentLoaded', function() {
            // Example: Add click handlers for expanding/collapsing sections
            document.querySelectorAll('.violation-section').forEach(section => {
                section.addEventListener('click', function() {
                    this.classList.toggle('expanded');
                });
            });
        });
    </script>
</body>
</html> 