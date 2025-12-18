# ACF REST API Extended - Auto-Update Setup Guide

This guide explains how to set up automatic plugin updates using Google Cloud Storage and Cloud Build.

## Overview

The auto-update system works as follows:
1. You push code changes to your Git repository
2. Cloud Build automatically creates a new plugin ZIP
3. The ZIP and update info are uploaded to Google Cloud Storage
4. WordPress sites check GCS for updates and install them automatically

## Prerequisites

- Google Cloud Platform account
- Git repository (GitHub, GitLab, Bitbucket, or Cloud Source Repositories)
- GCP project with billing enabled

---

## Step 1: Create a Google Cloud Storage Bucket

### Via Google Cloud Console

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Navigate to **Cloud Storage > Buckets**
3. Click **Create Bucket**
4. Configure:
   - **Name**: `your-wp-plugins` (must be globally unique)
   - **Location**: Choose a region close to your users
   - **Storage class**: Standard
   - **Access control**: Fine-grained
5. Click **Create**

### Via gcloud CLI

```bash
# Create bucket
gcloud storage buckets create gs://your-wp-plugins --location=us-central1

# Make bucket publicly readable
gcloud storage buckets add-iam-policy-binding gs://your-wp-plugins \
    --member=allUsers \
    --role=roles/storage.objectViewer
```

---

## Step 2: Set Up Cloud Build

### Enable Cloud Build API

```bash
gcloud services enable cloudbuild.googleapis.com
```

### Connect Your Repository

1. Go to [Cloud Build Triggers](https://console.cloud.google.com/cloud-build/triggers)
2. Click **Connect Repository**
3. Select your source (GitHub, Bitbucket, etc.)
4. Authenticate and select your repository
5. Click **Connect**

### Create a Build Trigger

1. Click **Create Trigger**
2. Configure:
   - **Name**: `acf-rest-api-deploy`
   - **Event**: Push to a branch
   - **Branch**: `^main$` or `^master$`
   - **Configuration**: Cloud Build configuration file
   - **Location**: `/cloudbuild.yaml`
3. Add **Substitution Variables**:
   - `_BUCKET`: `tanapon-wp-plugins`
   - `_PLUGIN_SLUG`: `acf-rest-api`
4. Click **Create**

### Grant Cloud Build Permissions

Cloud Build needs permission to write to your bucket:

```bash
# Get your project number
PROJECT_NUMBER=$(gcloud projects describe $(gcloud config get-value project) --format='value(projectNumber)')

# Grant Storage Admin role to Cloud Build service account
gcloud storage buckets add-iam-policy-binding gs://your-wp-plugins \
    --member="serviceAccount:${PROJECT_NUMBER}@cloudbuild.gserviceaccount.com" \
    --role="roles/storage.objectAdmin"
```

---

## Step 3: Configure Your Plugin

### Update the Plugin URL

Edit `acf-rest-api.php` and update the `ACF_REST_API_UPDATE_URL` constant:

```php
if (!defined('ACF_REST_API_UPDATE_URL')) {
    define('ACF_REST_API_UPDATE_URL', 'https://storage.googleapis.com/your-wp-plugins/acf-rest-api/plugin-info.json');
}
```

### Update plugin-info.json

Edit `plugin-info.json` and replace `YOUR_BUCKET_NAME` with your actual bucket name:

```json
{
    "download_url": "https://storage.googleapis.com/your-wp-plugins/acf-rest-api/acf-rest-api-1.0.0.zip",
    "banners": {
        "low": "https://storage.googleapis.com/your-wp-plugins/acf-rest-api/assets/banner-772x250.png"
    }
}
```

---

## Step 4: Deploy Your First Version

### Option A: Automatic (via Git push)

Simply push your code to the main branch:

```bash
git add .
git commit -m "Initial release with auto-updates"
git push origin main
```

Cloud Build will automatically:
1. Extract the version from your plugin header
2. Create a ZIP file
3. Update plugin-info.json
4. Upload everything to GCS

### Option B: Manual Upload

If you want to upload manually first:

```bash
# Create the ZIP
cd /path/to/your/plugin
zip -r acf-rest-api-1.0.0.zip . -x "*.git*" -x "cloudbuild.yaml"

# Upload to GCS
gsutil cp acf-rest-api-1.0.0.zip gs://your-wp-plugins/acf-rest-api/
gsutil cp plugin-info.json gs://your-wp-plugins/acf-rest-api/
```

---

## Step 5: Test the Update System

### Verify Files in GCS

Check that your files are accessible:

```bash
# Test the JSON endpoint
curl https://storage.googleapis.com/your-wp-plugins/acf-rest-api/plugin-info.json

# Test the ZIP is downloadable
curl -I https://storage.googleapis.com/your-wp-plugins/acf-rest-api/acf-rest-api-1.0.0.zip
```

### Test in WordPress

1. Install the plugin on a WordPress site
2. Go to **Plugins** page
3. Click **Check for updates** link next to the plugin
4. If a newer version exists in GCS, you'll see the update notification

---

## Releasing New Versions

To release a new version:

1. **Update version number** in `acf-rest-api.php`:
   ```php
   * Version: 1.1.0
   ```

2. **Update the constant**:
   ```php
   define('ACF_REST_API_VERSION', '1.1.0');
   ```

3. **Update changelog** in `plugin-info.json` (optional - Cloud Build will update version automatically)

4. **Commit and push**:
   ```bash
   git add .
   git commit -m "Release version 1.1.0"
   git push origin main
   ```

Cloud Build will handle the rest!

---

## Folder Structure After Setup

```
your-wp-plugins (GCS Bucket)
└── acf-rest-api/
    ├── plugin-info.json          # Update metadata
    ├── acf-rest-api-1.0.0.zip    # Version 1.0.0
    ├── acf-rest-api-1.1.0.zip    # Version 1.1.0
    ├── acf-rest-api-latest.zip   # Always latest version
    └── assets/                    # Optional: banners & icons
        ├── banner-772x250.png
        ├── banner-1544x500.png
        ├── icon-128x128.png
        └── icon-256x256.png
```

---

## Troubleshooting

### Updates Not Showing

1. **Clear WordPress transients**:
   ```php
   delete_site_transient('update_plugins');
   ```

2. **Check the update URL** is accessible in browser

3. **Verify version comparison** - remote version must be higher than installed

### Cloud Build Failing

1. Check Cloud Build logs in GCP Console
2. Verify substitution variables are set correctly
3. Ensure Cloud Build has storage permissions

### CORS Issues (if using from JS)

Add CORS configuration to your bucket:

```bash
cat > cors.json << 'EOF'
[
  {
    "origin": ["*"],
    "method": ["GET"],
    "responseHeader": ["Content-Type"],
    "maxAgeSeconds": 3600
  }
]
EOF

gsutil cors set cors.json gs://your-wp-plugins
```

---

## Security Considerations

### Private Plugins

For private/paid plugins, you can:

1. **Use signed URLs** instead of public access
2. **Add license key validation** in the updater class
3. **Use Cloud IAM** for access control

### Example: License Key Validation

```php
// In class-plugin-updater.php, modify get_remote_info():
private function get_remote_info($force_refresh = false) {
    $license_key = get_option('acf_rest_api_license_key');
    
    $response = wp_remote_get($this->update_url, [
        'headers' => [
            'X-License-Key' => $license_key,
        ],
    ]);
    // ... rest of the code
}
```

---

## Support

If you encounter issues:
1. Check Cloud Build logs
2. Verify GCS bucket permissions
3. Test URLs directly in browser
4. Enable WordPress debug logging

Happy updating! 🚀
