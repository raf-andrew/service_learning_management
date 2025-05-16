#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
TEST_DIR=".codespaces/testing"
CONFIG_DIR=".codespaces/config"
STATE_DIR=".codespaces/state"
SCRIPTS_DIR=".codespaces/scripts"
RESULTS_DIR="$TEST_DIR/results"

# Helper functions
log() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

# Initialize test results
init_results() {
    log "Initializing test results..."

    # Create results directory
    mkdir -p "$RESULTS_DIR"

    # Create results file
    cat > "$RESULTS_DIR/infrastructure-results.json" << EOF
{
    "test_run": {
        "id": "$(date +%Y%m%d_%H%M%S)",
        "timestamp": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
        "environment": "test",
        "results": {
            "configuration": {
                "passed": false,
                "details": {}
            },
            "state": {
                "passed": false,
                "details": {}
            },
            "codespaces": {
                "passed": false,
                "details": {}
            },
            "github": {
                "passed": false,
                "details": {}
            }
        }
    }
}
EOF

    log "Test results initialized"
}

# Test configuration
test_configuration() {
    log "Testing configuration..."

    local passed=true
    local details="{}"

    # Check configuration file
    if [ ! -f "$CONFIG_DIR/test-codespaces.json" ]; then
        passed=false
        details=$(echo "$details" | jq '. + {"file_exists": false}')
    else
        details=$(echo "$details" | jq '. + {"file_exists": true}')

        # Check JSON structure
        if ! jq empty "$CONFIG_DIR/test-codespaces.json" 2>/dev/null; then
            passed=false
            details=$(echo "$details" | jq '. + {"valid_json": false}')
        else
            details=$(echo "$details" | jq '. + {"valid_json": true}')

            # Check required fields
            local required_fields=("name" "version" "defaults" "environments")
            for field in "${required_fields[@]}"; do
                if ! jq -e ".$field" "$CONFIG_DIR/test-codespaces.json" > /dev/null; then
                    passed=false
                    details=$(echo "$details" | jq --arg field "$field" '. + {"missing_field": $field}')
                fi
            done
        fi
    fi

    # Update results
    jq --arg passed "$passed" --argjson details "$details" \
        '.test_run.results.configuration.passed = ($passed == "true") | 
         .test_run.results.configuration.details = $details' \
        "$RESULTS_DIR/infrastructure-results.json" > "$RESULTS_DIR/temp.json"
    mv "$RESULTS_DIR/temp.json" "$RESULTS_DIR/infrastructure-results.json"

    if [ "$passed" = true ]; then
        log "Configuration tests passed"
    else
        warn "Configuration tests failed"
        jq -r '.test_run.results.configuration.details | to_entries | .[] | "- \(.key): \(.value)"' "$RESULTS_DIR/infrastructure-results.json"
    fi
}

# Test state management
test_state() {
    log "Testing state management..."

    local passed=true
    local details="{}"

    # Check state file
    if [ ! -f "$STATE_DIR/test-state.json" ]; then
        passed=false
        details=$(echo "$details" | jq '. + {"file_exists": false}')
    else
        details=$(echo "$details" | jq '. + {"file_exists": true}')

        # Check JSON structure
        if ! jq empty "$STATE_DIR/test-state.json" 2>/dev/null; then
            passed=false
            details=$(echo "$details" | jq '. + {"valid_json": false}')
        else
            details=$(echo "$details" | jq '. + {"valid_json": true}')

            # Test state updates
            local test_value="test_$(date +%s)"
            jq --arg value "$test_value" '.test_value = $value' "$STATE_DIR/test-state.json" > "$STATE_DIR/temp.json"
            mv "$STATE_DIR/temp.json" "$STATE_DIR/test-state.json"

            if [ "$(jq -r '.test_value' "$STATE_DIR/test-state.json")" = "$test_value" ]; then
                details=$(echo "$details" | jq '. + {"state_updates": true}')
            else
                passed=false
                details=$(echo "$details" | jq '. + {"state_updates": false}')
            fi
        fi
    fi

    # Update results
    jq --arg passed "$passed" --argjson details "$details" \
        '.test_run.results.state.passed = ($passed == "true") | 
         .test_run.results.state.details = $details' \
        "$RESULTS_DIR/infrastructure-results.json" > "$RESULTS_DIR/temp.json"
    mv "$RESULTS_DIR/temp.json" "$RESULTS_DIR/infrastructure-results.json"

    if [ "$passed" = true ]; then
        log "State management tests passed"
    else
        warn "State management tests failed"
        jq -r '.test_run.results.state.details | to_entries | .[] | "- \(.key): \(.value)"' "$RESULTS_DIR/infrastructure-results.json"
    fi
}

