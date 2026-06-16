export const BASE_URL = 'http://localhost:8081/api';

export const DEFAULT_THRESHOLDS = {
  http_req_duration: ['p(95)<500', 'p(99)<1000'],
  http_req_failed: ['rate<0.01'],
};

export const STAGES_RAMP_UP = [
  { duration: '30s', target: 10 },
  { duration: '1m', target: 25 },
  { duration: '2m', target: 50 },
];

export const STAGES_SPIKE = [
  { duration: '10s', target: 0 },
  { duration: '30s', target: 200 },
  { duration: '2m', target: 200 },
  { duration: '30s', target: 0 },
];

export const STAGES_STRESS = (() => {
  const stages = [];
  for (let i = 5; i <= 300; i += 5) {
    stages.push({ duration: '10s', target: i });
  }
  return stages;
})();

export const HEADERS_JSON = {
  'Content-Type': 'application/json',
  'Accept': 'application/json',
};
