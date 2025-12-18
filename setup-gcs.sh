#!/bin/bash
#
# ACF REST API Extended - GCS Auto-Update Setup Script
# 
# Usage: ./setup-gcs.sh YOUR_BUCKET_NAME
#
# This script will:
# 1. Create a GCS bucket
# 2. Set public access
# 3. Create folder structure
# 4. Upload initial files
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if bucket name provided
if [ -z "$1" ]; then
    echo -e "${RED}❌ Error: Please provide bucket name${NC}"
    echo ""
    echo "Usage: ./setup-gcs.sh YOUR_BUCKET_NAME [REGION]"
    echo ""
    echo "Example:"
    echo "  ./setup-gcs.sh my-wp-plugins"
    echo "  ./setup-gcs.sh my-wp-plugins asia-southeast1"
    exit 1
fi

BUCKET_NAME=$1
REGION=${2:-"asia-southeast1"}
PLUGIN_SLUG="acf-rest-api"

echo ""
echo "========================================"
echo "  ACF REST API - GCS Setup"
echo "========================================"
echo ""
echo "Bucket: ${BUCKET_NAME}"
echo "Region: ${REGION}"
echo "Plugin: ${PLUGIN_SLUG}"
echo ""

# Check if gcloud is installed
if ! command -v gcloud &> /dev/null; then
    echo -e "${RED}❌ Error: gcloud CLI not found${NC}"
    echo "Please install Google Cloud SDK: https://cloud.google.com/sdk/docs/install"
    exit 1
fi

# Check if logged in
if ! gcloud auth list --filter=status:ACTIVE --format="value(account)" | head -n1 > /dev/null 2>&1; then
    echo -e "${YELLOW}⚠️  Not logged in to gcloud. Running 'gcloud auth login'...${NC}"
    gcloud auth login
fi

# Get current project
PROJECT=$(gcloud config get-value project 2>/dev/null)
if [ -z "$PROJECT" ]; then
    echo -e "${RED}❌ Error: No GCP project set${NC}"
    echo "Run: gcloud config set project YOUR_PROJECT_ID"
    exit 1
fi

echo "Project: ${PROJECT}"
echo ""

# Step 1: Create bucket
echo -e "${YELLOW}📦 Step 1: Creating bucket...${NC}"
if gsutil ls -b gs://${BUCKET_NAME} &> /dev/null; then
    echo -e "${GREEN}✅ Bucket already exists${NC}"
else
    gsutil mb -l ${REGION} -p ${PROJECT} gs://${BUCKET_NAME}
    echo -e "${GREEN}✅ Bucket created${NC}"
fi

# Step 2: Set public access
echo ""
echo -e "${YELLOW}🔓 Step 2: Setting public access...${NC}"
gsutil iam ch allUsers:objectViewer gs://${BUCKET_NAME}
echo -e "${GREEN}✅ Public access configured${NC}"

# Step 3: Create folder structure
echo ""
echo -e "${YELLOW}📁 Step 3: Creating folder structure...${NC}"
echo "placeholder" | gsutil cp - gs://${BUCKET_NAME}/${PLUGIN_SLUG}/.placeholder
gsutil rm gs://${BUCKET_NAME}/${PLUGIN_SLUG}/.placeholder 2>/dev/null || true
echo -e "${GREEN}✅ Folder structure ready${NC}"

# Step 4: Update local files
echo ""
echo -e "${YELLOW}📝 Step 4: Updating local configuration files...${NC}"

# Update acf-rest-api.php
if [ -f "acf-rest-api.php" ]; then
    sed -i "s|YOUR_BUCKET_NAME|${BUCKET_NAME}|g" acf-rest-api.php
    echo -e "${GREEN}✅ Updated acf-rest-api.php${NC}"
else
    echo -e "${YELLOW}⚠️  acf-rest-api.php not found (run from plugin directory)${NC}"
fi

# Update plugin-info.json
if [ -f "plugin-info.json" ]; then
    sed -i "s|YOUR_BUCKET_NAME|${BUCKET_NAME}|g" plugin-info.json
    echo -e "${GREEN}✅ Updated plugin-info.json${NC}"
else
    echo -e "${YELLOW}⚠️  plugin-info.json not found${NC}"
fi

# Step 5: Display Cloud Build trigger info
echo ""
echo "========================================"
echo -e "${GREEN}🎉 GCS Setup Complete!${NC}"
echo "========================================"
echo ""
echo "Next steps:"
echo ""
echo "1. Create Cloud Build Trigger:"
echo "   - Go to: https://console.cloud.google.com/cloud-build/triggers"
echo "   - Connect your repository"
echo "   - Create trigger with these substitutions:"
echo "     _BUCKET: ${BUCKET_NAME}"
echo "     _PLUGIN_SLUG: ${PLUGIN_SLUG}"
echo ""
echo "2. Grant Cloud Build permissions:"
echo "   PROJECT_NUMBER=\$(gcloud projects describe ${PROJECT} --format='value(projectNumber)')"
echo "   gcloud storage buckets add-iam-policy-binding gs://${BUCKET_NAME} \\"
echo "     --member=\"serviceAccount:\${PROJECT_NUMBER}@cloudbuild.gserviceaccount.com\" \\"
echo "     --role=\"roles/storage.objectAdmin\""
echo ""
echo "3. Push your code to trigger the first build"
echo ""
echo "URLs after deployment:"
echo "  • Plugin ZIP: https://storage.googleapis.com/${BUCKET_NAME}/${PLUGIN_SLUG}/${PLUGIN_SLUG}-VERSION.zip"
echo "  • Update Info: https://storage.googleapis.com/${BUCKET_NAME}/${PLUGIN_SLUG}/plugin-info.json"
echo ""
