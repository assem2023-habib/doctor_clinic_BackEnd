# Login Security System

نظام أمن تسجيل الدخول — يمنع الاختراق باستخدام 3 استراتيجيات حظر متكاملة.

---

## 1. Architecture Overview

```
LoginSecurityManager (Context/Main Orchestrator)
├── Implements Strategy Pattern
├── يستقبل set of BlockingStrategyInterface
├── يرتبهم حسب getPriority() (أقل رقم = ينفذ أولاً)
│
├── BlockingStrategyInterface
│   ├── check(LoginSecurityContext): ?BlockingDecision
│   └── getPriority(): int
│
├── Contract: LoginAttemptRepositoryInterface
│   └── Impl: EloquentLoginAttemptRepository
│
├── Contract: DeviceFingerprintServiceInterface
│   └── Impl: DeviceFingerprintService
│
├── Events
│   ├── LoginFromNewDevice
│   └── SuspiciousLoginAttempts
│
└── Listeners
    ├── SendNewDeviceNotification
    └── NotifySuspiciousActivity
```

---

## 2. Strategy Priorities

| الإستراتيجية | الأولوية | النوع | تسبب حظر؟ | الوصف |
|-------------|---------|-------|-----------|-------|
| `SuspiciousActivityStrategy` | 5 | تحذيرية | ❌ لا | ترسل إنذاراً فقط عند اكتشاف 3+ أجهزة مختلفة |
| `FingerprintBlockingStrategy` | 10 | حظر | ✅ نعم | تحظر الجهاز بعد 5 محاولات فاشلة/30 دقيقة |
| `IpBlockingStrategy` | 20 | حظر | ✅ نعم | تحظر الـ IP بعد 10 محاولات فاشلة/15 دقيقة |

---

## 3. Contracts (Interfaces)

### `BlockingStrategyInterface`

**الملف:** `app/Domains/Auth/Contracts/BlockingStrategyInterface.php`

```php
interface BlockingStrategyInterface
{
    public function check(LoginSecurityContext $context): ?BlockingDecision;

    public function getPriority(): int;
}
```

### `LoginAttemptRepositoryInterface`

**الملف:** `app/Domains/Auth/Contracts/LoginAttemptRepositoryInterface.php`

```php
interface LoginAttemptRepositoryInterface
{
    public function recordAttempt(LoginAttemptData $data): void;
    public function countRecentFailuresByEmail(string $email, int $minutes): int;
    public function countRecentFailuresByIp(string $ip, int $minutes): int;
    public function countRecentFailuresByFingerprint(string $fingerprint, int $minutes): int;
    public function countRecentFailuresByEmailFromDifferentFingerprints(string $email, int $minutes): int;
    public function isDeviceBlocked(string $fingerprint): bool;
    public function isIpBlocked(string $ip): bool;
    public function blockDevice(string $fingerprint, int $durationMinutes, string $reason): void;
    public function blockIp(string $ip, int $durationMinutes, string $reason): void;
    public function getLastSuccessfulAttempt(string $email): ?LoginAttemptData;
}
```

### `DeviceFingerprintServiceInterface`

**الملف:** `app/Domains/Auth/Contracts/DeviceFingerprintServiceInterface.php`

```php
interface DeviceFingerprintServiceInterface
{
    public function isKnownDevice(string $userId, string $fingerprint): bool;
    public function registerDevice(string $userId, string $fingerprint, string $ip, ?string $userAgent, ?string $deviceName): void;
    public function isBlocked(string $fingerprint): bool;
    public function block(string $fingerprint, int $durationMinutes, string $reason): void;
    public function getTrustedDevices(string $userId): array;
}
```

---

## 4. DTOs

### `LoginSecurityContext`

**الملف:** `app/Domains\Auth\DTOs\LoginSecurityContext.php`

```php
class LoginSecurityContext
{
    public readonly string $email;
    public readonly string $password;
    public readonly string $ip;
    public readonly ?string $deviceFingerprint;
    public readonly ?string $userAgent;
}
```

> يُنشأ داخل `LoginAction::execute()` قبل أي عملية أمنية.

### `BlockingDecision`

