#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
CHECKLISTS_DIR=".codespaces/testing/checklists"
RESULTS_DIR=".codespaces/testing/results"
COMPLETE_DIR=".codespaces/testing/.complete"
SCRIPTS_DIR=".codespaces/testing/scripts"

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

# Create necessary directories
mkdir -p "$RESULTS_DIR" "$COMPLETE_DIR"

# Function to run a single checklist
run_checklist() {
    local checklist_file="$1"
    local checklist_name=$(basename "$checklist_file" .json)
    local result_file="$RESULTS_DIR/${checklist_name}_$(date +%Y%m%d_%H%M%S).json"
    
    log "Running checklist: $checklist_name"
    
    # Read checklist items
    local items=$(jq -r '.items[]' "$checklist_file")
    
    # Process each item
    while IFS= read -r item; do
        local id=$(echo "$item" | jq -r '.id')
        local name=$(echo "$item" | jq -r '.name')
        local method=$(echo "$item" | jq -r '.verification_method')
        
        log "Testing: $name ($id)"
        
        # Run the verification method
        if [[ $method == Command:* ]]; then
            local command=${method#Command: }
            eval "$command" > /dev/null 2>&1
            local status=$?
            
            if [ $status -eq 0 ]; then
                log "✓ $name passed"
                # Update item status
                item=$(echo "$item" | jq '.status = "completed"')
            else
                error "✗ $name failed"
                item=$(echo "$item" | jq '.status = "failed"')
            fi
        else
            warn "Unknown verification method: $method"
            item=$(echo "$item" | jq '.status = "skipped"')
        fi
        
        # Update items array
        items=$(echo "$items" | jq --argjson new_item "$item" 'if .id == $new_item.id then $new_item else . end')
    done <<< "$items"
    
    # Create result file
    jq --argjson items "$items" '.items = $items' "$checklist_file" > "$result_file"
    
    # Check if all items are completed
    local all_completed=$(jq -r '.items | all(.status == "completed")' "$result_file")
    
    if [ "$all_completed" = "true" ]; then
        log "All items completed for $checklist_name"
        mv "$checklist_file" "$COMPLETE_DIR/"
    else
        warn "Some items failed or were skipped for $checklist_name"
    fi
}

# Main execution
main() {
    # Check if jq is installed
    if ! command -v jq &> /dev/null; then
        error "jq is required but not installed"
    fi
    
    # Run each checklist
    for checklist in "$CHECKLISTS_DIR"/*.json; do
        if [ -f "$checklist" ]; then
            run_checklist "$checklist"
        fi
    done
    
    log "All checklists completed"
}

main 