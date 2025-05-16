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

# Verify repository access
verify_repo_access() {
    log "Verifying repository access..."

    # Check if we can access the repository
    if ! gh repo view "$TEST_REPO" &> /dev/null; then
        error "Cannot access repository: $TEST_REPO"
    fi

    # Check if we're on the test branch
    current_branch=$(git rev-parse --abbrev-ref HEAD)
    if [ "$current_branch" != "$TEST_BRANCH" ]; then
        error "Not on test branch: $TEST_BRANCH"
    fi

    log "Repository access verified"
}

# Verify GitHub CLI authentication
verify_github_auth() {
    log "Verifying GitHub CLI authentication..."

    # Check if GitHub CLI is installed
    if ! command -v gh &> /dev/null; then
        error "GitHub CLI is not installed"
    fi

    # Check GitHub authentication
    if ! gh auth status &> /dev/null; then
        warn "GitHub authentication required"
        gh auth login
    fi

    # Verify authentication worked
    if ! gh auth status &> /dev/null; then
        error "GitHub authentication failed"
    fi

    log "GitHub CLI authentication verified"
}

# Verify Codespaces access
verify_codespaces_access() {
    log "Verifying Codespaces access..."

    # Check if we can list Codespaces
    if ! gh codespace list &> /dev/null; then
        error "Cannot access Codespaces"
    fi

    log "Codespaces access verified"
}

# Main verification process
main() {
    verify_github_auth
    verify_repo_access
    verify_codespaces_access
    log "Environment verification complete"
}

main 