**الملف:** `app/Domains\Auth\DTOs\BlockingDecision.php`

```php
class BlockingDecision
{
    public readonly bool $blocked;          // true = ممنوع الدخول
    public readonly string $reason;          // رسالة للمستخدم
    public readonly ?int $retryAfterSeconds; // كم ثانية حتى المحاولة التالية
    public readonly ?string $strategy;       // أي استراتيجية قررت
}
```

### `LoginAttemptData`

**الملف:** `app/Domains\Auth\DTOs\LoginAttemptData.php`

```php
class LoginAttemptData
{
    public readonly ?string $email;
    public readonly string $ip;
    public readonly ?string $deviceFingerprint;
    public readonly ?string $userAgent;
    public readonly bool $success;
    public readonly ?string $failureReason;
}
```

---

## 5. Strategies

### 5.1 `FingerprintBlockingStrategy` (Priority 10)

**الملف:** `app/Domains\Auth\Strategies\FingerprintBlockingStrategy.php`

**المنطق:**

```
check(LoginSecurityContext $context)
│
├── if deviceFingerprint === null → return null (تخطي)
│
├── if isDeviceBlocked($fingerprint)
│     └── return BlockingDecision(blocked: true)
│           reason: "This device is blocked..."
│           retryAfterSeconds: 3600
│
├── $recentFailures = countRecentFailuresByFingerprint($fingerprint, 30 min)
│
├── if $recentFailures >= 5
│     ├── blockDevice($fingerprint, 60 min, "تجاوز X محاولة فاشلة في 30 دقيقة")
│     └── return BlockingDecision(blocked: true)
│           reason: "Too many attempts from this device..."
│           retryAfterSeconds: 3600
│
└── return null (مسموح)
```

**متى ينفذ؟** فقط عندما يرسل العميل `device_fingerprint`.

**مدة الحظر:** 60 دقيقة (قابلة للتعديل في `config/login-security.php`).

### 5.2 `IpBlockingStrategy` (Priority 20)

**الملف:** `app/Domains\Auth\Strategies\IpBlockingStrategy.php`

**المنطق:**

```
check(LoginSecurityContext $context)
│
├── if isIpBlocked($ip)
│     └── return BlockingDecision(blocked: true)
│           reason: "This IP address is temporarily blocked..."
│           retryAfterSeconds: 1800
│
├── $recentFailures = countRecentFailuresByIp($ip, 15 min)
│
├── if $recentFailures >= 10
│     ├── blockIp($ip, 30 min, "تجاوز X محاولة فاشلة من IP واحد في 15 دقيقة")
│     └── return BlockingDecision(blocked: true)
│           reason: "Too many attempts from this IP address..."
│           retryAfterSeconds: 1800
│
└── return null (مسموح)
```

> **ملاحظة:** الـ IP يُخزّن في جدول `device_fingerprints` بمفتاح `ip:{ip_address}` لتمييزه عن بصمات الأجهزة العادية.

### 5.3 `SuspiciousActivityStrategy` (Priority 5)

**الملف:** `app/Domains\Auth\Strategies\SuspiciousActivityStrategy.php`

**المنطق:**

```
check(LoginSecurityContext $context)
│
├── if deviceFingerprint === null → return null
│
├── $differentDevices = countRecentFailuresByEmailFromDifferentFingerprints($email, 10 min)
│
├── if $differentDevices >= 3
│     └── return BlockingDecision(blocked: false)
│           reason: "Suspicious login activity detected from your account."
│           (لا يحظر، فقط ينبه)
│
└── return null
```

> **ملاحظة مهمة:** `blocked: false` — هذه الاستراتيجية لا تمنع الدخول أبداً. ترسل إشارة فقط لـ `LoginSecurityManager` ليقوم بإرسال حدث `SuspiciousLoginAttempts`.

---

## 6. الـ Repository: `EloquentLoginAttemptRepository`

**الملف:** `app/Domains\Auth\Repositories\EloquentLoginAttemptRepository.php`

### Models المستخدمة:

