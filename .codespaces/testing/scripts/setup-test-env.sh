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

# Check prerequisites
check_prerequisites() {
    log "Checking prerequisites..."

    # Check GitHub CLI
    if ! command -v gh &> /dev/null; then
        error "GitHub CLI is not installed"
    fi

    # Check Docker
    if ! command -v docker &> /dev/null; then
        error "Docker is not installed"
    fi

    # Check environment variables
    if [ -z "$TEST_REPO" ] || [ -z "$TEST_BRANCH" ] || [ -z "$TEST_TOKEN" ]; then
        error "Required environment variables are not set"
    fi

    log "Prerequisites check passed"
}

# Create test branch
create_test_branch() {
    log "Creating test branch..."

    # Check if branch exists
    if git show-ref --verify --quiet refs/heads/$TEST_BRANCH; then
        warn "Test branch already exists, switching to it"
        git checkout $TEST_BRANCH
    else
        git checkout -b $TEST_BRANCH
    fi

    log "Test branch created/checked out"
}

# Set up test configuration
setup_test_config() {
    log "Setting up test configuration..."

    # Create test configuration
    cat > $CONFIG_DIR/test-codespaces.json << EOF
{
    "name": "test-codespace",
    "version": "1.0.0",
    "description": "Test Codespace Configuration",
    "defaults": {
        "region": "$TEST_REGION",
        "machine": "$TEST_MACHINE",
        "branch": "$TEST_BRANCH",
        "ports": [8000, 3306, 6379],
        "features": {
            "docker": true,
            "github-cli": true,
            "node": true
        }
    },
    "environments": {
        "test": {
            "name": "test",
            "machine": "$TEST_MACHINE",
            "ports": [8000, 3306, 6379],
            "features": {
                "docker": true,
                "github-cli": true,
                "node": true
            }
        }
    }
}
EOF

    log "Test configuration created"
}

# Initialize test state
init_test_state() {
    log "Initializing test state..."

    # Create test state file
    cat > $STATE_DIR/test-state.json << EOF
{
    "version": "1.0.0",
    "last_updated": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
    "environments": {
        "test": {
            "status": "not_created",
            "name": null,
            "url": null,
            "created_at": null,
            "updated_at": null,
            "services": {
                "app": {
                    "status": "not_running",
                    "container_id": null,
                    "ports": {},
                    "last_started": null
                },
                "mysql": {
                    "status": "not_running",
                    "container_id": null,
                    "ports": {},
                    "last_started": null
                },
                "redis": {
                    "status": "not_running",
                    "container_id": null,
                    "ports": {},
                    "last_started": null
                }
            },
            "networks": {
                "default": {
                    "status": "not_created",
                    "id": null,
                    "created_at": null
                }
            },
            "volumes": {
                "mysql_data": {
                    "status": "not_created",
                    "id": null,
                    "created_at": null
                },
                "redis_data": {
                    "status": "not_created",
                    "id": null,
                    "created_at": null
                }
            }
        }
    },
    "github": {
        "auth": {
            "status": "not_authenticated",
            "token": null,
            "token_expires_at": null,
            "user": null
        },
        "repository": {
            "name": "$TEST_REPO",
            "owner": null,
            "branch": "$TEST_BRANCH",
            "url": null
        }
    }
}
EOF

    log "Test state initialized"
}

# Set up test credentials
setup_test_credentials() {
    log "Setting up test credentials..."

    # Create test credentials file
    cat > $TEST_DIR/credentials.json << EOF
{
    "github": {
        "token": "$TEST_TOKEN",
        "expires_at": "$(date -u -d "+1 day" +"%Y-%m-%dT%H:%M:%SZ")"
    },
    "docker": {
        "registry": "ghcr.io",
        "username": "$(echo $TEST_REPO | cut -d'/' -f1)",
        "token": "$TEST_TOKEN"
    }
}
EOF

    # Set secure permissions
    chmod 600 $TEST_DIR/credentials.json

    log "Test credentials set up"
}

# Main execution
main() {
    log "Starting test environment setup..."

    # Check prerequisites
    check_prerequisites

    # Create test branch
    create_test_branch

    # Set up test configuration
    setup_test_config

    # Initialize test state
    init_test_state

    # Set up test credentials
    setup_test_credentials

    log "Test environment setup completed"
}

# Run main function
main 