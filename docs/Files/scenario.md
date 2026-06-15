# File Management — Complete Scenario

> هذا المستند يشرح السيناريو الكامل لإدارة الملفات في النظام: من رفع الملف إلى تحميله، مع توضيح دور كل مكون (بشري أو تقني) في كل خطوة.

---

## 1. Actors (الفاعلون)

| Actor | النوع | الدور |
|-------|-------|-------|
| **Patient** | Human | يرفع ملفاته الطبية، يشاهدها، يحذفها، يطلب رابط تحميل |
| **Doctor** | Human | يشاهد ملفات مرضاه (المعالج أو المشرف)، يطلب رابط تحميل |
| **Supervisor Doctor** | Human | طبيب مشرف لديه صلاحية وصول لملفات المريض (via `doctor_patient.supervision_status = approved`) |
| **Admin** | Human | صلاحية كاملة على جميع الملفات (عرض، تحميل) |
| **FileController** | System (API) | يستقبل الطلبات ويوجهها إلى الـ Actions المناسبة |
| **FileAccessService** | System (CoR) | سلسلة مسؤولية تتحقق من صلاحية الوصول (Owner → TreatingDoctor → Supervisor → Admin) |
| **FileStorageService** | System (Strategy) | يخزن/يسترجع الملفات من القرص عبر الـ Storage Strategy (`LocalFileStorage`) |
| **ChunkStorageService** | System | يدير تخزين القطع (chunks) مؤقتاً ثم تجميعها |
| **Frontend (SPA/Mobile)** | System (Agent) | يتفاعل مع الـ API نيابة عن المستخدم البشري، يدير الـ UI وتحميل الملفات |

---

## 2. Scenario: Upload a File (Direct)

### 2.1 Trigger
المريض يريد رفع تقرير طبي (مثل lab_result) مرتبط بسجل طبي معين.

### 2.2 Flow

```
Patient (Human)
  │
  │ 1. يفتح تطبيق الواجهة ويختار ملف + السجل الطبي + التصنيف
  ▼
Frontend (Agent)
  │
  │ 2. يقرأ الملف محلياً، يتحقق من الحجم (max 20MB)
  │ 3. يرسل POST /api/v1/files (multipart/form-data)
  │    Headers: Authorization: Bearer {access_token}
  │    Body: file, medical_record_id, file_category, checksum (optional)
  ▼
FileController::store()
  │
  │ 4. StoreFileRequest يتحقق من:
  │    - وجود الملف وعدم تجاوز 20MB
  │    - صحة MIME type (من config/files.allowed_mime_types)
  │    - وجود medical_record_id
  │    - صحة file_category (enum)
  │    - checksum إن وُجد (SHA256 hex 64 char)
  │
  │ 5. ينشئ StoreFileData DTO من البيانات المُحقق منها
  │
  ▼
StoreFileAction::execute(StoreFileData)
  │
  │ 6. يولد UUID v7 كاسم ملف: {uuid}.{ext}
  │ 7. يستخدم FileStorageService لاختيار Storage Strategy حسب disk
  │    (حالياً: LocalFileStorage دائماً)
  │
  │ 8. LocalFileStorage::store(tempPath, relativePath)
  │    - ينسخ الملف إلى: storage/app/files/{medicalRecordId}/{uuid}.pdf
  │    - يرجع المسار النسبي: files/{medicalRecordId}/{uuid}.pdf
  │
  │ 9. ينشئ سجل File في DB:
  │    - user_id = Auth::id(), medical_record_id, disk = 'local'
  │    - path = المسار النسبي, original_name, mime_type
  │    - size, file_category, checksum
  │    - upload_status = 'completed', total_chunks = 1
  │
  │ 10. يرجع File model
  ▼
FileResource (Response)
  │
  │ 11. يعرض البيانات مع whenLoaded('downloads') → downloads_count
  ▼
Frontend ← 201 Created (JSON)
  │
  │ 12. يعرض رسالة نجاح للمريض
  ▼
Patient (Human) ← يرى الملف في القائمة
```

### 2.3 Key Decisions
- **لماذا `file_size` بالـ bytes؟** — Laravel validation تستخدم KB، لكن DB يخزن bytes لدقة الحسابات
- **لماذا المسار نسبي؟** — لدعم تغيير storage driver مستقبلاً (S3) دون تغيير المسارات المخزنة
- **لماذا `user_id` = Auth::id()؟** — لربط الملف بمالكه الأصلي (الرافع) بغض النظر عن صلاحية المشاهدة

---

## 3. Scenario: Upload a File (Chunked)

