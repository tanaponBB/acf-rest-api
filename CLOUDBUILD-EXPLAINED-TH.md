# อธิบาย cloudbuild.yaml แบบละเอียด

## cloudbuild.yaml คืออะไร?

`cloudbuild.yaml` คือไฟล์ config ที่บอก Google Cloud Build ว่าต้องทำอะไรบ้างเมื่อมีการ push code ขึ้น GitHub

---

## โครงสร้างไฟล์

```yaml
steps:           # ขั้นตอนที่ต้องทำ (ทำทีละ step ตามลำดับ)
  - name: ...    # Step 1
  - name: ...    # Step 2

substitutions:   # ตัวแปรที่ใช้ในไฟล์ (ตั้งค่าได้ใน Cloud Build Console)
  _BUCKET: '...'
  _PLUGIN_SLUG: '...'

options:         # ตั้งค่าเพิ่มเติม
  logging: ...

timeout: '600s'  # เวลา timeout (10 นาที)
```

---

## อธิบายทีละบรรทัด

```yaml
steps:
```
**คำอธิบาย:** เริ่มต้นกำหนด steps ที่ Cloud Build จะทำ

---

### Step 1: สร้าง ZIP File

```yaml
  - name: 'ubuntu'
```
**คำอธิบาย:** ใช้ Docker image ชื่อ `ubuntu` เป็นเครื่องที่จะรัน commands

---

```yaml
    entrypoint: 'bash'
```
**คำอธิบาย:** ใช้ `bash` เป็นตัวรัน commands (เหมือนเปิด Terminal)

---

```yaml
    args:
      - '-c'
      - |
```
**คำอธิบาย:** 
- `-c` = บอก bash ว่าจะรับ command เป็น string
- `|` = เริ่มต้น multi-line string (commands หลายบรรทัด)

---

```yaml
        apt-get update && apt-get install -y zip jq
```
**คำอธิบาย:** 
- `apt-get update` = อัปเดต package list ของ Ubuntu
- `apt-get install -y zip jq` = ติดตั้งโปรแกรม:
  - `zip` = สำหรับสร้างไฟล์ ZIP
  - `jq` = สำหรับแก้ไขไฟล์ JSON
- `-y` = ตอบ yes อัตโนมัติ (ไม่ต้องรอ confirm)

---

```yaml
        VERSION=$$(grep -oP "Version:\s*\K[0-9.]+" ${_PLUGIN_SLUG}.php | head -1)
```
**คำอธิบาย:** ดึงเลข version จากไฟล์ PHP

| ส่วน | ความหมาย |
|------|----------|
| `VERSION=$$()` | เก็บผลลัพธ์ใส่ตัวแปร VERSION (`$$` ใช้ใน Cloud Build แทน `$`) |
| `grep -oP` | ค้นหาข้อความด้วย regex |
| `"Version:\s*\K[0-9.]+"` | หา pattern "Version: X.X.X" แล้วเอาเฉพาะตัวเลข |
| `${_PLUGIN_SLUG}.php` | ชื่อไฟล์ (acf-rest-api.php) |
| `head -1` | เอาแค่บรรทัดแรก |

**ตัวอย่าง:**
```
ไฟล์ acf-rest-api.php มี:
* Version: 1.3.3

ผลลัพธ์: VERSION = "1.3.3"
```

---

```yaml
        echo "========================================="
        echo "Building version: $$VERSION"
        echo "========================================="
```
**คำอธิบาย:** แสดงข้อความใน log ว่ากำลัง build version อะไร

---

```yaml
        mkdir -p build/${_PLUGIN_SLUG}
```
**คำอธิบาย:** สร้าง folder สำหรับเก็บไฟล์ที่จะ zip

| ส่วน | ความหมาย |
|------|----------|
| `mkdir -p` | สร้าง folder (ถ้ามีอยู่แล้วไม่ error) |
| `build/${_PLUGIN_SLUG}` | path = `build/acf-rest-api/` |

