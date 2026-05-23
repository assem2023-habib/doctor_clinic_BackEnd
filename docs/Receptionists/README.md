# Receptionists Domain — التوثيق الكامل

إدارة موظفي الاستقبال — عرض، بحث، إنشاء، تحديث، حذف.

---

## قائمة الـ Endpoints

| # | Method | Endpoint | Auth | الوصف |
|---|--------|----------|------|-------|
| 1 | `GET` | `/api/v1/receptionists` | ❌ عام | قائمة موظفي الاستقبال مع البحث والفلترة ([توثيق](list-receptionists.md)) |
| 2 | `POST` | `/api/v1/receptionists` | ✅ admin | إنشاء موظف استقبال جديد ([توثيق](create-receptionist.md)) |
| 3 | `GET` | `/api/v1/receptionists/{receptionist}` | ❌ عام | عرض موظف استقبال |
| 4 | `PUT` | `/api/v1/receptionists/{receptionist}` | ✅ admin | تحديث كامل |
| 5 | `PATCH` | `/api/v1/receptionists/{receptionist}` | ✅ admin | تحديث جزئي |
| 6 | `DELETE` | `/api/v1/receptionists/{receptionist}` | ✅ admin | حذف مع تنظيف |
| 7 | `PUT` | `/api/v1/receptionists/{receptionist}/activate-account` | ✅ admin | تفعيل حساب الاستقبال ([توثيق](activate-account.md)) |

---

## الموديلات

| الموديل | الجدول | الحقول |
|---------|--------|--------|
| `Receptionist` | `receptionists` | id, user_id, shift_start, shift_end |

### علاقات Receptionist:
- `user()` — BelongsTo → User

---

## هيكل المجلدات

```
app/Domains/Receptionists/
├── Actions/
│   ├── ActivateReceptionistAccountAction.php
│   ├── CreateReceptionistAction.php
│   ├── UpdateReceptionistAction.php
│   └── DeleteReceptionistAction.php
├── DTOs/
│   └── UpdateReceptionistData.php
├── Models/
│   └── Receptionist.php
├── Requests/
│   ├── StoreReceptionistRequest.php
│   ├── UpdateReceptionistRequest.php
│   └── PatchReceptionistRequest.php
└── Resources/
    └── ReceptionistResource.php
```
