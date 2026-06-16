import { check, sleep } from 'k6';
import { get } from '../lib/client.js';
import { randomSleep } from '../lib/helpers.js';
import { DEFAULT_THRESHOLDS, STAGES_RAMP_UP } from '../config/options.js';

export const options = {
  stages: STAGES_RAMP_UP,
  thresholds: {
    ...DEFAULT_THRESHOLDS,
    'http_req_duration{name:get_specializations}': ['p(95)<200'],
    'http_req_duration{name:get_doctors}': ['p(95)<300'],
    http_reqs: ['count>1000'],
  },
};

export default function () {
  // Browse public reference data (cached, no auth)
  const endpoints = [
    { path: '/v1/specializations', name: 'get_specializations' },
    { path: '/v1/countries', name: 'get_countries' },
    { path: '/v1/cities', name: 'get_cities' },
    { path: '/v1/receptionists', name: 'get_receptionists' },
  ];

  for (const ep of endpoints) {
    const res = get(ep.path, null, { name: ep.name });
    check(res, {
      [`${ep.name} status 200`]: (r) => r.status === 200,
    });
    sleep(randomSleep(1, 3));
  }

  // Browse doctors pages
  for (let page = 1; page <= 3; page++) {
    const res = get(`/v1/doctors?page=${page}&limit=20`, null, { name: 'get_doctors' });
    check(res, {
      'doctors page status 200': (r) => r.status === 200,
    });
    sleep(randomSleep(1, 2));
  }
}