### 3.1 Trigger
المريض يريد رفع ملف كبير (مثلاً 15MB X-Ray image) ويتجاوز حد chunk_size. الشبكة غير مستقرة، لذا يستخدم التحميل المقسم.

### 3.2 Flow

```
Frontend (Agent)
  │
  │ 1. يقرر استخدام chunked upload عندما file_size > 5MB (chunk_size)
  │
  ─── Step A: Init ──────────────────────────────────────────────
  │
  │ 2. يرسل POST /api/v1/files/init (JSON body)
  │    { medical_record_id, file_category, original_name,
  │      mime_type, file_size, checksum? }
  ▼
InitChunkedUploadAction
  │
  │ 3. ينشئ File record مع upload_status = 'uploading'
  │ 4. يرجع { id, upload_status: 'uploading', total_chunks: 0 }
  ▼
Frontend ← 201 Created
  │
  ─── Step B: Upload Chunks (N times) ──────────────────────────
  │
  │ 5. يقسم الملف إلى chunks بحجم 5MB
  │    (chunk_0, chunk_1, ..., chunk_N-1)
  │
  │ لكل chunk:
  │   6. يرسل POST /api/v1/files/{id}/chunk (multipart)
  │      chunk=bin, chunk_index=0-based
  │
  ▼
UploadChunkAction
  │
  │   7. ChunkStorageService::storeChunk(file_id, index, chunk)
  │      - يخزن القطعة في: storage/chunks/{file_id}/chunk_{index}
  │   8. File::increment('total_chunks')
  │
  ▼
Frontend ← 200 OK (كل chunk)
  │
  │ 9. يتابع إلى أن يرفع كل الـ chunks
  │
  ─── Step C: Complete (Assemble) ──────────────────────────────
  │
  │ 10. يرسل POST /api/v1/files/{id}/complete (JSON)
  │     { checksum: "sha256_hex" } (optional)
  ▼
AssembleChunksAction
  │
  │ 11. ChunkStorageService::assembleChunks(file_id, total_chunks)
  │     - يقرأ كل chunk_index بالترتيب
  │     - يدمجها في ملف مؤقت واحد
  │     - يرجع المسار المؤقت
  │
  │ 12. إن وُجد checksum → hash_file('sha256', tempPath) === checksum
  │     → mismatch يرمي ApiServiceException(400)
  │
  │ 13. FileStorageService::store(tempPath, finalRelativePath)
  │     - يخزن الملف النهائي
  │
  │ 14. ChunkStorageService::cleanup(file_id)
  │     - يحذف مجلد chunks/{file_id} بالكامل
  │
  │ 15. يحدّث File record:
  │     path = finalPath, upload_status = 'completed', checksum
  │
  ▼
Frontend ← 200 OK
  │
  │ 16. يعرض رسالة نجاح للمريض
  ▼
Patient (Human)
```

### 3.3 Key Decisions
- **لماذا chunks في مجلد منفصل؟** — لعزل التخزين المؤقت عن الملفات النهائية
- **لماذا التحقق من checksum عند الـ complete؟** — للتأكد من سلامة الملف المجمّع
- **ماذا لو فشلت إحدى الخطوات؟** — الـ Frontend يعيد المحاولة من آخر chunk ناجح (حسب total_chunks في DB)

---

## 4. Scenario: Request & Download File

### 4.1 Trigger
طبيب معالج يريد تحميل تقرير مخبري لمريضه.

### 4.2 Flow (Request Download Link)

```
Doctor (Human)
  │
  │ 1. يضغط على زر "Download" في الواجهة
  ▼
Frontend (Agent)
  │
  │ 2. يرسل POST /api/v1/files/{id}/download-link
  │    Headers: Authorization: Bearer {token}
  ▼
FileController::requestDownloadLink()
  │
  │ 3. يبحث عن File (أو 404)
  ▼
RequestDownloadLinkAction::execute(User, File)
  │
  │ 4. FileAccessService::canAccess(User, File, throw: true)
  │    │
  │    │── Chain of Responsibility:
  │    │    OwnerHandler? → user_id === file->user_id? → YES/NO
  │    │    ↓ (if NO)
  │    │    TreatingDoctorHandler? → medicalRecord->doctor_id === user->doctor->id? → YES/NO
  │    │    ↓ (if NO)
  │    │    SupervisorDoctorHandler? → doctor_patient.supervision_status = approved? → YES/NO
  │    │    ↓ (if NO)
  │    │    AdminHandler? → user has role 'admin'? → YES/NO
  │    │    ↓ (if NO)
  │    │    Throws 403 Forbidden
  │
  │ 5. يولد signed URL:
  │    URL::temporarySignedRoute(
  │      'files.download',
  │      now()->endOfDay(),           // ينتهي بنهاية اليوم
  │      ['file' => $id, 'user' => $user->id]
  │    )
  │
  ▼
Frontend ← 200 OK { url, expires_at }
  │
  │ 6. يستخدم الرابط للتحميل المباشر (أو يفتحه في نافذة جديدة / متصفح)
  ▼
Doctor (Human) ← لديه رابط قابل للتحميل حتى نهاية اليوم
```

