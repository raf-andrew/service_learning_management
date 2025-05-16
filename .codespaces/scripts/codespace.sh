#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
CONFIG_FILE=".codespaces/config/codespaces.json"
STATE_FILE=".codespaces/state/codespaces.json"
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

# Check if GitHub CLI is installed
check_gh_cli() {
    if ! command -v gh &> /dev/null; then
        error "GitHub CLI is not installed. Please install it first: https://cli.github.com/"
    fi
}

# Check GitHub authentication
check_auth() {
    if ! gh auth status &> /dev/null; then
        log "GitHub authentication required"
        gh auth login --web
    fi
}

# Update state file
update_state() {
    local key=$1
    local value=$2
    jq "$key = $value" "$STATE_FILE" > tmp.$$.json && mv tmp.$$.json "$STATE_FILE"
}

# Get Codespace status
get_codespace_status() {
    local env=$1
    jq -r ".environments.$env.status" "$STATE_FILE"
}

# Create Codespace
create_codespace() {
    local env=$1
    local config=$(jq -r ".environments.$env" "$CONFIG_FILE")
    
    log "Creating Codespace for environment: $env"
    
    # Create Codespace using GitHub CLI
    gh codespace create \
        --repo "$(jq -r '.github.repository.url' "$STATE_FILE")" \
        --branch "$(jq -r '.github.repository.branch' "$STATE_FILE")" \
        --machine "$(jq -r '.environments.'$env'.machine' "$CONFIG_FILE")" \
        --region "$(jq -r '.defaults.region' "$CONFIG_FILE")" \
        --json id,name,url \
        > codespace.json
    
    # Update state
    local name=$(jq -r '.name' codespace.json)
    local url=$(jq -r '.url' codespace.json)
    
    update_state ".environments.$env.status" "\"created\""
    update_state ".environments.$env.name" "\"$name\""
    update_state ".environments.$env.url" "\"$url\""
    update_state ".environments.$env.created_at" "\"$(date -u +"%Y-%m-%dT%H:%M:%SZ")\""
    
    log "Codespace created: $name"
    log "URL: $url"
}

# Delete Codespace
delete_codespace() {
    local env=$1
    local name=$(jq -r ".environments.$env.name" "$STATE_FILE")
    
    if [ "$name" != "null" ]; then
        log "Deleting Codespace: $name"
        gh codespace delete "$name" --force
        
        # Update state
        update_state ".environments.$env.status" "\"not_created\""
        update_state ".environments.$env.name" "null"
        update_state ".environments.$env.url" "null"
        update_state ".environments.$env.created_at" "null"
        
        log "Codespace deleted"
    else
        warn "No Codespace found for environment: $env"
    fi
}

# Rebuild Codespace
rebuild_codespace() {
    local env=$1
    local name=$(jq -r ".environments.$env.name" "$STATE_FILE")
    
    if [ "$name" != "null" ]; then
        log "Rebuilding Codespace: $name"
        gh codespace rebuild "$name"
        
        # Update state
        update_state ".environments.$env.updated_at" "\"$(date -u +"%Y-%m-%dT%H:%M:%SZ")\""
        
        log "Codespace rebuilt"
    else
        warn "No Codespace found for environment: $env"
    fi
}

# List Codespaces
list_codespaces() {
    log "Listing Codespaces"
    gh codespace list --json name,state,gitStatus,createdAt
}

# Connect to Codespace
connect_codespace() {
    local env=$1
    local name=$(jq -r ".environments.$env.name" "$STATE_FILE")
    
    if [ "$name" != "null" ]; then
        log "Connecting to Codespace: $name"
        gh codespace code "$name"
    else
        warn "No Codespace found for environment: $env"
    fi
}

# Main function
main() {
    local action=$1
    local env=$2
    
    # Check GitHub CLI
    check_gh_cli
    
    # Check authentication
    check_auth
    
    case $action in
        "create")
            create_codespace "$env"
            ;;
        "delete")
            delete_codespace "$env"
            ;;
        "rebuild")
            rebuild_codespace "$env"
            ;;
        "list")
            list_codespaces
            ;;
        "connect")
            connect_codespace "$env"
            ;;
        *)
            error "Unknown action: $action"
            ;;
    esac
}

# Run main function with arguments
main "$@" 