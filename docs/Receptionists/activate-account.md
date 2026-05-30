# PUT /api/v1/receptionists/{receptionist}/activate-account

تفعيل حساب موظف استقبال. يقوم بتعيين `is_active = true` لحساب المستخدم المرتبط.

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `PUT` |
| **URL** | `/api/v1/receptionists/{receptionist}/activate-account` |
| **Auth** | ✅ Bearer token (admin فقط) |
| **Middleware** | `auth:api`, `active`, `admin` |

---

## 2. الـ Action: `ActivateReceptionistAccountAction::execute()`

**الملف:** `app/Domains/Receptionists/Actions/ActivateReceptionistAccountAction.php`

```php
public function execute(Receptionist $receptionist): Receptionist
{
    $receptionist->user->update(['is_active' => true]);
    $receptionist->load('user.roles');

    return $receptionist;
}
```

---

## 3. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Receptionist/ReceptionistController.php`

```php
public function activateAccount(Receptionist $receptionist): JsonResponse
{
    $receptionist = $this->activateReceptionistAccountAction->execute($receptionist);

    return ApiResponse::success(
        new ReceptionistResource($receptionist->user),
        __('auth.account_activated')
    );
}
```

---

## 4. Response

### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "Account activated successfully",
    "data": {
        "id": "019e1d0f-...",
        "first_name": "Layla",
        "last_name": "Hassan",
        "email": "receptionist@example.com",
        "roles": ["Receptionist"],
        "shift_start": "09:00",
        "shift_end": "17:00",
        "phone": "+963912345680",
        "gender": "female",
        "birthday_date": "1998-11-05",
        "is_active": true
    }
}
```

### ❌ Unauthenticated — `401 Unauthorized`

```json
{
    "status": 401,
    "message": "Unauthenticated"
}
```

### ❌ Forbidden — `403 Forbidden`

```json
{
    "status": 403,
    "message": "Forbidden"
}
```

---

## 5. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `200` | `Account activated successfully` | نجاح |
| `401` | `Unauthenticated` | لم يتم تسجيل الدخول |
| `403` | `Forbidden` | ليس لديك صلاحية admin |
| `404` | `Not Found` | الـ Receptionist UUID غير موجود |
