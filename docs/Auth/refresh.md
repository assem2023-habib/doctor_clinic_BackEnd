# POST /api/v1/auth/refresh

تحديث التوكنات باستخدام `refresh_token`. عندما ينتهي `access_token`، يُستخدم هذا الـ endpoint للحصول على توكن جديد دون إعادة تسجيل الدخول.

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `POST` |
| **URL** | `/api/v1/auth/refresh` |
| **Auth** | ❌ لا يحتاج |
| **Middleware** | لا يوجد |
| **Content-Type** | `application/json` |

---

## 2. Request Body

| الحقل | النوع | الحالة | التحقق |
|-------|-------|--------|--------|
| `refresh_token` | `string` | **مطلوب** | — |

### مثال:

```json
{
    "refresh_token": "def50200..."
}
```

---

## 3. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Auth/AuthController.php`

```php
public function refresh(Request $request): JsonResponse
{
    $request->validate(['refresh_token' => 'required|string']);

    try {
        $tokenData = $this->authService->refreshToken($request->refresh_token);

        return ApiResponse::success([
            'access_token' => $tokenData->accessToken,
            'refresh_token' => $tokenData->refreshToken,
            'expires_in' => $tokenData->expiresIn,
            'token_type' => 'Bearer',
        ], __('auth.refresh_success'));
    } catch (RequestException $e) {
        return ApiResponse::unauthorized(
            __('Invalid or expired refresh token.')
        );
    }
}
```

> **ملاحظة:** هذا الـ endpoint **لا يعيد** بيانات المستخدم. يجب على العميل استدعاء `GET /me` بعد التحديث.

---

## 4. Action Layer: `AuthService::refreshToken()`

**الملف:** `app/Domains\Auth\Services\AuthService.php`

### التدفق الداخلي:

```
AuthService::refreshToken(string $refreshToken): TokenData
│
├── 1. إنشاء ServerRequest(POST, /oauth/token)
│     ├── grant_type = refresh_token
│     ├── refresh_token = $refreshToken
│     ├── client_id = config('passport.password_client_id')
│     └── client_secret = config('passport.password_client_secret')
│
├── 2. AuthorizationServer::respondToAccessTokenRequest()
│
├── 3. if (OAuthServerException)
│     └── throw AuthenticationException
│
└── 4. return TokenData::fromPassportResponse($body)
```

---

## 5. Response

### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "Token refreshed successfully.",
    "data": {
        "access_token": "eyJ0eXAiOiJKV1Qi...",
        "refresh_token": "def50200...",
        "expires_in": 86400,
        "token_type": "Bearer"
    }
}
```

### ❌ Invalid/Expired — `401 Unauthorized`

```json
{
    "status": 401,
    "message": "Invalid or expired refresh token."
}
```

---

## 6. DTO: `TokenData`

**الملف:** `app/Domains\Auth\DTOs\TokenData.php`

```php
class TokenData
{
    public readonly string $accessToken;
    public readonly string $refreshToken;
    public readonly int $expiresIn;
}
```

### `fromPassportResponse(array $response): self`

| مفتاح الـ Response | خاصية الـ DTO |
|--------------------|---------------|
| `access_token` | `$accessToken` |
| `refresh_token` | `$refreshToken` |
| `expires_in` | `$expiresIn` |

---

## 7. Sequence Diagram

```
Client                     Controller              AuthService
  │                            │                      │
  │── POST /refresh ──────────→│                      │
  │                            │                      │
  │                            │── $request->validate │
  │                            │                      │
  │                            │── refreshToken() ───→│
  │                            │                      │── ServerRequest(/oauth/token)
  │                            │                      │── AuthorizationServer
  │                            │                      │── respondToAccessTokenRequest()
  │                            │←── TokenData ────────│
  │                            │                      │
  │                            │── ApiResponse::success
  │← 200 + JSON ──────────────│                      │
```

---

## 8. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `200` | `Token refreshed successfully.` | نجاح |
| `401` | `Invalid or expired refresh token.` | الـ refresh_token غير صالح أو منتهي |
| `422` | `Validation failed` | حقل `refresh_token` ناقص |
