# RBAC Domain — التوثيق الكامل

نظام إدارة الصلاحيات والأدوار (Role-Based Access Control) للمنصة. يعتمد على **PHP 8 Attributes** لتحديد الصلاحيات المطلوبة لكل Controller/Method.

---

## قائمة الـ Endpoints

| # | Method | Endpoint | Auth | Admin | الصفحة |
|---|--------|----------|------|-------|--------|
| 1 | `GET` | `/api/v1/roles` | ✅ Bearer | ❌ | [list-roles.md](list-roles.md) |
| 2 | `GET` | `/api/v1/roles/{role}` | ✅ Bearer | ❌ | [show-role.md](show-role.md) |
| 3 | `POST` | `/api/v1/roles` | ✅ Bearer | ✅ | [create-role.md](create-role.md) |
| 4 | `PUT` | `/api/v1/roles/{role}` | ✅ Bearer | ✅ | [update-role.md](update-role.md) |
| 5 | `DELETE` | `/api/v1/roles/{role}` | ✅ Bearer | ✅ | [delete-role.md](delete-role.md) |
| 6 | `POST` | `/api/v1/roles/{role}/permissions` | ✅ Bearer | ✅ | [sync-permissions.md](sync-permissions.md) |
| 7 | `GET` | `/api/v1/permissions` | ✅ Bearer | ❌ | [list-permissions.md](list-permissions.md) |
| 8 | `GET` | `/api/v1/permissions/{permission}` | ✅ Bearer | ❌ | [show-permission.md](show-permission.md) |
| 9 | `POST` | `/api/v1/permissions` | ✅ Bearer | ✅ | [create-permission.md](create-permission.md) |
| 10 | `PUT` | `/api/v1/permissions/{permission}` | ✅ Bearer | ✅ | [update-permission.md](update-permission.md) |
| 11 | `DELETE` | `/api/v1/permissions/{permission}` | ✅ Bearer | ✅ | [delete-permission.md](delete-permission.md) |
| 12 | `GET` | `/api/v1/users/{user}/roles` | ✅ Bearer | ❌ | [user-roles-get.md](user-roles-get.md) |
| 13 | `POST` | `/api/v1/users/{user}/roles` | ✅ Bearer | ✅ | [user-roles-sync.md](user-roles-sync.md) |
| 14 | `POST` | `/api/v1/device-tokens` | ✅ Bearer | ❌ | [device-tokens.md](device-tokens.md) |

---

## آلية العمل

```
#[Role('admin')]                          ← PHP Attribute على الـ Controller
#[Role('super-admin')]                    ← قابل للتكرار (repeatable)
class RoleController { ... }

      ↓
middleware 'role.authorize'               ← يقرأ الـ Attributes عبر Reflection
      ↓
PermissionService::hasAnyRole($user, $roles)  ← يتحقق من pivot role_user
```

### خطوات إضافة صلاحية لـ Controller جديد:
1. أضف `#[Role('role_slug')]` فوق الكلاس أو الميثود
2. سجل الميثود في الـ Route
3. أضف middleware `role.authorize` للـ Route أو الـ group

---

## الأنماط المعمارية

| النمط | أين يستخدم |
|-------|-----------|
| **Attribute Pattern** | `#[Role]` PHP 8 Attribute على Controllers |
| **Middleware Pattern** | `AuthorizeByAttribute` — يقرأ Attribute ويقرر السماح/الرفض |
| **Service Pattern** | `PermissionService` — 11 method static لإدارة الصلاحيات |
| **Repository Pattern** | Eloquent Models (`Role`, `Permission`) |
| **Resource Pattern** | `RoleResource`, `PermissionResource` |

---

## هيكل قاعدة البيانات

```
roles
├── id (UUID)
├── name
├── slug (unique)
├── description (nullable)
├── guard_name (default: 'api')
├── is_system (boolean) ← admin/doctor/patient roles لا يمكن حذفها
└── timestamps

permissions
├── id (UUID)
├── name
├── slug (unique)
├── description (nullable)
├── group (nullable) ← تجميع: Appointments, Patients, RBAC, Locations
├── guard_name (default: 'api')
└── timestamps

role_permission (pivot)
├── role_id (FK)
├── permission_id (FK)
└── timestamps

role_user (pivot)
├── role_id (FK)
├── user_id (FK)
└── timestamps
```

---

## هيكل المجلدات

```
app/Domains/RBAC/
├── Attributes/
│   └── Role.php                    ← #[Role('admin')] PHP 8 Attribute
├── Controllers/
│   ├── RoleController.php          ← CRUD roles + syncPermissions
│   ├── PermissionController.php    ← CRUD permissions
│   └── UserRoleController.php      ← get/sync roles للمستخدم
├── Enums/                          ← (للإستخدام المستقبلي)
├── Models/
│   ├── Role.php                    ← BelongsToMany permissions, users
│   └── Permission.php              ← BelongsToMany roles
├── Requests/                       ← FormRequest validation
│   ├── StoreRoleRequest.php
│   ├── UpdateRoleRequest.php
│   ├── StorePermissionRequest.php
│   ├── UpdatePermissionRequest.php
│   ├── SyncRolePermissionsRequest.php
│   └── SyncUserRolesRequest.php
├── Resources/
│   ├── RoleResource.php
│   └── PermissionResource.php
└── Services/
    └── PermissionService.php       ← 11 static methods

app/Http/Middleware/
└── AuthorizeByAttribute.php        ← middleware 'role.authorize'

app/Http/Controllers/Api/V1/
└── DeviceTokenController.php       ← POST /api/v1/device-tokens
```

---

## الأدوار المبدئية (Seed)

| الرتبة | slug | صلاحيات | System |
|--------|------|---------|--------|
| Super Admin | `super-admin` | جميع الصلاحيات (28) | ✅ |
| Admin | `admin` | جميع الصلاحيات (28) | ✅ |
| Doctor | `doctor` | appointments.view/edit, patients.view, doctors.view | ✅ |
| Patient | `patient` | appointments.view/create, patients.view | ✅ |
| Receptionist | `receptionist` | appointments + patients CRUD, doctors.view | ❌ |

## PermissionService API Reference

| Method | الوصف |
|--------|-------|
| `hasRole($user, 'admin')` | هل للمستخدم رتبة معينة؟ |
| `hasAnyRole($user, ['admin','doctor'])` | هل لديه أي من هذه الرتب؟ |
| `hasAllRoles($user, ['admin','doctor'])` | هل لديه كل هذه الرتب؟ |
| `hasPermission($user, 'roles.create')` | هل لديه صلاحية معينة؟ (عبر رتبته) |
| `hasAnyPermission($user, [...])` | هل لديه أي من هذه الصلاحيات؟ |
| `hasAllPermissions($user, [...])` | هل لديه كل هذه الصلاحيات؟ |
| `getUserRoles($user)` | قائمة الرتب للمستخدم |
| `getUserPermissions($user)` | قائمة الصلاحيات للمستخدم |
| `getUserPermissionSlugs($user)` | slugs فقط |
| `assignRole($user, 'doctor')` | إضافة رتبة |
| `removeRole($user, 'doctor')` | إزالة رتبة |
| `syncRoles($user, ['doctor','patient'])` | مزامنة الرتب (يحذف القديم ويضيف الجديد) |