# Test codespaces
test_codespaces() {
    log "Testing codespaces..."

    local passed=true
    local details="{}"

    # Check GitHub CLI authentication
    if ! gh auth status &> /dev/null; then
        passed=false
        details=$(echo "$details" | jq '. + {"github_auth": false}')
    else
        details=$(echo "$details" | jq '. + {"github_auth": true}')

        # List existing codespaces
        local codespaces=$(gh codespace list --json name,state -q '.[] | select(.name | startswith("test-")) | .name')
        if [ -n "$codespaces" ]; then
            details=$(echo "$details" | jq '. + {"existing_codespaces": true}')
        else
            details=$(echo "$details" | jq '. + {"existing_codespaces": false}')
        fi

        # Create test codespace
        local codespace_name="test-codespace-$(date +%s)"
        if gh codespace create --repo "$TEST_REPO" --branch "$TEST_BRANCH" --machine "$TEST_MACHINE" --region "$TEST_REGION" --name "$codespace_name" &> /dev/null; then
            details=$(echo "$details" | jq '. + {"codespace_creation": true}')

            # Check codespace status
            sleep 10  # Wait for codespace to be ready
            local status=$(gh codespace view "$codespace_name" --json state -q '.state')
            if [ "$status" = "Available" ]; then
                details=$(echo "$details" | jq '. + {"codespace_status": true}')

                # Delete test codespace
                if gh codespace delete "$codespace_name" --force &> /dev/null; then
                    details=$(echo "$details" | jq '. + {"codespace_deletion": true}')
                else
                    passed=false
                    details=$(echo "$details" | jq '. + {"codespace_deletion": false}')
                fi
            else
                passed=false
                details=$(echo "$details" | jq '. + {"codespace_status": false}')
            fi
        else
            passed=false
            details=$(echo "$details" | jq '. + {"codespace_creation": false}')
        fi
    fi

    # Update results
    jq --arg passed "$passed" --argjson details "$details" \
        '.test_run.results.codespaces.passed = ($passed == "true") | 
         .test_run.results.codespaces.details = $details' \
        "$RESULTS_DIR/infrastructure-results.json" > "$RESULTS_DIR/temp.json"
    mv "$RESULTS_DIR/temp.json" "$RESULTS_DIR/infrastructure-results.json"

    if [ "$passed" = true ]; then
        log "Codespace tests passed"
    else
        warn "Codespace tests failed"
        jq -r '.test_run.results.codespaces.details | to_entries | .[] | "- \(.key): \(.value)"' "$RESULTS_DIR/infrastructure-results.json"
    fi
}

# Test GitHub integration
test_github() {
    log "Testing GitHub integration..."

    local passed=true
    local details="{}"

    # Check repository access
    if ! gh repo view "$TEST_REPO" &> /dev/null; then
        passed=false
        details=$(echo "$details" | jq '. + {"repo_access": false}')
    else
        details=$(echo "$details" | jq '. + {"repo_access": true}')

        # Check branch access
        if ! git ls-remote --heads origin "$TEST_BRANCH" &> /dev/null; then
            passed=false
            details=$(echo "$details" | jq '. + {"branch_access": false}')
        else
            details=$(echo "$details" | jq '. + {"branch_access": true}')
        fi

        # Check workflow access
        if ! gh workflow list &> /dev/null; then
            passed=false
            details=$(echo "$details" | jq '. + {"workflow_access": false}')
        else
            details=$(echo "$details" | jq '. + {"workflow_access": true}')
        fi
    fi

    # Update results
    jq --arg passed "$passed" --argjson details "$details" \
        '.test_run.results.github.passed = ($passed == "true") | 
         .test_run.results.github.details = $details' \
        "$RESULTS_DIR/infrastructure-results.json" > "$RESULTS_DIR/temp.json"
    mv "$RESULTS_DIR/temp.json" "$RESULTS_DIR/infrastructure-results.json"

    if [ "$passed" = true ]; then
        log "GitHub integration tests passed"
    else
        warn "GitHub integration tests failed"
        jq -r '.test_run.results.github.details | to_entries | .[] | "- \(.key): \(.value)"' "$RESULTS_DIR/infrastructure-results.json"
    fi
}

# Generate report
generate_report() {
    log "Generating test report..."

    local timestamp=$(date +%Y%m%d_%H%M%S)
    local report_file="$TEST_DIR/archives/$timestamp/infrastructure-report.md"

    # Create report directory
    mkdir -p "$TEST_DIR/archives/$timestamp"

    # Generate markdown report
    cat > "$report_file" << EOF
# Infrastructure Test Report

## Test Run Information
- **ID**: $(jq -r '.test_run.id' "$RESULTS_DIR/infrastructure-results.json")
- **Timestamp**: $(jq -r '.test_run.timestamp' "$RESULTS_DIR/infrastructure-results.json")
- **Environment**: $(jq -r '.test_run.environment' "$RESULTS_DIR/infrastructure-results.json")

## Test Results

### Configuration Tests
$(jq -r '.test_run.results.configuration | "Status: \(if .passed then "PASSED" else "FAILED" end)\n\nDetails:\n\(.details | to_entries | .[] | "- \(.key): \(.value)")"' "$RESULTS_DIR/infrastructure-results.json")

### State Management Tests
$(jq -r '.test_run.results.state | "Status: \(if .passed then "PASSED" else "FAILED" end)\n\nDetails:\n\(.details | to_entries | .[] | "- \(.key): \(.value)")"' "$RESULTS_DIR/infrastructure-results.json")

### Codespace Tests
$(jq -r '.test_run.results.codespaces | "Status: \(if .passed then "PASSED" else "FAILED" end)\n\nDetails:\n\(.details | to_entries | .[] | "- \(.key): \(.value)")"' "$RESULTS_DIR/infrastructure-results.json")

### GitHub Integration Tests
$(jq -r '.test_run.results.github | "Status: \(if .passed then "PASSED" else "FAILED" end)\n\nDetails:\n\(.details | to_entries | .[] | "- \(.key): \(.value)")"' "$RESULTS_DIR/infrastructure-results.json")
EOF

    log "Test report generated: $report_file"
}

# Main execution
main() {
    log "Starting infrastructure tests..."

    # Initialize test results
    init_results

    # Run tests
    test_configuration
    test_state
    test_codespaces
    test_github

    # Generate report
    generate_report

    log "Infrastructure tests completed"
}

# Run main function
main 