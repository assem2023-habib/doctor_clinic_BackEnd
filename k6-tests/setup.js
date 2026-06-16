import { check } from 'k6';
import http from 'k6/http';
import { BASE_URL, HEADERS_JSON } from './config/options.js';

export function setup() {
  console.log('K6: Setup phase - verifying API is reachable');

  // Test that the API is alive
  const res = http.get(`${BASE_URL}/v1/specializations`, { headers: HEADERS_JSON });
  check(res, {
    'API is reachable': (r) => r.status === 200,
  });

  if (res.status !== 200) {
    console.error(`Setup failed: API not reachable at ${BASE_URL}`);
    return { ready: false };
  }

  return {
    ready: true,
    baseUrl: BASE_URL,
    startedAt: new Date().toISOString(),
  };
}
