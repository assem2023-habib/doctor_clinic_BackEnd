# Caching & Redis

## Overview

التخزين المؤقت للأطباء والتقييمات والمواقع (دول/مدن) والتخصصات باستخدام version-based cache keys مع Redis.

---

## Redis Setup

### Docker (Development)

```yaml
# docker-compose.yml
services:
  redis:
    build:
      context: ./docker/redis
      dockerfile: Dockerfile
    container_name: doctor-clinic-redis
    ports:
      - "6379:6379"
    volumes:
      - redis-data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 5s
      timeout: 3s
      retries: 5

volumes:
  redis-data:
```

### Redis Config (`docker/redis/redis.conf`)

| الإعداد | القيمة | الشرح |
|---------|--------|-------|
| `bind` | `0.0.0.0` | يستمع على جميع الواجهات (للاتصال من الـ host) |
| `protected-mode` | `no` | معطل لأن الاتصال يأتي من الـ host وليس loopback داخل Docker |
| `maxmemory` | `512mb` | حد الذاكرة |
| `maxmemory-policy` | `allkeys-lru` | إزالة الأقل استخداماً عند الوصول للحد |
| `save` | `900 1`, `300 10`, `60 10000` | AOF persistence |
| `rename-command` | `FLUSHDB`, `FLUSHALL`, `CONFIG` | تعطيل الأوامر الخطيرة |

### Environment (`.env`)

```env
REDIS_CLIENT=phpredis
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
```

> **ملاحظة:** يستخدم `phpredis` extension (وليس `predis/predis`). تم تثبيت `php_redis.dll` لـ PHP 8.3 TS x64.

### Verification

```bash
# من داخل Laravel
php artisan tinker
> Cache::store('redis')->set('test', 'ok')
> Cache::store('redis')->get('test')
# => "ok"

# من CLI
redis-cli -h 127.0.0.1 ping
# => PONG
```

---

## Caching Architecture

### Design Pattern: Version-based Cache Keys + Observer Pattern

```
Version Key                 Cache Entries
─────────────               ─────────────────
doctors:cache_version ──►   doctors:index:v{version}:{hash}
  (integer)                 doctors:ratings:v{version}:{id}:{hash}

ratings:cache_version ──►  ratings:index:v{version}:{hash}
  (integer)                 ratings:show:v{version}:{id}

countries:cache_version ──► countries:index:v{version}:{hash}
  (integer)                 countries:show:v{version}:{id}

cities:cache_version ──►   cities:index:v{version}:{hash}
  (integer)                 cities:show:v{version}:{id}

specializations:cache_ver ─► specializations:index:v{version}:{hash}
  (integer)                 specializations:show:v{version}:{id}
```

### How It Works

1. **Model Event** (`saved`/`deleted`) ← **Observer Pattern**
   - `Doctor::saved/deleted` → `Cache::increment('doctors:cache_version')`
   - `Rating::saved/deleted` → `Cache::increment('ratings:cache_version')` (& `doctors:` if `type=user`)
   - `Country::saved/deleted` → `Cache::increment('countries:cache_version')`
   - `City::saved/deleted` → `Cache::increment('cities:cache_version')`
   - `Specialization::saved/deleted` → `Cache::increment('specializations:cache_version')`

2. **Controller Read** ← **Cache-Aside Pattern**
   ```php
   $version = Cache::get('doctors:cache_version', 0);
   $key = 'doctors:index:v' . $version . ':' . md5(serialize($filters));
   $data = Cache::remember($key, 172800, fn() => query());
   ```

3. **Invalidation**: Increment version → old keys become inaccessible → next request computes fresh data

### TTL (Time To Live)

| المدة | الثواني | الاستخدام |
|-------|---------|-----------|
| يومان | 172800 | جميع القوائم المخبأة (Doctors, Ratings, Countries, Cities, Specializations) |

---

## ClearsCache Trait

**File:** `app/Domains/Shared/Traits/ClearsCache.php`

```php
trait ClearsCache
{
    protected static function bootClearsCache(): void
    {
        static::saved(static function ($model) {
            foreach ($model->cacheVersionsToIncrement() as $key) {
                Cache::increment($key);
            }
        });

        static::deleted(static function ($model) {
            foreach ($model->cacheVersionsToIncrement() as $key) {
                Cache::increment($key);
            }
        });
    }
}
```

### Usage

أضف الـ trait وأ Implement `cacheVersionsToIncrement()`:

```php
class Doctor extends Model
{
    use HasUuidV7, ClearsCache;

    public function cacheVersionsToIncrement(): array
    {
        return ['doctors:cache_version'];
    }
}
```

---

