import { exec } from 'k6/execution';

// This file orchestrates all scenarios sequentially.
// Run with: k6 run run-all.js
//
// To run individual scenarios:
//   k6 run scenarios/browsing.js
//   k6 run scenarios/active-users.js
//   etc.

export const options = {
  scenarios: {
    browsing: {
      executor: 'per-vu-iterations',
      vus: 50,
      iterations: 1,
      maxDuration: '10m',
      exec: 'browsingScenario',
      startTime: '0s',
    },
    active_users: {
      executor: 'ramping-vus',
      exec: 'activeUsersScenario',
      startTime: '5m',
      stages: [
        { duration: '1m', target: 20 },
        { duration: '3m', target: 100 },
        { duration: '5m', target: 100 },
        { duration: '1m', target: 0 },
      ],
    },
    spike: {
      executor: 'ramping-arrival-rate',
      exec: 'spikeScenario',
      startTime: '15m',
      stages: [
        { duration: '10s', target: 10 },
        { duration: '30s', target: 200 },
        { duration: '2m', target: 200 },
        { duration: '30s', target: 10 },
      ],
    },
    notification_polling: {
      executor: 'per-vu-iterations',
      vus: 50,
      iterations: 5,
      maxDuration: '10m',
      exec: 'notificationPollingScenario',
      startTime: '18m',
    },
    appointment_lifecycle: {
      executor: 'per-vu-iterations',
      vus: 30,
      iterations: 1,
      maxDuration: '10m',
      exec: 'appointmentLifecycleScenario',
      startTime: '23m',
    },
    stress: {
      executor: 'ramping-vus',
      exec: 'stressScenario',
      startTime: '28m',
      stages: [
        { duration: '10s', target: 5 },
        { duration: '10s', target: 10 },
        { duration: '10s', target: 15 },
        { duration: '10s', target: 20 },
        { duration: '10s', target: 25 },
        { duration: '10s', target: 30 },
        { duration: '10s', target: 35 },
        { duration: '10s', target: 40 },
        { duration: '10s', target: 45 },
        { duration: '10s', target: 50 },
        { duration: '10s', target: 55 },
        { duration: '10s', target: 60 },
        { duration: '10s', target: 65 },
        { duration: '10s', target: 70 },
        { duration: '10s', target: 75 },
        { duration: '10s', target: 80 },
        { duration: '10s', target: 85 },
        { duration: '10s', target: 90 },
        { duration: '10s', target: 95 },
        { duration: '10s', target: 100 },
        { duration: '10s', target: 105 },
        { duration: '10s', target: 110 },
        { duration: '10s', target: 115 },
        { duration: '10s', target: 120 },
      ],
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.05'],
    http_req_duration: ['p(95)<1000'],
  },
};

export function browsingScenario() {
  require('./scenarios/browsing.js').default();
}

export function activeUsersScenario() {
  require('./scenarios/active-users.js').default();
}

export function spikeScenario() {
  require('./scenarios/spike.js').default();
}

export function notificationPollingScenario() {
  require('./scenarios/notification-polling.js').default();
}

export function appointmentLifecycleScenario() {
  require('./scenarios/appointment-lifecycle.js').default();
}

export function stressScenario() {
  require('./scenarios/stress.js').default();
}