### 4.3 Flow (Actual Download via Signed URL)

```
Doctor's Browser / Client
  │
  │ 1. يفتح الرابط المُوقّع (GET /files/{id}/download?expires=...&signature=...&user=...)
  ▼
Laravel ValidateSignature Middleware
  │
  │ 2. يتحقق من صحة التوقيع وعدم انتهاء الصلاحية
  │    → فشل = 403 Invalid signature
  │
  │ 3. يمرر إلى FileController::download()
  ▼
FileController::download(File, Request)
  │
  │ 4. يبحث عن File (أو 404)
  │ 5. يتأكد من وجود user_id في الـ URL
  │ 6. FileStorageService::disk('local')::retrieve($file->path)
  │    → يرجع full path: storage/app/files/{medicalRecordId}/{uuid}.pdf
  │
  │ 7. ينشئ BinaryFileResponse:
  │    - Content-Type: file->mime_type
  │    - Content-Length: file->size
  │    - Accept-Ranges: bytes  ← يدعم الاستئناف
  │    - Content-Disposition: attachment; filename="original_name"
  │
  │ 8. يسجل التحميل في FileDownload:
  │    file_id, user_id, ip_address, user_agent, downloaded_at
  │
  ▼
Client ← 200 OK (with binary) or 206 Partial Content (if Range header)
  │
  │ 9. إذا كان الطلب يحمل Range header (مثلاً iOS NSURLSession, curl):
  │    → Laravel يتعامل مع partial content تلقائياً
  │    → 206 Partial Content + Content-Range
  ▼
Doctor (Human) ← يستلم الملف
```

### 4.4 Key Decisions
- **لماذا signed URL وليس تحميل مباشر؟** — الأمان: لا يمكن لأحد تحميل ملف دون صلاحية + صلاحية زمنية محدودة
- **لماذا `Accept-Ranges: bytes`؟** — لدعم استئناف التحميل على iOS والشبكات غير المستقرة
- **لماذا `user` في signed URL؟** — لتسجيل من قام بالتحميل في جدول `file_downloads`
- **لماذا `endOfDay()` كوقت انتهاء؟** — المستخدم يحتاج صلاحية ليوم كامل، لكنها تنتهي تلقائياً بعدها

---

## 5. Access Control Matrix (مصفوفة الصلاحيات)

| Action | Patient (Owner) | Treating Doctor | Supervisor Doctor | Admin | Stranger |
|--------|:---:|:---:|:---:|:---:|:---:|
| Upload (`POST /files`) | ✅ | ❌ | ❌ | ✅ | ❌ |
| List (`GET /files`) | ✅ (own) | ✅ (patients) | ✅ | ✅ (all) | ❌ |
| Show (`GET /files/{id}`) | ✅ | ✅ | ✅ | ✅ | ❌ |
| Delete (`DELETE /files/{id}`) | ✅ (owner only) | ❌ | ❌ | ❌ | ❌ |
| Request Download Link (`POST /.../download-link`) | ✅ | ✅ | ✅ | ✅ | ❌ |
| Download (signed) | ✅ | ✅ | ✅ | ✅ | ❌ |

---

## 6. Storage Layout

```
storage/app/
├── files/                          ← الملفات النهائية
│   └── {medical_record_id}/
│       └── {file_uuid}.pdf
│       └── {file_uuid}.jpg
│
└── chunks/                         ← القطع المؤقتة (تُحذف بعد التجميع)
    └── {file_uuid}/
        ├── chunk_0
        ├── chunk_1
        └── ...
```

---

## 7. Error Scenarios

| الخطأ | المكان | الاستجابة |
|-------|--------|-----------|
| ملف بدون صلاحية | `RequestDownloadLinkAction` / `ShowAction` | 403 Forbidden |
| توقيع Signed URL منتهٍ أو مزوّر | `ValidateSignature` Middleware | 403 Invalid signature |
| حجم ملف يتجاوز 20MB | `StoreFileRequest` / `InitUploadRequest` | 422 Validation error |
| MIME type غير مسموح | `StoreFileRequest` | 422 Validation error |
| Checksum mismatch | `AssembleChunksAction` | 400 Checksum mismatch |
| ملف غير موجود | `FileController` (show/delete/download) | 404 Not Found |
| Medical Record غير موجود | `StoreFileRequest` | 422 Validation error |
| التحميل بدون تسجيل الدخول | `auth:api` Middleware | 401 Unauthenticated |
| المستخدم غير نشط | `active` Middleware | 403 Account inactive |

