#!/bin/bash

###############################################################################
#  APK GENERATOR SCRIPT FOR FEE MANAGEMENT SYSTEM
#  Quick automated script to generate APK using Bubblewrap CLI
###############################################################################

echo "═══════════════════════════════════════════════════════════════"
echo "   📱 Fee Management System - APK Generator"
echo "═══════════════════════════════════════════════════════════════"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if Node.js is installed
echo -e "${BLUE}➤ Checking prerequisites...${NC}"
if ! command -v node &> /dev/null; then
    echo -e "${RED}✗ Node.js is not installed${NC}"
    echo ""
    echo "Please install Node.js first:"
    echo "  macOS: brew install node"
    echo "  Windows: Download from https://nodejs.org"
    echo "  Linux: sudo apt install nodejs npm"
    exit 1
fi

echo -e "${GREEN}✓ Node.js is installed${NC}"

# Check if bubblewrap is installed
if ! command -v bubblewrap &> /dev/null; then
    echo -e "${YELLOW}! Bubblewrap CLI not found${NC}"
    echo -e "${BLUE}➤ Installing Bubblewrap CLI...${NC}"
    npm install -g @bubblewrap/cli

    if [ $? -ne 0 ]; then
        echo -e "${RED}✗ Failed to install Bubblewrap${NC}"
        exit 1
    fi

    echo -e "${GREEN}✓ Bubblewrap CLI installed${NC}"
else
    echo -e "${GREEN}✓ Bubblewrap CLI is installed${NC}"
fi

echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "   🔧 Configuration"
echo "═══════════════════════════════════════════════════════════════"
echo ""

# Get domain from user
read -p "Enter your domain (e.g., myschool.com): " DOMAIN
if [ -z "$DOMAIN" ]; then
    echo -e "${RED}✗ Domain is required${NC}"
    exit 1
fi

# Get app name
read -p "Enter app name (default: Fee Management System): " APP_NAME
APP_NAME=${APP_NAME:-"Fee Management System"}

# Get package name
echo ""
echo "Package name format: com.yourschool.feemgmt"
read -p "Enter package name (e.g., com.stjohns.feemgmt): " PACKAGE_NAME
if [ -z "$PACKAGE_NAME" ]; then
    echo -e "${RED}✗ Package name is required${NC}"
    exit 1
fi

# Validate package name format
if [[ ! $PACKAGE_NAME =~ ^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$ ]]; then
    echo -e "${RED}✗ Invalid package name format${NC}"
    echo "Use format: com.company.appname (lowercase, dots, no spaces)"
    exit 1
fi

echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "   📋 Summary"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "  Domain:       https://$DOMAIN"
echo "  App Name:     $APP_NAME"
echo "  Package Name: $PACKAGE_NAME"
echo "  Manifest URL: https://$DOMAIN/manifest.json"
echo ""
read -p "Continue? (y/n): " CONFIRM

if [ "$CONFIRM" != "y" ] && [ "$CONFIRM" != "Y" ]; then
    echo -e "${YELLOW}✗ Cancelled${NC}"
    exit 0
fi

# Create project directory
PROJECT_DIR="fee-management-apk"
echo ""
echo -e "${BLUE}➤ Creating project directory...${NC}"
mkdir -p "$PROJECT_DIR"
cd "$PROJECT_DIR"

# Initialize bubblewrap project
echo -e "${BLUE}➤ Initializing Bubblewrap project...${NC}"
echo ""

bubblewrap init \
    --manifest "https://$DOMAIN/manifest.json" \
    --directory . \
    --packageId "$PACKAGE_NAME" \
    --name "$APP_NAME"

if [ $? -ne 0 ]; then
    echo ""
    echo -e "${RED}✗ Failed to initialize project${NC}"
    echo ""
    echo "Common issues:"
    echo "  1. Manifest.json not accessible at https://$DOMAIN/manifest.json"
    echo "  2. SSL certificate not enabled"
    echo "  3. Domain not reachable"
    echo ""
    echo "Please fix these issues and try again."
    exit 1
fi

echo ""
echo -e "${GREEN}✓ Project initialized${NC}"

# Build APK
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "   🔨 Building APK"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo -e "${BLUE}➤ Building APK... (this may take 5-10 minutes)${NC}"
echo ""

bubblewrap build

if [ $? -ne 0 ]; then
    echo ""
    echo -e "${RED}✗ Build failed${NC}"
    echo ""
    echo "Check the error messages above for details."
    exit 1
fi

echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "   🎉 SUCCESS!"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo -e "${GREEN}✓ APK built successfully!${NC}"
echo ""
echo "APK Location:"
echo "  $PWD/app/build/outputs/apk/release/app-release-unsigned.apk"
echo ""
echo "Next Steps:"
echo "  1. Transfer APK to your Android phone"
echo "  2. Enable 'Install from unknown sources' in phone settings"
echo "  3. Open APK file to install"
echo "  4. Test all features"
echo ""
echo "For signed APK (for Play Store):"
echo "  Run: bubblewrap build --skipPwaValidation"
echo "  Then sign with your keystore"
echo ""
echo "═══════════════════════════════════════════════════════════════"

# Open output directory
if command -v open &> /dev/null; then
    open app/build/outputs/apk/release/
elif command -v xdg-open &> /dev/null; then
    xdg-open app/build/outputs/apk/release/
fi

echo ""
echo "Press any key to exit..."
read -n 1 -s
