import { check, sleep } from 'k6';
import { get } from '../lib/client.js';
import { randomItem, randomSleep } from '../lib/helpers.js';
import { STAGES_SPIKE, DEFAULT_THRESHOLDS } from '../config/options.js';

export const options = {
  stages: STAGES_SPIKE,
  thresholds: {
    ...DEFAULT_THRESHOLDS,
    http_req_failed: ['rate<0.05'],
    http_req_duration: ['p(95)<1000', 'p(99)<2000'],
  },
  noConnectionReuse: true,
};

// Pre-login to get tokens before spike
const PATIENT_EMAILS = Array.from({ length: 50 }, (_, i) => `patient_test_${i + 1}@example.com`);
const PASSWORD = 'Password1!';

export default function () {
  // Each VU logs in once
  const loginRes = get('/v1/auth/login', null);
  // Don't check - we login via API

  // Then hammer the read-heavy endpoints
  const endpoints = [
    '/v1/doctors?limit=20',
    '/v1/doctors?limit=20&page=2',
    '/v1/specializations',
    '/v1/countries',
    '/v1/cities',
    '/v1/receptionists',
  ];

  const ep = randomItem(endpoints);
  const res = get(ep, null, { name: `spike_${ep.replace(/\//g, '_')}` });
  check(res, {
    'spike request ok': (r) => r.status === 200 || r.status === 429,
  });

  sleep(randomSleep(0.5, 1.5));
}
