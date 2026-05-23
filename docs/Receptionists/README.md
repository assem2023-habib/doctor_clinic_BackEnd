# Receptionists Domain — التوثيق الكامل

إدارة موظفي الاستقبال — تفعيل الحساب.

---

## قائمة الـ Endpoints

| # | Method | Endpoint | Auth | الوصف |
|---|--------|----------|------|-------|
| 1 | `PUT` | `/api/v1/receptionists/{receptionist}/activate-account` | ✅ admin | تفعيل حساب الاستقبال ([توثيق](activate-account.md)) |

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
│   └── ActivateReceptionistAccountAction.php
├── Models/
│   └── Receptionist.php
└── Resources/
    └── ReceptionistResource.php
```