| الموديل | الجدول | الوصف |
|---------|--------|-------|
| `LoginAttempt` | `login_attempts` | يسجل كل محاولة دخول (نجاح/فشل) |
| `DeviceFingerprint` | `device_fingerprints` | بصمة الجهاز مع حالة الحظر والبيانات |

### طرق الحظر:

| الطريقة | الوصف |
|---------|-------|
| `blockDevice($fingerprint, $durationMinutes, $reason)` | يحظر جهاز في `device_fingerprints` |
| `blockIp($ip, $durationMinutes, $reason)` | يحظر IP (مخزّن بـ `ip:{ip}` كمفتاح) |
| `isDeviceBlocked($fingerprint)` | يتحقق هل الجهاز محظور (`blocked_until > now()`) |
| `isIpBlocked($ip)` | يتحقق هل الـ IP محظور |

---

## 7. الـ Service: `LoginSecurityManager`

**الملف:** `app/Domains\Auth\Services\LoginSecurityManager.php`

### `checkBlocked(LoginSecurityContext $context): ?BlockingDecision`

```
1. يرتب الاستراتيجيات حسب الأولوية (تصاعدياً)
2. يمر على كل استراتيجية:
   - إذا قررت blocked = true ← يرجع القرار فوراً (يمنع الدخول)
   - إذا قررت blocked = false ← يستدعي handleSuspiciousActivity() (ينبه فقط)
3. إذا لم تقرر أي استراتيجية شيئاً ← يرجع null (مسموح)
```

### `handleSuccess(LoginSecurityContext $context): void`

```
1. يسجل محاولة ناجحة في login_attempts
2. إذا كان deviceFingerprint موجود:
   - هل الجهاز معروف للمستخدم؟
     - لا ← يسجل جهاز جديد في known_user_devices
           ← يحدث DeviceFingerprint
           ← يرسل حدث LoginFromNewDevice
     - نعم ← يحدث last_seen_at في known_user_devices
           ← يحدث DeviceFingerprint
```

### `handleFailure(LoginSecurityContext $context): void`

```
1. يسجل محاولة فاشلة في login_attempts
2. يحسب إجمالي الفشل لهذا الإيميل في آخر 60 دقيقة
3. يحسب عدد الأجهزة المختلفة التي حاولت على هذا الإيميل في آخر 10 دقائق
4. إذا:
   - الفشل >= 5 (max_failures_per_email_per_hour) أو
   - الأجهزة >= 3 (max_unique_fingerprints_per_email_per_10_minutes)
   ← يرسل حدث SuspiciousLoginAttempts
```

---

## 8. Models

### `LoginAttempt`

**الملف:** `app/Domains\Auth\Models\LoginAttempt.php`

| الحقل | النوع | الوصف |
|-------|-------|-------|
| `email` | `string` | الإيميل المستخدم في المحاولة |
| `ip` | `string` | IP المصدر |
| `device_fingerprint` | `string?` | بصمة الجهاز |
| `user_agent` | `string?` | User-Agent |
| `success` | `boolean` | هل نجحت المحاولة؟ |
| `failure_reason` | `string?` | سبب الفشل |
| `attempted_at` | `datetime` | وقت المحاولة |

### `DeviceFingerprint`

**الملف:** `app/Domains\Auth\Models\DeviceFingerprint.php`

| الحقل | النوع | الوصف |
|-------|-------|-------|
| `fingerprint_hash` | `string` (PK) | بصمة الجهاز (أو `ip:{ip}` للـ IP) |
| `fingerprint_data` | `json?` | بيانات إضافية من FingerprintJS |
| `ip_first_seen` | `string?` | أول IP شوهد منه |
| `blocked_until` | `datetime?` | مدة الحظر (null = غير محظور) |
| `block_reason` | `string?` | سبب الحظر |

`scopeBlocked($query)` — يرجع الأجهزة المحظورة حالياً (`blocked_until > now()`).

### `KnownUserDevice`

**الملف:** `app/Domains\Auth\Models\KnownUserDevice.php`

| الحقل | النوع | الوصف |
|-------|-------|-------|
| `user_id` | `uuid` (FK → users) | المستخدم |
| `device_fingerprint` | `string` | بصمة الجهاز |
| `device_name` | `string?` | اسم الجهاز |
| `trusted_at` | `datetime?` | متى تمت الموثوقية (null = غير موثوق) |