## Cache Invalidation Matrix

### Doctors

| الإجراء | الـ Endpoint | الـ Cache Key | يبطل؟ |
|---------|-------------|---------------|-------|
| قراءة القائمة | `GET /api/v1/doctors` | `doctors:index:v{version}:{hash}` | لا |
| قراءة طبيب | `GET /api/v1/doctors/{doctor}` | غير مخبأ (user-specific) | لا |
| قراءة تقييمات طبيب | `GET /api/v1/doctors/{doctor}/ratings` | `doctors:ratings:v{version}:{id}:{hash}` | لا |
| إنشاء طبيب | `POST /api/v1/doctors` | — | ✅ `doctors:cache_version++` |
| تحديث طبيب | `PUT/PATCH /api/v1/doctors/{doctor}` | — | ✅ `doctors:cache_version++` |
| حذف طبيب | `DELETE /api/v1/doctors/{doctor}` | — | ✅ `doctors:cache_version++` |
| تفعيل حساب | `PUT /api/v1/doctors/{doctor}/activate-account` | — | ✅ `doctors:cache_version++` |

### Ratings

| الإجراء | الـ Endpoint | الـ Cache Key | يبطل؟ |
|---------|-------------|---------------|-------|
| قراءة القائمة | `GET /api/v1/ratings` | `ratings:index:v{version}:{hash}` | لا |
| قراءة تقييم | `GET /api/v1/ratings/{rating}` | `ratings:show:v{version}:{id}` | لا |
| إنشاء تقييم (`type=user`) | `POST /api/v1/ratings` | — | ✅ `ratings:cache_version++` + `doctors:cache_version++` |
| إنشاء تقييم (`type!=user`) | `POST /api/v1/ratings` | — | ✅ `ratings:cache_version++` فقط |
| تحديث تقييم | `PUT /api/v1/ratings/{rating}` | — | ✅ `ratings:cache_version++` |
| حذف تقييم (`type=user`) | `DELETE /api/v1/ratings/{rating}` | — | ✅ `ratings:cache_version++` + `doctors:cache_version++` |
| حذف تقييم (`type!=user`) | `DELETE /api/v1/ratings/{rating}` | — | ✅ `ratings:cache_version++` فقط |

### Cross-invalidation

عند إنشاء/تحديث/حذف تقييم من نوع `user` (خاص بطبيب)، يبطل كاش **الأطباء** أيضاً لأن متوسط التقييمات يظهر في `GET /api/v1/doctors/{doctor}`:

```
Rating::saved()/deleted()
├── type = user   → increment('ratings') + increment('doctors')
└── type ≠ user   → increment('ratings') only
```

---

## Cached Endpoints

### Doctors & Ratings

| Method | Path | Cache Key | TTL |
|--------|------|-----------|-----|
| `GET` | `/api/v1/doctors` | `doctors:index:v{ver}:{md5(filters)}` | 172800s |
| `GET` | `/api/v1/doctors/{doctor}/ratings` | `doctors:ratings:v{ver}:{id}:{md5(filters)}` | 172800s |
| `GET` | `/api/v1/ratings` | `ratings:index:v{ver}:{md5(filters)}` | 172800s |
| `GET` | `/api/v1/ratings/{rating}` | `ratings:show:v{ver}:{id}` | 172800s |

> **ملاحظة:** `GET /api/v1/doctors/{doctor}` (show) **غير مخبأ** لأن الـ Response يعتمد على هوية المستخدم (`has_rated`, `supervision_request` للمريض).

### Countries & Cities

| Method | Path | Cache Key | TTL | Middleware |
|--------|------|-----------|-----|-----------|
| `GET` | `/api/v1/countries` | `countries:index:v{ver}:{md5(filters)}` | 172800s | **بدون** (عام) |
| `GET` | `/api/v1/countries/{country}` | `countries:show:v{ver}:{id}` | 172800s | **بدون** (عام) |
| `GET` | `/api/v1/cities` | `cities:index:v{ver}:{md5(filters)}` | 172800s | **بدون** (عام) |
| `GET` | `/api/v1/cities/{city}` | `cities:show:v{ver}:{id}` | 172800s | **بدون** (عام) |

> **ملاحظة:** هذه الـ endpoints عامة (غير مصادقة) ومستخدمة في صفحة التسجيل (register). التخزين المؤقت عبر Redis يمنع الضغط المتكرر على قاعدة البيانات من الزوار غير المسجلين.

### Cache Invalidation — Countries & Cities

