#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Function to check if GitHub CLI is installed
check_gh_cli() {
    if ! command -v gh &> /dev/null; then
        echo -e "${RED}GitHub CLI is not installed. Please install it first.${NC}"
        exit 1
    fi
}

# Function to authenticate with GitHub
authenticate_github() {
    if ! gh auth status &> /dev/null; then
        echo -e "${YELLOW}Authenticating with GitHub...${NC}"
        gh auth login --web
    fi
}

# Function to create a new Codespace
create_codespace() {
    echo -e "${YELLOW}Creating new Codespace...${NC}"
    gh codespace create --repo $(gh repo view --json nameWithOwner -q .nameWithOwner) --branch main
}

# Function to list existing Codespaces
list_codespaces() {
    echo -e "${YELLOW}Listing existing Codespaces...${NC}"
    gh codespace list
}

# Function to rebuild a Codespace
rebuild_codespace() {
    local codespace_name=$1
    if [ -z "$codespace_name" ]; then
        echo -e "${RED}Please provide a Codespace name${NC}"
        exit 1
    fi
    echo -e "${YELLOW}Rebuilding Codespace: $codespace_name${NC}"
    gh codespace rebuild $codespace_name
}

# Function to run tests in Codespace
run_tests() {
    local codespace_name=$1
    if [ -z "$codespace_name" ]; then
        echo -e "${RED}Please provide a Codespace name${NC}"
        exit 1
    fi
    echo -e "${YELLOW}Running tests in Codespace: $codespace_name${NC}"
    gh codespace ssh $codespace_name --command "composer test"
}

# Main script
check_gh_cli
authenticate_github

case "$1" in
    "create")
        create_codespace
        ;;
    "list")
        list_codespaces
        ;;
    "rebuild")
        rebuild_codespace $2
        ;;
    "test")
        run_tests $2
        ;;
    *)
        echo -e "${YELLOW}Usage:${NC}"
        echo "  $0 create    - Create a new Codespace"
        echo "  $0 list      - List existing Codespaces"
        echo "  $0 rebuild <codespace> - Rebuild a specific Codespace"
        echo "  $0 test <codespace>    - Run tests in a specific Codespace"
        ;;
esac 