---

```yaml
        cp ${_PLUGIN_SLUG}.php build/${_PLUGIN_SLUG}/
```
**คำอธิบาย:** copy ไฟล์หลักไปใส่ folder

```
จาก: acf-rest-api.php
ไป:  build/acf-rest-api/acf-rest-api.php
```

---

```yaml
        cp -r includes build/${_PLUGIN_SLUG}/ 2>/dev/null || true
```
**คำอธิบาย:** copy folder `includes` ทั้งหมด

| ส่วน | ความหมาย |
|------|----------|
| `cp -r` | copy แบบ recursive (รวม subfolder) |
| `2>/dev/null` | ถ้ามี error ให้ซ่อนไว้ |
| `\|\| true` | ถ้า copy ไม่สำเร็จ ไม่ต้อง fail (เผื่อไม่มี folder) |

---

```yaml
        cp readme.txt build/${_PLUGIN_SLUG}/ 2>/dev/null || true
```
**คำอธิบาย:** copy ไฟล์ readme.txt (ถ้ามี)

---

```yaml
        cd build
        zip -r ${_PLUGIN_SLUG}.zip ${_PLUGIN_SLUG}
```
**คำอธิบาย:** สร้างไฟล์ ZIP

| ส่วน | ความหมาย |
|------|----------|
| `cd build` | เข้าไปใน folder build |
| `zip -r` | สร้าง ZIP แบบ recursive |
| `${_PLUGIN_SLUG}.zip` | ชื่อไฟล์ = `acf-rest-api.zip` |
| `${_PLUGIN_SLUG}` | folder ที่จะ zip = `acf-rest-api/` |

**ผลลัพธ์:** ได้ไฟล์ `build/acf-rest-api.zip` ที่มีโครงสร้าง:
```
acf-rest-api.zip
└── acf-rest-api/
    ├── acf-rest-api.php
    ├── includes/
    │   ├── class-gtm-tracking.php
    │   └── ...
    └── readme.txt
```

---

```yaml
        echo "ZIP contents:"
        unzip -l ${_PLUGIN_SLUG}.zip
```
**คำอธิบาย:** แสดงรายการไฟล์ใน ZIP (สำหรับ debug)

---

```yaml
        cp ../plugin-info.json . 2>/dev/null || echo '{}' > plugin-info.json
```
**คำอธิบาย:** copy ไฟล์ plugin-info.json มาใน folder build

| ส่วน | ความหมาย |
|------|----------|
| `cp ../plugin-info.json .` | copy จาก root มาที่นี่ |
| `\|\| echo '{}' > plugin-info.json` | ถ้าไม่มีไฟล์ ให้สร้างไฟล์เปล่า |

---

```yaml
        jq --arg v "$$VERSION" \
           --arg url "https://storage.googleapis.com/${_BUCKET}/${_PLUGIN_SLUG}/${_PLUGIN_SLUG}.zip" \
           --arg date "$$(date -u +"%Y-%m-%d %H:%M:%S")" \
           '.version=$$v | .download_url=$$url | .last_updated=$$date' plugin-info.json > tmp.json && mv tmp.json plugin-info.json
```
**คำอธิบาย:** อัปเดตไฟล์ plugin-info.json ด้วยข้อมูลใหม่

| ส่วน | ความหมาย |
|------|----------|
| `jq` | เครื่องมือแก้ไข JSON |
| `--arg v "$$VERSION"` | สร้างตัวแปร v = version ที่ดึงมา |
| `--arg url "..."` | สร้างตัวแปร url = URL ของ ZIP บน GCS |
| `--arg date "..."` | สร้างตัวแปร date = วันที่ปัจจุบัน |
| `.version=$$v` | แก้ไข field version |
| `.download_url=$$url` | แก้ไข field download_url |
| `.last_updated=$$date` | แก้ไข field last_updated |
| `> tmp.json && mv tmp.json plugin-info.json` | เขียนลงไฟล์ใหม่ |

