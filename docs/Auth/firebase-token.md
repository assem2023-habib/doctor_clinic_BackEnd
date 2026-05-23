# POST /api/v1/auth/firebase-token

إنشاء Firebase Custom Token — يستخدمه الـ Frontend لتسجيل الدخول في Firebase وقراءة Realtime Database.

---

## 1. التدفق الكامل

```
1. المستخدم يسجل دخول ← POST /api/v1/auth/login ← يحصل على Bearer token
2. الـ Frontend يرسل Bearer token إلى هذا الـ Endpoint
3. Laravel يُنشئ Firebase Custom Token باستخدام Service Account
4. الـ Frontend يستخدم Firebase SDK: signInWithCustomToken(token)
5. الآن الـ Frontend قادر على قراءة /doctors/{id}/booked-appointments من RTDB
```

---

## 2. Route Info

| Field | Value |
|-------|-------|
| **Method** | `POST` |
| **URL** | `/api/v1/auth/firebase-token` |
| **Auth** | ✅ `Bearer <access_token>` |
| **Middleware** | `auth:api`, `active` |
| **Content-Type** | `application/json` |

---

## 3. Request

**Headers:**

```
Authorization: Bearer eyJ0eXAiOiJKV1Qi...
```

**Body:** لا يحتاج Body.

---

## 4. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Auth/AuthController.php`

```php
public function firebaseToken(Request $request): JsonResponse
{
    $user = $request->user();

    $auth = $this->firebase->auth();

    if (!$auth) {
        return ApiResponse::error(__('Firebase not configured'), 500);
    }

    try {
        $uid = "user_{$user->id}";
        $customToken = $auth->createCustomToken($uid, [
            'user_id' => $user->id,
            'role' => $user->roles->pluck('slug')->implode(','),
        ]);

        return ApiResponse::success([
            'firebase_token' => $customToken->toString(),
            'uid' => $uid,
        ], __('auth.firebase_token_generated'));
    } catch (\Throwable $e) {
        return ApiResponse::error(__('Failed to generate Firebase token'), 500);
    }
}
```

---

## 5. Response

```json
{
    "status": 200,
    "message": "Firebase token generated successfully.",
    "data": {
        "firebase_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
        "uid": "user_019e1d0f-1ec6-7289-8cb3-eb9bdb0f1009"
    }
}
```

---

## 6. Frontend Integration (JavaScript)

```javascript
import { initializeApp } from "firebase/app";
import { getDatabase, ref, onValue } from "firebase/database";
import { getAuth, signInWithCustomToken } from "firebase/auth";

const firebaseConfig = {
  apiKey: "...",
  authDomain: "...",
  databaseURL: "https://clinic-managment-9c6fe-default-rtdb.europe-west1.firebasedatabase.app",
  projectId: "clinic-managment-9c6fe",
};

// 1. بعد تسجيل الدخول في Laravel، اجلب Firebase Token
const response = await fetch("/api/v1/auth/firebase-token", {
  headers: { Authorization: "Bearer " + laravelAccessToken },
});
const { firebase_token } = (await response.json()).data;

// 2. سجل دخول في Firebase
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
await signInWithCustomToken(auth, firebase_token);

// 3. اقرأ المواعيد المباشرة
const db = getDatabase(app);
const ref = ref(db, "doctors/" + doctorId + "/booked-appointments");
onValue(ref, (snapshot) => {
  const appointments = snapshot.val();
  // appointments = { uuid1: {...}, uuid2: {...}, ... }
});
```

---

## 7. Firebase Custom Token Claims

كل Custom Token يحتوي على الـ claims التالية:

| Claim | القيمة | مثال |
|-------|--------|------|
| `uid` | `user_{id}` | `user_019e1d0f-...` |
| `user_id` | UUID المستخدم | `019e1d0f-...` |
| `role` | الأدوار مفصولة بفاصلة | `patient` / `admin` / `doctor,receptionist` |

---

## 8. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `200` | `Firebase token generated successfully.` | نجاح |
| `401` | `Unauthenticated.` | التوكن غير صالح أو منتهي |
| `500` | `Firebase not configured.` | لم يتم إعداد Firebase (الـ credentials غير موجودة) |
| `500` | `Failed to generate Firebase token.` | خطأ في توليد التوكن من Firebase |
