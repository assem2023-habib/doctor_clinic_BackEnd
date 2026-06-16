import { check, sleep } from 'k6';
import { get } from '../lib/client.js';
import { randomItem, randomSleep } from '../lib/helpers.js';
import { STAGES_STRESS, DEFAULT_THRESHOLDS } from '../config/options.js';

export const options = {
  stages: STAGES_STRESS,
  thresholds: {
    ...DEFAULT_THRESHOLDS,
    http_req_failed: ['rate<0.10'],
  },
  noConnectionReuse: true,
};

const ENDPOINTS = [
  '/v1/doctors?limit=20',
  '/v1/specializations',
  '/v1/countries',
  '/v1/receptionists',
];

export default function () {
  const ep = randomItem(ENDPOINTS);
  const res = get(ep);
  check(res, {
    'stress request responded': (r) => r.status < 500,
  });
  res.tags = { name: `stress_${ep.replace(/[\/?]/g, '_')}` };

  sleep(randomSleep(0.5, 1));
}