**ก่อน:**
```json
{
  "version": "1.3.2",
  "download_url": "old-url"
}
```

**หลัง:**
```json
{
  "version": "1.3.3",
  "download_url": "https://storage.googleapis.com/tanapon-wp-plugins/acf-rest-api/acf-rest-api.zip",
  "last_updated": "2025-12-23 10:30:00"
}
```

---

```yaml
        echo "Updated plugin-info.json:"
        cat plugin-info.json
```
**คำอธิบาย:** แสดงเนื้อหา plugin-info.json ที่อัปเดตแล้ว (สำหรับ debug)

---

```yaml
        cd ..
```
**คำอธิบาย:** กลับไป folder ก่อนหน้า

---

### Step 2: Upload ไป Google Cloud Storage

```yaml
  - name: 'gcr.io/cloud-builders/gsutil'
```
**คำอธิบาย:** ใช้ Docker image ที่มี `gsutil` (เครื่องมือจัดการ GCS)

---

```yaml
    entrypoint: 'bash'
    args:
      - '-c'
      - |
```
**คำอธิบาย:** รัน bash commands (เหมือน Step 1)

---

```yaml
        echo "Uploading to GCS..."
```
**คำอธิบาย:** แสดงข้อความว่ากำลัง upload

---

```yaml
        gsutil -h "Cache-Control:no-cache, max-age=0" cp build/${_PLUGIN_SLUG}.zip gs://${_BUCKET}/${_PLUGIN_SLUG}/${_PLUGIN_SLUG}.zip
```
**คำอธิบาย:** Upload ไฟล์ ZIP ไป GCS

| ส่วน | ความหมาย |
|------|----------|
| `gsutil` | เครื่องมือจัดการ Google Cloud Storage |
| `-h "Cache-Control:no-cache, max-age=0"` | ตั้งค่าไม่ให้ cache (เพื่อให้เห็น version ใหม่ทันที) |
| `cp` | คำสั่ง copy |
| `build/${_PLUGIN_SLUG}.zip` | ไฟล์ต้นทาง = `build/acf-rest-api.zip` |
| `gs://${_BUCKET}/${_PLUGIN_SLUG}/` | ปลายทาง = `gs://tanapon-wp-plugins/acf-rest-api/` |

---

```yaml
        gsutil -h "Cache-Control:no-cache, max-age=0" -h "Content-Type:application/json" cp build/plugin-info.json gs://${_BUCKET}/${_PLUGIN_SLUG}/plugin-info.json
```
**คำอธิบาย:** Upload ไฟล์ plugin-info.json ไป GCS

| ส่วน | ความหมาย |
|------|----------|
| `-h "Content-Type:application/json"` | บอกว่าเป็นไฟล์ JSON |

---

```yaml
        gsutil acl ch -u AllUsers:R gs://${_BUCKET}/${_PLUGIN_SLUG}/${_PLUGIN_SLUG}.zip || true
```
**คำอธิบาย:** ตั้งค่าให้ไฟล์ ZIP เข้าถึงได้แบบ public

| ส่วน | ความหมาย |
|------|----------|
| `acl ch` | เปลี่ยน Access Control List |
| `-u AllUsers:R` | ให้ทุกคน (AllUsers) อ่านได้ (R = Read) |
| `\|\| true` | ถ้า error ไม่ต้อง fail |

---

```yaml
        gsutil acl ch -u AllUsers:R gs://${_BUCKET}/${_PLUGIN_SLUG}/plugin-info.json || true
```
**คำอธิบาย:** ตั้งค่าให้ไฟล์ plugin-info.json เข้าถึงได้แบบ public

---

