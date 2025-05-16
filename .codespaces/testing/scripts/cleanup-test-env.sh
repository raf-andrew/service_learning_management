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

# Clean up test codespaces
cleanup_codespaces() {
    log "Cleaning up test codespaces..."

    # Get list of test codespaces
    local codespaces=$(gh codespace list --json name,state -q '.[] | select(.name | startswith("test-")) | .name')

    if [ -n "$codespaces" ]; then
        for codespace in $codespaces; do
            log "Deleting codespace: $codespace"
            gh codespace delete "$codespace" --force
        done
    else
        warn "No test codespaces found"
    fi

    log "Test codespaces cleanup completed"
}

# Clean up test networks
cleanup_networks() {
    log "Cleaning up test networks..."

    # Get list of test networks
    local networks=$(docker network ls --filter "name=test-" --format "{{.Name}}")

    if [ -n "$networks" ]; then
        for network in $networks; do
            log "Removing network: $network"
            docker network rm "$network"
        done
    else
        warn "No test networks found"
    fi

    log "Test networks cleanup completed"
}

# Clean up test volumes
cleanup_volumes() {
    log "Cleaning up test volumes..."

    # Get list of test volumes
    local volumes=$(docker volume ls --filter "name=test-" --format "{{.Name}}")

    if [ -n "$volumes" ]; then
        for volume in $volumes; do
            log "Removing volume: $volume"
            docker volume rm "$volume"
        done
    else
        warn "No test volumes found"
    fi

    log "Test volumes cleanup completed"
}

# Clean up test files
cleanup_files() {
    log "Cleaning up test files..."

    # Remove test configuration
    if [ -f "$CONFIG_DIR/test-codespaces.json" ]; then
        log "Removing test configuration"
        rm "$CONFIG_DIR/test-codespaces.json"
    fi

    # Remove test state
    if [ -f "$STATE_DIR/test-state.json" ]; then
        log "Removing test state"
        rm "$STATE_DIR/test-state.json"
    fi

    # Remove test credentials
    if [ -f "$TEST_DIR/credentials.json" ]; then
        log "Removing test credentials"
        rm "$TEST_DIR/credentials.json"
    fi

    # Archive test results
    if [ -d "$RESULTS_DIR" ]; then
        local timestamp=$(date +%Y%m%d_%H%M%S)
        local archive_dir="$TEST_DIR/archives/$timestamp"
        
        log "Archiving test results to $archive_dir"
        mkdir -p "$archive_dir"
        mv "$RESULTS_DIR"/* "$archive_dir/"
    fi

    log "Test files cleanup completed"
}

# Clean up test branch
cleanup_branch() {
    log "Cleaning up test branch..."

    # Check if we're on the test branch
    if [ "$(git rev-parse --abbrev-ref HEAD)" = "$TEST_BRANCH" ]; then
        # Switch to main branch
        git checkout main

        # Delete test branch
        if git show-ref --verify --quiet refs/heads/$TEST_BRANCH; then
            log "Deleting test branch: $TEST_BRANCH"
            git branch -D "$TEST_BRANCH"
        fi
    else
        warn "Not on test branch, skipping branch cleanup"
    fi

    log "Test branch cleanup completed"
}

# Verify cleanup
verify_cleanup() {
    log "Verifying cleanup..."

    local passed=true
    local details="{}"

    # Check for remaining codespaces
    local remaining_codespaces=$(gh codespace list --json name,state -q '.[] | select(.name | startswith("test-")) | .name')
    if [ -n "$remaining_codespaces" ]; then
        passed=false
        details=$(echo "$details" | jq '. + {"remaining_codespaces": true}')
    else
        details=$(echo "$details" | jq '. + {"remaining_codespaces": false}')
    fi

    # Check for remaining networks
    local remaining_networks=$(docker network ls --filter "name=test-" --format "{{.Name}}")
    if [ -n "$remaining_networks" ]; then
        passed=false
        details=$(echo "$details" | jq '. + {"remaining_networks": true}')
    else
        details=$(echo "$details" | jq '. + {"remaining_networks": false}')
    fi

    # Check for remaining volumes
    local remaining_volumes=$(docker volume ls --filter "name=test-" --format "{{.Name}}")
    if [ -n "$remaining_volumes" ]; then
        passed=false
        details=$(echo "$details" | jq '. + {"remaining_volumes": true}')
    else
        details=$(echo "$details" | jq '. + {"remaining_volumes": false}')
    fi

    # Check for remaining files
    if [ -f "$CONFIG_DIR/test-codespaces.json" ] || \
       [ -f "$STATE_DIR/test-state.json" ] || \
       [ -f "$TEST_DIR/credentials.json" ]; then
        passed=false
        details=$(echo "$details" | jq '. + {"remaining_files": true}')
    else
        details=$(echo "$details" | jq '. + {"remaining_files": false}')
    fi

    # Check for remaining branch
    if git show-ref --verify --quiet refs/heads/$TEST_BRANCH; then
        passed=false
        details=$(echo "$details" | jq '. + {"remaining_branch": true}')
    else
        details=$(echo "$details" | jq '. + {"remaining_branch": false}')
    fi

    # Create cleanup report
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local report_file="$TEST_DIR/archives/$timestamp/cleanup-report.json"

    cat > "$report_file" << EOF
{
    "cleanup": {
        "timestamp": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
        "passed": $passed,
        "details": $details
    }
}
EOF

    if [ "$passed" = true ]; then
        log "Cleanup verification passed"
    else
        warn "Cleanup verification failed"
        jq -r '.cleanup.details | to_entries | .[] | "- \(.key): \(.value)"' "$report_file"
    fi
}

# Main execution
main() {
    log "Starting test environment cleanup..."

    # Clean up resources
    cleanup_codespaces
    cleanup_networks
    cleanup_volumes
    cleanup_files
    cleanup_branch

    # Verify cleanup
    verify_cleanup

    log "Test environment cleanup completed"
}

# Run main function
main 