---

## 8. System Component Interaction Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        Frontend (SPA/Mobile)                    │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌────────────────┐  │
│  │ Upload   │  │ Chunk    │  │ File     │  │ Download       │  │
│  │ Manager  │  │ Manager  │  │ List     │  │ Handler        │  │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └───────┬────────┘  │
│       │              │              │                │           │
└───────┼──────────────┼──────────────┼────────────────┼───────────┘
        │              │              │                │
        ▼              ▼              ▼                ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Laravel API (Server)                        │
│                                                                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                   FileController                         │  │
│  │  store()  init()  uploadChunk()  complete()  index()     │  │
│  │  show()   destroy()  requestDownloadLink()  download()   │  │
│  └─────┬─────────┬──────────┬──────────┬──────────┬─────────┘  │
│        │         │          │          │          │            │
│        ▼         ▼          ▼          ▼          ▼            │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────────┐ ┌──────────┐ │
│  │Store   │ │Init    │ │Upload  │ │Assemble    │ │Delete    │ │
│  │File    │ │Chunked │ │Chunk   │ │Chunks      │ │File      │ │
│  │Action  │ │Upload  │ │Action  │ │Action      │ │Action    │ │
│  │        │ │Action  │ │        │ │            │ │          │ │
│  └───┬────┘ └───┬────┘ └───┬────┘ └─────┬──────┘ └────┬─────┘ │
│      │          │          │            │             │       │
│      ▼          ▼          ▼            ▼             ▼       │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │              Services Layer                              │  │
│  │  ┌──────────────────┐  ┌──────────────────┐              │  │
│  │  │ FileStorageService│  │ ChunkStorage     │              │  │
│  │  │ (Strategy)        │  │ Service          │              │  │
│  │  │ ┌──────────────┐  │  │ storeChunk()     │              │  │
│  │  │ │LocalFile     │  │  │ assembleChunks() │              │  │
│  │  │ │Storage       │  │  │ cleanup()        │              │  │
│  │  │ └──────────────┘  │  └──────────────────┘              │  │
│  │  └──────────────────┘                                     │  │
│  │                                                           │  │
│  │  ┌─────────────────────────────────────────────────────┐  │  │
│  │  │ FileAccessService (Chain of Responsibility)         │  │  │
│  │  │  Owner → TreatingDoctor → Supervisor → Admin       │  │  │
│  │  └─────────────────────────────────────────────────────┘  │  │
│  └──────────────────────────────────────────────────────────┘  │
│                        │            │                          │
│                        ▼            ▼                          │
│              ┌─────────────────────────────┐                   │
│              │         Database            │                   │
│              │  ┌──────────────────────┐   │                   │
│              │  │ files                │   │                   │
│              │  │ file_downloads       │   │                   │
│              │  │ medical_records      │   │                   │
│              │  │ doctor_patient       │   │                   │
│              │  └──────────────────────┘   │                   │
│              └─────────────────────────────┘                   │
│                        │                                       │
│                        ▼                                       │
│              ┌─────────────────────┐                           │
│              │   Local Disk        │                           │
│              │  storage/app/files/ │                           │
│              │  storage/app/chunks/│                           │
│              └─────────────────────┘                           │
└─────────────────────────────────────────────────────────────────┘
```

---

## 9. Configuration Reference

| Key | Default | Description |
|-----|---------|-------------|
| `files.max_file_size` | 20480 (KB) | أقصى حجم للملف (20MB) |
| `files.chunk_size` | 5120 (KB) | حجم القطعة الواحدة (5MB) |
| `files.allowed_mime_types` | ['application/pdf', 'image/jpeg', ...] | أنواع MIME المسموح بها |
| `files.download_ttl_minutes` | 1440 (24h) | مدة صلاحية رابط التحميل |
| `files.storage.disk` | 'local' | Storage driver المستخدم |

---

## 10. Sequence Summary (تسلسل مختصر)

```
[Patient] → Frontend → POST /files (direct) OR /files/init → /files/{id}/chunk → /files/{id}/complete
            ↓
            File saved to disk + DB record
            ↓
[Doctor/Admin] → Frontend → POST /files/{id}/download-link → signed URL
            ↓
[Browser] → GET {signed URL} → ValidateSignature → BinaryFileResponse (Range support)
            ↓
            FileDownload logged → file delivered to client
```