```yaml
        echo "========================================="
        echo "Deployment complete!"
        echo "ZIP: https://storage.googleapis.com/${_BUCKET}/${_PLUGIN_SLUG}/${_PLUGIN_SLUG}.zip"
        echo "Info: https://storage.googleapis.com/${_BUCKET}/${_PLUGIN_SLUG}/plugin-info.json"
        echo "========================================="
```
**คำอธิบาย:** แสดง URL ของไฟล์ที่ upload สำเร็จ

---

### Substitutions (ตัวแปร)

```yaml
substitutions:
  _BUCKET: 'tanapon-wp-plugins'
  _PLUGIN_SLUG: 'acf-rest-api'
```
**คำอธิบาย:** กำหนดค่า default ของตัวแปร

| ตัวแปร | ค่า | ใช้ทำอะไร |
|--------|-----|----------|
| `_BUCKET` | `tanapon-wp-plugins` | ชื่อ GCS bucket |
| `_PLUGIN_SLUG` | `acf-rest-api` | ชื่อ plugin |

**หมายเหตุ:** สามารถ override ค่าเหล่านี้ได้ใน Cloud Build Console > Trigger > Substitution Variables

---

### Options

```yaml
options:
  logging: CLOUD_LOGGING_ONLY
```
**คำอธิบาย:** ตั้งค่า logging

| ค่า | ความหมาย |
|-----|----------|
| `CLOUD_LOGGING_ONLY` | เก็บ log ใน Cloud Logging เท่านั้น (ไม่เก็บใน GCS) |

---

### Timeout

```yaml
timeout: '600s'
```
**คำอธิบาย:** กำหนด timeout = 600 วินาที (10 นาที)

ถ้า build ใช้เวลานานกว่านี้จะ fail อัตโนมัติ

---

## สรุป Flow ทั้งหมด

```
┌─────────────────────────────────────────────────────────────┐
│                    Cloud Build ทำงาน                         │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  Step 1: Build (ใช้ Ubuntu container)                        │
│                                                             │
│  1. ติดตั้ง zip และ jq                                       │
│  2. ดึง version จาก acf-rest-api.php → "1.3.3"              │
│  3. สร้าง folder build/acf-rest-api/                        │
│  4. Copy ไฟล์ทั้งหมดไปใส่                                     │
│  5. สร้าง acf-rest-api.zip                                  │
│  6. อัปเดต plugin-info.json ด้วย version ใหม่                │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  Step 2: Upload (ใช้ gsutil container)                       │
│                                                             │
│  1. Upload acf-rest-api.zip → GCS                           │
│  2. Upload plugin-info.json → GCS                           │
│  3. ตั้งค่า public access                                    │
│  4. แสดง URL ที่ upload สำเร็จ                               │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  ผลลัพธ์: ไฟล์บน GCS                                         │
│                                                             │
│  gs://tanapon-wp-plugins/acf-rest-api/                      │
│  ├── acf-rest-api.zip      (plugin ZIP)                     │
│  └── plugin-info.json      (metadata สำหรับ auto-update)    │
│                                                             │
│  URLs:                                                      │
│  • https://storage.googleapis.com/.../acf-rest-api.zip      │
│  • https://storage.googleapis.com/.../plugin-info.json      │
└─────────────────────────────────────────────────────────────┘
```

---

## ตัวอย่างไฟล์เต็ม พร้อม Comments

