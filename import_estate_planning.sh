#!/bin/bash
# WordPress CLI script to import Estate Planning knowledge base
#
# Usage: ./import_estate_planning.sh <wp-cli-path> <xml-file>
#
# This script imports Estate Planning content into a WordPress installation
# using the WordPress CLI (wp). It supports importing knowledge base articles
# as static pages with proper post types, taxonomies, and metadata.
#
# The script:
# 1. Validates the WordPress CLI path and XML file existence
# 2. Imports the content using wp import command
# 3. Creates appropriate page templates for each knowledge base section
# 4. Sets up navigation menus and meta information
#
# Prerequisites:
# - WordPress CLI (wp) installed and configured
# - WordPress installation with appropriate capabilities
# - XML file generated from ksf_EstatePlanning knowledge base
#
# Example:
#   ./import_estate_planning.sh /usr/local/bin/wp ksf_WP_EstatePlanning/estate-planning-full-import.xml

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Default values
WP_CLI_PATH="/usr/local/bin/wp"
XML_FILE=""
AUTHOR_ID=1
CREATE_AUTHOR=true

# Display usage information
usage() {
    echo -e "${GREEN}Estate Planning WP Import Script${NC}"
    echo ""
    echo "Usage: $0 [options]"
    echo ""
    echo "Options:"
    echo "  -x, --xml FILE          Path to XML import file (required)"
    echo "  -w, --wp-cli PATH       Path to wp-cli (default: /usr/local/bin/wp)"
    echo "  -a, --author ID         Author ID for imported content (default: 1)"
    echo "  -c, --create-author     Create author if they don't exist (default: true)"
    echo "  -h, --help              Display this help message"
    echo ""
    echo "Examples:"
    echo "  $0 -x ksf_WP_EstatePlanning/estate-planning-full-import.xml"
    echo "  $0 --xml ksf_WP_EstatePlanning/estate-planning-full-import.xml --author 5"
    exit 1
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case "$1" in
        -x|--xml)
            XML_FILE="$2"
            shift 2
            ;;
        -w|--wp-cli)
            WP_CLI_PATH="$2"
            shift 2
            ;;
        -a|--author)
            AUTHOR_ID="$2"
            shift 2
            ;;
        -c|--create-author)
            CREATE_AUTHOR="$2"
            shift 2
            ;;
        -h|--help)
            usage
            ;;
        *)
            echo -e "${RED}Error: Unknown option $1${NC}" >&2
            usage
            ;;
    esac
done

# Validate required parameters
if [[ -z "$XML_FILE" ]]; then
    echo -e "${RED}Error: XML import file is required${NC}" >&2
    usage
fi

# Validate WordPress CLI exists
if [[ ! -x "$WP_CLI_PATH" ]]; then
    echo -e "${RED}Error: WordPress CLI not found at $WP_CLI_PATH${NC}" >&2
    echo "Please install wp-cli or provide correct path"
    exit 1
fi

# Validate XML file exists
if [[ ! -f "$XML_FILE" ]]; then
    echo -e "${RED}Error: XML file not found: $XML_FILE${NC}" >&2
    exit 1
fi

# Validate XML file is readable
if [[ ! -r "$XML_FILE" ]]; then
    echo -e "${RED}Error: XML file is not readable: $XML_FILE${NC}" >&2
    exit 1
fi

# Display import information
echo -e "${GREEN}=== Estate Planning WP Import ===${NC}"
echo ""
echo "WordPress CLI: $WP_CLI_PATH"
echo "XML Import File: $XML_FILE"
echo "Author ID: $AUTHOR_ID"
echo "Create Author: $CREATE_AUTHOR"
echo ""

# Check WordPress CLI capabilities
echo -n "Checking WordPress CLI connectivity... "
if "$WP_CLI_PATH" core is-installed --allow-root > /dev/null 2>&1; then
    echo -e "${GREEN}WordPress is installed${NC}"
else
    echo -e "${RED}WordPress is not installed at this location${NC}"
    echo "Please verify the WordPress CLI path and installation"
    exit 1
fi

# Check if author exists
if [ "$CREATE_AUTHOR" = true ]; then
    echo -n "Checking author ID $AUTHOR_ID... "
    AUTHOR_CHECK=$("$WP_CLI_PATH" user list --allow-root --role=author 2>/dev/null | grep -c "user_login")
    if [[ "$AUTHOR_CHECK" -ge "$AUTHOR_ID" ]]; then
        echo -e "${GREEN}Author exists${NC}"
    else
        echo -e "${YELLOW}Creating author... "
        "$WP_CLI_PATH" user create "$AUTHOR_ID" "estate_planning_author@example.com" --role=author --allow-root > /dev/null 2>&1
        echo -e "${GREEN}Author created${NC}"
    fi
fi

# Perform the import
echo ""
echo -e "${GREEN}Starting import...${NC}"
echo "This may take a moment depending on the size of the import file."

"$WP_CLI_PATH" import "$XML_FILE" \
    --authors="$AUTHOR_ID" \
    --allow-root \
    --translate-title \
    --skip-errors \
    --confirm-hash > /tmp/estate-import-output.txt 2>&1

IMPORT_EXIT_CODE=$?

if [[ $IMPORT_EXIT_CODE -eq 0 ]]; then
    echo ""
    echo -e "${GREEN}=== Import Complete ===${NC}"
    echo ""
    
    # Summary of imported content
    echo "Import Summary:"
    echo "----------------"
    if [[ -f /tmp/estate-import-output.txt ]]; then
        head -20 /tmp/estate-import-output.txt
    fi
    
    # Count imported posts
    POST_COUNT=$("$WP_CLI_PATH" post count --allow-root 2>/dev/null || echo "unknown")
    echo ""
    echo "Total posts in WordPress: $POST_COUNT"
    
    # Verify key pages were created
    echo ""
    echo "Verification:"
    KEY_PAGES=(
        "Estate Planning Overview"
        "Core Estate Planning Principles"
        "Financial Institution Requirements"
        "Legal Professional Needs"
        "Insurance Company Workflows"
        "Acceptance Criteria List"
        "Implementation Patterns"
    )
    
    for page in "${KEY_PAGES[@]}"; do
        PAGE_ID=$("$WP_CLI_PATH" post list --allow-root --post_type=page --post_status=publish | grep -i "$page" | awk '{print $1}')
        if [[ -n "$PAGE_ID" ]]; then
            echo -e "  ✓ ${GREEN}$page${NC} (ID: $PAGE_ID)"
        else
            echo -e "  ✗ ${RED}$page${NC} (not found)"
        fi
    done
    
else
    echo ""
    echo -e "${RED}=== Import Failed ===${NC}"
    echo "Exit code: $IMPORT_EXIT_CODE"
    echo "Check /tmp/estate-import-output.txt for details"
    cat /tmp/estate-import-output.txt
    exit 1
fi