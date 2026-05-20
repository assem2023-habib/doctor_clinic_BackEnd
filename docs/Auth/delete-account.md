# DELETE /api/v1/auth/account

حذف الحساب بشكل دائم. يتطلب تأكيد كلمة السر.

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `DELETE` |
| **URL** | `/api/v1/auth/account` |
| **Auth** | ✅ `Bearer <access_token>` |
| **Middleware** | `auth:api` |
| **Content-Type** | `application/json` |

---

## 2. Request Body

| الحقل | النوع | الحالة | التحقق |
|-------|-------|--------|--------|
| `password` | `string` | **مطلوب** | `current_password` (يتحقق من صحة كلمة السر) |

### مثال:

```json
{
    "password": "MySecurePassword123!"
}
```

---

## 3. الـ Request: `DeleteAccountRequest`

**الملف:** `app/Domains\Auth\Requests\DeleteAccountRequest.php`

```php
public function rules(): array
{
    return [
        'password' => ['required', 'string', 'current_password'],
    ];
}
```

> يتطلب تأكيد كلمة السر لمنع الحذف العرضي أو غير المصرّح به.

---

## 4. Action: `DeleteAccountAction::execute()`

**الملف:** `app/Domains\Auth\Actions\DeleteAccountAction.php`

```php
public function execute(User $user): void
{
    if ($user->hasRole('doctor') && $user->doctor) {
        $this->doctorDeletionService->deleteDoctor($user->doctor, $user);
        return;
    }

    if ($user->hasRole('patient') && $user->patient) {
        $this->patientDeletionService->deletePatient($user->patient, $user);
        return;
    }

    // Admin / Receptionist
    DB::transaction(function () use ($user) {
        $userLabel = $user->id . ': ' . $user->first_name . ' ' . $user->last_name;

        AppointmentStatusLog::where('changed_by', 'like', $user->id . ':%')->delete();

        Appointment::where('created_by', 'like', $user->id . ':%')->delete();

        $user->tokens()->delete();

        $user->delete();
    });
}
```

### التدفق الكامل:

```
DeleteAccountAction::execute(User $user)
│
├── if hasRole('doctor')
│     └── DoctorDeletionService::deleteDoctor()
│           ├── حذف المواعيف المرتبطة
│           ├── حذف سجلات الطبيب
│           └── حذف المستخدم
│
├── if hasRole('patient')
│     └── PatientDeletionService::deletePatient()
│           ├── حذف المواعيف المرتبطة
│           ├── حذف سجلات المريض
│           └── حذف المستخدم
│
└── else (Admin / Receptionist)
      └── DB::transaction
            ├── حذف AppointmentStatusLog (حيث changed_by = userId)
            ├── حذف Appointment (حيث created_by = userId)
            ├── حذف جميع OAuth tokens
            └── حذف المستخدم
```

### Dependencies:

| الخدمة | الغرض |
|--------|-------|
| `DoctorDeletionService` | حذف متتالي لطبيب مع تنظيف cascade |
| `PatientDeletionService` | حذف متتالي لمريض مع تنظيف cascade |

---

## 5. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Auth/AuthController.php`

```php
public function deleteAccount(DeleteAccountRequest $request): JsonResponse
{
    $this->deleteAccountAction->execute($request->user());

    return ApiResponse::success(null, __('auth.account_deleted'));
}
```

---

## 6. Response

### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "Account deleted successfully.",
    "data": null
}
```

### ❌ Wrong Password — `422 Unprocessable Entity`

```json
{
    "status": 422,
    "message": "Validation failed",
    "errors": {
        "password": ["The password you entered is incorrect."]
    }
}
```

---

## 7. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `200` | `Account deleted successfully.` | نجاح |
| `422` | `Validation failed` | كلمة السر غير صحيحة |
| `401` | `Unauthenticated.` | التوكن غير صالح |

---

## 8. تحذير

> ⚠️ هذا الإجراء **نهائي ولا يمكن التراجع عنه**. جميع بيانات المستخدم (المواعيد، السجلات الطبية، الوصفات، الصور) سيتم حذفها بشكل دائم.