```yaml
# cloudbuild.yaml - ไฟล์ config สำหรับ Google Cloud Build

steps:
  # ========================================
  # Step 1: สร้าง ZIP file
  # ========================================
  - name: 'ubuntu'                    # ใช้ Ubuntu container
    entrypoint: 'bash'                # รัน bash
    args:
      - '-c'
      - |
        # ติดตั้ง tools ที่จำเป็น
        apt-get update && apt-get install -y zip jq
        
        # ดึง version จากไฟล์ PHP (เช่น "1.3.3")
        VERSION=$$(grep -oP "Version:\s*\K[0-9.]+" ${_PLUGIN_SLUG}.php | head -1)
        echo "Building version: $$VERSION"
        
        # สร้าง folder structure
        mkdir -p build/${_PLUGIN_SLUG}
        
        # Copy ไฟล์ไปใส่ folder
        cp ${_PLUGIN_SLUG}.php build/${_PLUGIN_SLUG}/
        cp -r includes build/${_PLUGIN_SLUG}/ 2>/dev/null || true
        cp readme.txt build/${_PLUGIN_SLUG}/ 2>/dev/null || true
        
        # สร้าง ZIP
        cd build
        zip -r ${_PLUGIN_SLUG}.zip ${_PLUGIN_SLUG}
        
        # อัปเดต plugin-info.json
        cp ../plugin-info.json . 2>/dev/null || echo '{}' > plugin-info.json
        jq --arg v "$$VERSION" \
           --arg url "https://storage.googleapis.com/${_BUCKET}/${_PLUGIN_SLUG}/${_PLUGIN_SLUG}.zip" \
           '.version=$$v | .download_url=$$url' plugin-info.json > tmp.json
        mv tmp.json plugin-info.json

  # ========================================
  # Step 2: Upload ไป Google Cloud Storage
  # ========================================
  - name: 'gcr.io/cloud-builders/gsutil'  # ใช้ gsutil container
    entrypoint: 'bash'
    args:
      - '-c'
      - |
        echo "Uploading to GCS..."
        
        # Upload ZIP (ไม่ cache เพื่อให้เห็น version ใหม่ทันที)
        gsutil -h "Cache-Control:no-cache" \
          cp build/${_PLUGIN_SLUG}.zip \
          gs://${_BUCKET}/${_PLUGIN_SLUG}/
        
        # Upload plugin-info.json
        gsutil -h "Cache-Control:no-cache" \
          -h "Content-Type:application/json" \
          cp build/plugin-info.json \
          gs://${_BUCKET}/${_PLUGIN_SLUG}/
        
        # ตั้งค่า public access
        gsutil acl ch -u AllUsers:R gs://${_BUCKET}/${_PLUGIN_SLUG}/${_PLUGIN_SLUG}.zip
        gsutil acl ch -u AllUsers:R gs://${_BUCKET}/${_PLUGIN_SLUG}/plugin-info.json
        
        echo "Done! Files uploaded to GCS"

# ========================================
# ตัวแปรที่ใช้ในไฟล์
# (สามารถ override ได้ใน Cloud Build Console)
# ========================================
substitutions:
  _BUCKET: 'tanapon-wp-plugins'       # ชื่อ GCS bucket
  _PLUGIN_SLUG: 'acf-rest-api'        # ชื่อ plugin

# ========================================
# ตั้งค่าเพิ่มเติม
# ========================================
options:
  logging: CLOUD_LOGGING_ONLY         # เก็บ log ใน Cloud Logging

timeout: '600s'                       # timeout 10 นาที
```

---

## วิธีใช้งาน

### 1. วางไฟล์ใน root ของ repo

```
your-repo/
├── acf-rest-api.php      ← ไฟล์หลัก (ต้องมี Version: X.X.X)
├── includes/             ← folder ที่มี class files
├── cloudbuild.yaml       ← ไฟล์นี้
├── plugin-info.json      ← metadata
└── readme.txt            ← (optional)
```

### 2. Push ไป GitHub

```bash
git add .
git commit -m "Setup Cloud Build"
git push origin main
```

### 3. Cloud Build จะทำงานอัตโนมัติ

เมื่อ push แล้ว Cloud Build จะ:
1. Clone repo
2. รัน cloudbuild.yaml
3. Upload ไฟล์ไป GCS

### 4. ตรวจสอบผลลัพธ์

```bash
# ดู build log
gcloud builds list --limit=1

# ตรวจสอบไฟล์บน GCS
gsutil ls gs://tanapon-wp-plugins/acf-rest-api/

# ทดสอบ URL
curl https://storage.googleapis.com/tanapon-wp-plugins/acf-rest-api/plugin-info.json
```
