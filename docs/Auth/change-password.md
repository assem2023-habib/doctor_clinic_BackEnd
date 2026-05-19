# PUT /api/v1/auth/password

تغيير كلمة السر الخاصة بالمستخدم الحالي.

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `PUT` |
| **URL** | `/api/v1/auth/password` |
| **Auth** | ✅ `Bearer <access_token>` |
| **Middleware** | `auth:api` |
| **Content-Type** | `application/json` |

---

## 2. Request Body

| الحقل | النوع | الحالة | التحقق |
|-------|-------|--------|--------|
| `old_password` | `string` | **مطلوب** | `current_password` (يتحقق من صحة كلمة السر الحالية) |
| `new_password` | `string` | **مطلوب** | Laravel Password defaults |

### مثال:

```json
{
    "old_password": "MyOldPassword123!",
    "new_password": "MyNewPassword456@"
}
```

---

## 3. الـ Request: `ChangePasswordRequest`

**الملف:** `app/Domains\Auth\Requests\ChangePasswordRequest.php`

```php
public function rules(): array
{
    return [
        'old_password' => ['required', 'string', 'current_password'],
        'new_password' => ['required', Password::defaults()],
    ];
}
```

> قاعدة `current_password` تتحقق تلقائياً من أن `old_password` تطابق كلمة السر الحالية للمستخدم المُوثّق.

---

## 4. Action: `ChangePasswordAction::execute()`

**الملف:** `app/Domains\Auth\Actions\ChangePasswordAction.php`

```php
public function execute(User $user, string $newPassword): void
{
    $user->update([
        'password' => Hash::make($newPassword),
    ]);
}
```

**التدفق:**

```
1. user->update(['password' => Hash::make($newPassword)])
   └── يتم تشفير كلمة السر الجديدة وتحديثها في قاعدة البيانات
```

> **ملاحظة:** لا يتم إلغاء التوكنات الحالية. يبقى المستخدم مسجلاً الدخول.

---

## 5. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Auth/AuthController.php`

```php
public function changePassword(ChangePasswordRequest $request): JsonResponse
{
    $this->changePasswordAction->execute(
        user: $request->user(),
        newPassword: $request->new_password,
    );

    return ApiResponse::success(null, __('auth.password_changed'));
}
```

---

## 6. Response

### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "Password changed successfully.",
    "data": null
}
```

### ❌ Wrong Old Password — `422 Unprocessable Entity`

```json
{
    "status": 422,
    "message": "Validation failed",
    "errors": {
        "old_password": ["The provided password does not match your current password."]
    }
}
```

---

## 7. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `200` | `Password changed successfully.` | نجاح |
| `422` | `Validation failed` | `old_password` خطأ أو `new_password` ضعيف |
| `401` | `Unauthenticated.` | التوكن غير صالح |