`scopeTrusted($query)` — الأجهزة الموثوقة فقط.  
`trust(): void` — تجعل الجهاز موثوقاً.

---

## 9. Events & Listeners

### `LoginFromNewDevice`

**الملف:** `app/Domains\Auth\Events\LoginFromNewDevice.php`

| الخاصية | النوع |
|---------|-------|
| `$user` | `User` |
| `$ip` | `string` |
| `$deviceFingerprint` | `string` |
| `$userAgent` | `?string` |

**Listener:** `SendNewDeviceNotification` ← يسجل في Log ويرسل إشعار للمستخدم عبر `NotificationManager` (Database + Firebase + WebSocket حسب الإعدادات).

### `SuspiciousLoginAttempts`

**الملف:** `app/Domains\Auth\Events\SuspiciousLoginAttempts.php`

| الخاصية | النوع |
|---------|-------|
| `$user` | `User` |
| `$ip` | `string` |
| `$deviceFingerprint` | `?string` |
| `$failedAttempts` | `int` |
| `$differentDevices` | `int` |

**Listener:** `NotifySuspiciousActivity` ← يسجل تحذير في Log ويرسل إشعار أمني للمستخدم.

### تسجيل الأحداث في `LoginSecurityServiceProvider`:

```php
Event::listen(LoginFromNewDevice::class, SendNewDeviceNotification::class);
Event::listen(SuspiciousLoginAttempts::class, NotifySuspiciousActivity::class);
```

---

## 10. Config: `config/login-security.php`

```php
return [
    'strategies' => [
        'fingerprint' => [
            'enabled'  => true,
            'priority' => 10,
        ],
        'ip' => [
            'enabled'  => true,
            'priority' => 20,
        ],
    ],

    'limits' => [
        'max_failures_per_email_per_hour'                       => 5,
        'max_failures_per_ip_per_15_minutes'                    => 10,
        'max_failures_per_fingerprint_per_30_minutes'           => 5,
        'max_unique_fingerprints_per_email_per_10_minutes'       => 3,
    ],

    'block_durations' => [
        'fingerprint_temporary' => 60,   // دقيقة
        'ip_temporary'          => 30,   // دقيقة
        'ip_permanent'          => 1440, // دقيقة (24 ساعة)
    ],

    'device_trust' => [
        'auto_trust_after_logins' => 3,
        'max_trusted_devices'     => 10,
    ],
];
```

---

## 11. التسجيل في Service Provider

**الملف:** `app/Domains/Auth/Providers/LoginSecurityServiceProvider.php`

يسجل في `bootstrap/app.php`:

```php
->withProviders([
    App\Domains\Auth\Providers\LoginSecurityServiceProvider::class,
])
```

ماذا يفعل في `register()`:

```
1. bind(LoginAttemptRepositoryInterface → EloquentLoginAttemptRepository)
2. bind(DeviceFingerprintServiceInterface → DeviceFingerprintService)
3. singleton(LoginSecurityManager)
   ├── ينشئ الـ Manager
   ├── إذا fingerprint مفعّل ← addStrategy(new FingerprintBlockingStrategy)
   ├── إذا ip مفعّل ← addStrategy(new IpBlockingStrategy)
   └── addStrategy(new SuspiciousActivityStrategy) ← دائمة التفعيل
```

---

## 12. Key Design Decisions

| القرار | السبب |
|--------|-------|
| Device Fingerprint أفضل من IP فقط | IP سهل التجاوز (VPN, CG-NAT, dynamic IP) |
| 3 طبقات (device + IP + suspicious) | حماية متعددة المستويات |
| SuspiciousActivity لا يحظر أبداً | لا نريد حظر صاحب الحساب الحقيقي، فقط ننبهه |
| Fingerprint يسبق IP | بصمة الجهاز أكثر دقة وثباتاً من IP |
| `blocked_until` بدلاً من `is_blocked` boolean | فك الحظر تلقائياً بدون حاجة cron job |
| `ip_first_seen` nullable | مرونة عند الحظر من سياقات بدون IP |
