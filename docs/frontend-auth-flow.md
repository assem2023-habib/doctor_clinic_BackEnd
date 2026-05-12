# Frontend Auth Flow

## Tokens

| Token | Type | Lifetime | Usage |
|-------|------|----------|-------|
| `access_token` | Bearer JWT | 1 year (configurable) | Sent in `Authorization: Bearer <token>` header |
| `refresh_token` | Opaque string | 2 years (configurable) | Used to get new tokens when `access_token` expires |

## All Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/v1/auth/register/patient` | ❌ | Register + auto-login |
| POST | `/api/v1/auth/register/doctor` | ❌ | Register + auto-login |
| POST | `/api/v1/auth/register/receptionist` | ❌ | Register + auto-login |
| POST | `/api/v1/auth/login` | ❌ | Login by email + password |
| POST | `/api/v1/auth/logout` | ✅ | Revoke current token |
| POST | `/api/v1/auth/refresh` | ❌ | Get new tokens using `refresh_token` |
| PUT | `/api/v1/auth/password` | ✅ | Change password (needs old_password) |
| DELETE | `/api/v1/auth/account` | ✅ | Delete account permanently |
| GET | `/api/v1/auth/me` | ✅ | Get current user profile |

## Response Structures

### Login / Register (201/200)

```json
{
    "access_token": "eyJ0eXAiOiJKV1Qi...",
    "refresh_token": "def50200...",
    "expires_in": 31536000,
    "token_type": "Bearer",
    "user": { ... }
}
```

### Refresh Token (200)

```json
{
    "access_token": "eyJ0eXAiOiJKV1Qi...",
    "refresh_token": "def50200...",
    "expires_in": 31536000,
    "token_type": "Bearer"
}
```

> Note: Refresh endpoint does NOT return user data. Call `/me` separately.

### Error Response (4xx)

```json
{
    "message": "Invalid credentials",
    "errors": {
        "email": ["These credentials do not match our records."]
    }
}
```

## Client-Side Token Storage (JavaScript Example)

```javascript
// After login/register
const { access_token, refresh_token, expires_in, user } = response.data;

localStorage.setItem('access_token', access_token);
localStorage.setItem('refresh_token', refresh_token);
localStorage.setItem('token_expires_at', Date.now() + expires_in * 1000);
```

## Axios Interceptor – Auto Refresh

```javascript
import axios from 'axios';

const api = axios.create({
    baseURL: 'http://localhost:8000/api/v1',
});

// Attach token to every request
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('access_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Handle 401 – auto refresh + retry
let isRefreshing = false;
let failedQueue = [];

const processQueue = (error, token = null) => {
    failedQueue.forEach((prom) => {
        if (error) prom.reject(error);
        else prom.resolve(token);
    });
    failedQueue = [];
};

api.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config;

        if (error.response?.status === 401 && !originalRequest._retry) {
            if (isRefreshing) {
                return new Promise((resolve, reject) => {
                    failedQueue.push({ resolve, reject });
                }).then((token) => {
                    originalRequest.headers.Authorization = `Bearer ${token}`;
                    return api(originalRequest);
                });
            }

            originalRequest._retry = true;
            isRefreshing = true;

            const refreshToken = localStorage.getItem('refresh_token');

            try {
                const { data } = await axios.post(
                    'http://localhost:8000/api/v1/auth/refresh',
                    { refresh_token: refreshToken }
                );

                localStorage.setItem('access_token', data.access_token);
                localStorage.setItem('refresh_token', data.refresh_token);

                processQueue(null, data.access_token);

                originalRequest.headers.Authorization = `Bearer ${data.access_token}`;
                return api(originalRequest);
            } catch (refreshError) {
                processQueue(refreshError, null);
                localStorage.clear();
                window.location.href = '/login';
                return Promise.reject(refreshError);
            } finally {
                isRefreshing = false;
            }
        }

        return Promise.reject(error);
    }
);

export default api;
```

## Important Notes

1. **Refresh Token Flow**: When the API returns 401, the interceptor automatically calls `/refresh` with the stored `refresh_token`, stores the new tokens, and retries the failed request.
2. **Concurrent Requests**: If multiple requests fail with 401 at the same time, only ONE refresh call is made (the `isRefreshing` flag). The others are queued and replayed once the new token arrives.
3. **Refresh Token Expired**: If `/refresh` itself fails (401), it means the refresh token is also expired. Clear all tokens and redirect to login page.
4. **No User Data on Refresh**: The `/refresh` endpoint returns only tokens. After refreshing, call `/me` to get the current user profile.
5. **Security**: Tokens are effectively long-lived (1 year). For better security, implement shorter token lifetimes with more frequent refresh cycles, or use httpOnly cookies instead of localStorage.
