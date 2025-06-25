# Code Sniffing Report

Generated on: {{ $generated_at->format('Y-m-d H:i:s') }}

## Statistics

| Metric | Value |
|--------|-------|
| Total Results | {{ $statistics['total_results'] }} |
| Total Violations | {{ $statistics['total_violations'] }} |
| Errors | {{ $statistics['error_count'] }} |
| Warnings | {{ $statistics['warning_count'] }} |
| Fixable Issues | {{ $statistics['fixable_count'] }} |
| Fixed Issues | {{ $statistics['fixed_count'] }} |
| Files Analyzed | {{ $statistics['files_analyzed'] }} |

## Detailed Results

@foreach($results as $result)
### {{ $result->file_path }}

**Date:** {{ $result->sniff_date->format('Y-m-d H:i:s') }}  
**Status:** {{ ucfirst($result->severity_level) }}  
**Execution Time:** {{ $result->formatted_execution_time }}  
**Standards Used:** {{ $result->standards_list }}

@if($result->violations->isNotEmpty())
#### Violations

@foreach($result->violations as $violation)
- **{{ $violation->message }}**
  - Severity: {{ ucfirst($violation->severity) }}
  - Location: {{ $violation->formatted_location }}
  - Rule: {{ $violation->rule_name }}
  - Status: {{ $violation->fix_status }}
@endforeach
@endif

---
@endforeach

@if($trends)
## Trend Analysis

| Date | Total Runs | Errors | Warnings |
|------|------------|--------|----------|
@foreach($trends as $trend)
| {{ $trend['date'] }} | {{ $trend['total_runs'] }} | {{ $trend['total_errors'] }} | {{ $trend['total_warnings'] }} |
@endforeach

### Trend Summary
- Total days analyzed: {{ count($trends) }}
- Average errors per day: {{ number_format(array_sum(array_column($trends, 'total_errors')) / count($trends), 2) }}
- Average warnings per day: {{ number_format(array_sum(array_column($trends, 'total_warnings')) / count($trends), 2) }}
@endif 