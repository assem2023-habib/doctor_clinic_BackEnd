# POST /api/v1/auth/logout

تسجيل الخروج — إلغاء (revoke) التوكن الحالي.

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `POST` |
| **URL** | `/api/v1/auth/logout` |
| **Auth** | ✅ `Bearer <access_token>` في `Authorization` header |
| **Middleware** | `auth:api` |
| **Content-Type** | — (بدون body) |

---

## 2. Request

**Headers:**

```
Authorization: Bearer eyJ0eXAiOiJKV1Qi...
```

**Body:** لا يحتاج Body.

---

## 3. Action: `LogoutAction::execute()`

**الملف:** `app/Domains\Auth\Actions\LogoutAction.php`

```php
public function execute(): void
{
    $user = Auth::user();
    $token = $user->token();

    if ($token) {
        $token->revoke();
    }
}
```

ما يحدث:

```
1. Auth::user() → المستخدم الحالي (من Bearer token)
2. $user->token() → الـ OAuth access token الحالي
3. $token->revoke() → إلغاء التوكن (يبقى في قاعدة البيانات لكن revoked = true)
```

---

## 4. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Auth/AuthController.php`

```php
public function logout(): JsonResponse
{
    $this->logoutAction->execute();

    return ApiResponse::success(null, __('auth.logout_success'));
}
```

---

## 5. Response

### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "Logged out successfully.",
    "data": null
}
```

### ❌ Unauthenticated — `401 Unauthorized`

```json
{
    "status": 401,
    "message": "Unauthenticated."
}
```

---

## 6. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `200` | `Logged out successfully.` | نجاح |
| `401` | `Unauthenticated.` | التوكن غير صالح أو منتهي |