| الإجراء | الـ Endpoint | يبطل؟ |
|---------|-------------|-------|
| قراءة القائمة | `GET /api/v1/countries` | لا |
| عرض دولة | `GET /api/v1/countries/{country}` | لا |
| إنشاء دولة | `POST /api/v1/countries` | ✅ `countries:cache_version++` |
| تحديث دولة | `PUT /api/v1/countries/{country}` | ✅ `countries:cache_version++` |
| حذف دولة | `DELETE /api/v1/countries/{country}` | ✅ `countries:cache_version++` |
| قراءة المدن | `GET /api/v1/cities` | لا |
| عرض مدينة | `GET /api/v1/cities/{city}` | لا |
| إنشاء مدينة | `POST /api/v1/cities` | ✅ `cities:cache_version++` |
| تحديث مدينة | `PUT /api/v1/cities/{city}` | ✅ `cities:cache_version++` |
| حذف مدينة | `DELETE /api/v1/cities/{city}` | ✅ `cities:cache_version++` |

### Specializations

| Method | Path | Cache Key | TTL |
|--------|------|-----------|-----|
| `GET` | `/api/v1/specializations` | `specializations:index:v{ver}:{md5(filters)}` | 172800s |
| `GET` | `/api/v1/specializations/{specialization}` | `specializations:show:v{ver}:{id}` | 172800s |

> **ملاحظة:** `show()` للتخصصات مخبأ (عكس `show()` للأطباء) لأن البيانات عامة ولا تعتمد على هوية المستخدم.

### Cache Invalidation — Specializations

| الإجراء | الـ Endpoint | يبطل؟ |
|---------|-------------|-------|
| قراءة القائمة | `GET /api/v1/specializations` | لا |
| عرض تخصص | `GET /api/v1/specializations/{specialization}` | لا |
| إنشاء تخصص | `POST /api/v1/specializations` | ✅ `specializations:cache_version++` |
| تحديث تخصص | `PUT /api/v1/specializations/{specialization}` | ✅ `specializations:cache_version++` |
| حذف تخصص | `DELETE /api/v1/specializations/{specialization}` | ✅ `specializations:cache_version++` |

---

## Adding Caching to New Endpoints

### 1. Add `ClearsCache` trait to the model

```php
use App\Domains\Shared\Traits\ClearsCache;

class YourModel extends Model
{
    use ClearsCache;

    public function cacheVersionsToIncrement(): array
    {
        return ['your:cache_version'];
    }
}
```

### 2. Wrap the controller query

```php
use Illuminate\Support\Facades\Cache;

public function index(Request $request): JsonResponse
{
    $version = Cache::get('your:cache_version', 0);
    $key = 'your:index:v' . $version . ':' . md5(serialize($request->only(['filter1', 'page', 'limit'])));

    $data = Cache::remember($key, 172800, function () use ($request) {
        return YourModel::query()->paginate();
    });

    return response()->json($data);
}
```

### 3. Verify

```bash
# First request — cache miss
curl -i http://localhost:8000/api/v1/your-endpoint

# Modify data (triggers invalidation)
curl -X POST http://localhost:8000/api/v1/your-endpoint ...

# Second request — cache miss (new version), fresh data
curl -i http://localhost:8000/api/v1/your-endpoint
```

---

## Key Differences: Tags vs Version-based

| الخاصية | Cache Tags | Version-based (مستعمل) |
|---------|-----------|----------------------|
| يدعم `array` driver | ❌ | ✅ |
| يدعم `redis` driver | ✅ | ✅ |
| يدعم `file` driver | ❌ | ✅ |
| إبطال مجموعة كاملة | `Cache::tags(['x'])->flush()` | `Cache::increment('x:version')` |
| Race condition | Less (atomic flush) | Acceptable (atomic increment) |
| Testing (phpunit) | ❌ (array store) | ✅ |

> **لماذا Version-based؟** لأن Laravel's `array` cache driver لا يدعم `Cache::tags()`. باستخدام version-based تعمل الاختبارات (`CACHE_STORE=array`) و Redis على حد سواء بنفس الكود.

---

## Tests

All cache tests are in the existing domain test files:

| Test File | Tests |
|-----------|-------|
| `tests/Feature/Doctors/DoctorTest.php` | Cache hit returns stale data, invalidation on create/update/delete, cross-invalidation from ratings |
| `tests/Feature/Ratings/RatingTest.php` | Cache hit on list + show, invalidation on create/update |
| `tests/Feature/Locations/CountryTest.php` | Cache hit returns stale data, invalidation on create/update/delete |
| `tests/Feature/Locations/CityTest.php` | Cache hit returns stale data, invalidation on create/update/delete |
| `tests/Feature/Doctors/SpecializationTest.php` | Cache hit returns stale data, invalidation on create/update/delete |

Run with: `php artisan test`
