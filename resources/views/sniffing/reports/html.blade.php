<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Sniffing Report</title>
    <style>
        :root {
            --primary-color: #4a5568;
            --secondary-color: #718096;
            --success-color: #48bb78;
            --warning-color: #ecc94b;
            --error-color: #f56565;
            --background-color: #f7fafc;
            --card-background: #ffffff;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: var(--primary-color);
            background-color: var(--background-color);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .card {
            background-color: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .statistics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            text-align: center;
            padding: 15px;
            border-radius: 6px;
            background-color: var(--card-background);
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }

        .results {
            margin-top: 40px;
        }

        .result-item {
            margin-bottom: 30px;
        }

        .violation {
            padding: 10px;
            margin: 5px 0;
            border-left: 4px solid;
        }

        .violation.error {
            border-left-color: var(--error-color);
            background-color: rgba(245, 101, 101, 0.1);
        }

        .violation.warning {
            border-left-color: var(--warning-color);
            background-color: rgba(236, 201, 75, 0.1);
        }

        .trends {
            margin-top: 40px;
        }

        .trend-chart {
            width: 100%;
            height: 300px;
            margin-top: 20px;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-success {
            background-color: var(--success-color);
            color: white;
        }

        .badge-warning {
            background-color: var(--warning-color);
            color: var(--primary-color);
        }

        .badge-error {
            background-color: var(--error-color);
            color: white;
        }

        @media (max-width: 768px) {
            .statistics {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Code Sniffing Report</h1>
            <p>Generated on {{ $generated_at->format('Y-m-d H:i:s') }}</p>
        </div>

        <div class="statistics">
            <div class="stat-card">
                <h3>Total Results</h3>
                <div class="stat-value">{{ $statistics['total_results'] }}</div>
            </div>
            <div class="stat-card">
                <h3>Total Violations</h3>
                <div class="stat-value">{{ $statistics['total_violations'] }}</div>
            </div>
            <div class="stat-card">
                <h3>Errors</h3>
                <div class="stat-value">{{ $statistics['error_count'] }}</div>
            </div>
            <div class="stat-card">
                <h3>Warnings</h3>
                <div class="stat-value">{{ $statistics['warning_count'] }}</div>
            </div>
            <div class="stat-card">
                <h3>Files Analyzed</h3>
                <div class="stat-value">{{ $statistics['files_analyzed'] }}</div>
            </div>
        </div>

        <div class="results">
            <h2>Detailed Results</h2>
            @foreach($results as $result)
                <div class="card result-item">
                    <h3>{{ $result->file_path }}</h3>
                    <p>
                        <span class="badge badge-{{ $result->severity_level }}">
                            {{ ucfirst($result->severity_level) }}
                        </span>
                        <span>Date: {{ $result->sniff_date->format('Y-m-d H:i:s') }}</span>
                        <span>Execution Time: {{ $result->formatted_execution_time }}</span>
                    </p>

                    @if($result->violations->isNotEmpty())
                        <h4>Violations</h4>
                        @foreach($result->violations as $violation)
                            <div class="violation {{ $violation->severity }}">
                                <strong>{{ $violation->message }}</strong>
                                <p>Location: {{ $violation->formatted_location }}</p>
                                <p>Rule: {{ $violation->rule_name }}</p>
                                <p>Status: {{ $violation->fix_status }}</p>
                            </div>
                        @endforeach
                    @endif
                </div>
            @endforeach
        </div>

        @if($trends)
            <div class="trends">
                <h2>Trend Analysis</h2>
                <div class="card">
                    <canvas id="trendChart" class="trend-chart"></canvas>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                const ctx = document.getElementById('trendChart').getContext('2d');
                const data = @json($trends);
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.map(item => item.date),
                        datasets: [
                            {
                                label: 'Errors',
                                data: data.map(item => item.total_errors),
                                borderColor: '#f56565',
                                tension: 0.1
                            },
                            {
                                label: 'Warnings',
                                data: data.map(item => item.total_warnings),
                                borderColor: '#ecc94b',
                                tension: 0.1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            </script>
        @endif
    </div>
</body>
</html> 