import { post } from './client.js';
import { check } from 'k6';

export function login(email, password) {
  const res = post('/v1/auth/login', { email, password });
  check(res, {
    'login successful': (r) => r.status === 200,
  });
  if (res.status !== 200) {
    console.error(`Login failed for ${email}: ${res.status} ${res.body}`);
    return null;
  }
  const body = JSON.parse(res.body);
  return body.data.access_token;
}

export function loginWithRetry(email, password, maxRetries = 3) {
  for (let i = 0; i < maxRetries; i++) {
    const token = login(email, password);
    if (token) return token;
  }
  return null;
}
