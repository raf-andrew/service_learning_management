<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Report: {{ $results['suite'] }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        h1, h2, h3 {
            color: #2c3e50;
        }

        .summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .test-results {
            margin-bottom: 30px;
        }

        .test-case {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
        }

        .test-case.passed {
            border-left: 4px solid #28a745;
        }

        .test-case.failed {
            border-left: 4px solid #dc3545;
        }

        .test-case.error {
            border-left: 4px solid #ffc107;
        }

        .test-case.skipped {
            border-left: 4px solid #6c757d;
        }

        .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
        }

        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }

        .security-checks, .code-quality {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .check-item, .metric-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .check-item:last-child, .metric-item:last-child {
            border-bottom: none;
        }

        .status {
            font-weight: bold;
        }

        .status.passed {
            color: #28a745;
        }

        .status.failed {
            color: #dc3545;
        }

        .status.warning {
            color: #ffc107;
        }

        .timestamp {
            color: #6c757d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <h1>Test Report: {{ $results['suite'] }}</h1>

    <div class="summary">
        <h2>Summary</h2>
        <p>Start Time: {{ $results['start_time'] }}</p>
        <p>End Time: {{ $results['end_time'] }}</p>
        <p>Duration: {{ $results['duration'] }} seconds</p>
    </div>

    <div class="metrics">
        <div class="metric-card">
            <div class="metric-value">{{ count($results['tests']) }}</div>
            <div class="metric-label">Total Tests</div>
        </div>
        <div class="metric-card">
            <div class="metric-value">{{ count(array_filter($results['tests'], fn($t) => $t['result'] === 'passed')) }}</div>
            <div class="metric-label">Passed</div>
        </div>
        <div class="metric-card">
            <div class="metric-value">{{ count(array_filter($results['tests'], fn($t) => $t['result'] === 'failed')) }}</div>
            <div class="metric-label">Failed</div>
        </div>
        <div class="metric-card">
            <div class="metric-value">{{ count(array_filter($results['tests'], fn($t) => $t['result'] === 'skipped')) }}</div>
            <div class="metric-label">Skipped</div>
        </div>
    </div>

    <div class="test-results">
        <h2>Test Results</h2>
        @foreach($results['tests'] as $test)
            <div class="test-case {{ $test['result'] }}">
                <h3>{{ $test['name'] }}</h3>
                <p>Result: <span class="status {{ $test['result'] }}">{{ $test['result'] }}</span></p>
                <p>Time: {{ $test['time'] }} seconds</p>
                <p>Memory: {{ $test['memory'] }} bytes</p>
                <p>Coverage: {{ $test['coverage'] }}%</p>
            </div>
        @endforeach
    </div>

    @if(count($securityChecks) > 0)
        <div class="security-checks">
            <h2>Security Checks</h2>
            @foreach($securityChecks as $check)
                <div class="check-item">
                    <span>{{ $check['check'] }}</span>
                    <span class="status {{ $check['result'] }}">{{ $check['result'] }}</span>
                </div>
            @endforeach
        </div>
    @endif

    @if(count($codeQuality) > 0)
        <div class="code-quality">
            <h2>Code Quality Metrics</h2>
            @foreach($codeQuality as $metric)
                <div class="metric-item">
                    <span>{{ $metric['metric'] }}</span>
                    <span>{{ $metric['value'] }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="timestamp">
        Generated: {{ now() }}
    </div>
</body>
</html> 