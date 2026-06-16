import { check, sleep } from 'k6';
import { get, post } from '../lib/client.js';
import { randomSleep, randomItem } from '../lib/helpers.js';
import { DEFAULT_THRESHOLDS } from '../config/options.js';
import { loginWithRetry } from '../lib/auth.js';

export const options = {
  stages: [
    { duration: '30s', target: 50 },
    { duration: '4m', target: 50 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    ...DEFAULT_THRESHOLDS,
    'http_req_duration{name:poll_notifications}': ['p(95)<300'],
    'http_req_duration{name:mark_read}': ['p(95)<400'],
  },
};

const PATIENT_EMAILS = Array.from({ length: 50 }, (_, i) => `patient_test_${i + 1}@example.com`);
const PASSWORD = 'Password1!';

export default function () {
  const email = randomItem(PATIENT_EMAILS);
  const token = loginWithRetry(email, PASSWORD);
  if (!token) return;

  // Poll notifications every iteration (simulates Flutter polling every 10s)
  let res = get('/v1/notifications?limit=20', token);
  check(res, { 'poll notifications 200': (r) => r.status === 200 });
  res.tags = { name: 'poll_notifications' };
  sleep(randomSleep(0.5, 1));

  // Mark some as read
  res = post('/v1/notifications/read', { ids: [] }, token);
  check(res, { 'mark read 200': (r) => r.status === 200 });
  res.tags = { name: 'mark_read' };

  sleep(randomSleep(5, 10));
}
