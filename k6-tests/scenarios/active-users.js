import { check, sleep } from 'k6';
import { get, post } from '../lib/client.js';
import { randomItem, randomSleep, pickRandomByRole } from '../lib/helpers.js';
import { DEFAULT_THRESHOLDS } from '../config/options.js';
import { loginWithRetry } from '../lib/auth.js';

export const options = {
  stages: [
    { duration: '1m', target: 20 },
    { duration: '3m', target: 100 },
    { duration: '5m', target: 100 },
    { duration: '1m', target: 0 },
  ],
  thresholds: {
    ...DEFAULT_THRESHOLDS,
    'http_req_duration{name:get_dashboard}': ['p(95)<500'],
    'http_req_duration{name:post_appointment}': ['p(95)<800'],
    'http_req_duration{name:get_appointments}': ['p(95)<400'],
  },
};

const PATIENT_EMAILS = Array.from({ length: 50 }, (_, i) => `patient_test_${i + 1}@example.com`);
const DOCTOR_EMAILS = Array.from({ length: 30 }, (_, i) => `doctor_test_${i + 1}@example.com`);
const RECEPTIONIST_EMAILS = Array.from({ length: 5 }, (_, i) => `receptionist_test_${i + 1}@example.com`);
const PASSWORD = 'Password1!';

export default function () {
  const vuRole = __VU % 3;
  let email, token;

  if (vuRole === 0) {
    // Patient VUs
    email = randomItem(PATIENT_EMAILS);
    token = loginWithRetry(email, PASSWORD);
    if (!token) return;

    patientWorkflow(token, email);

  } else if (vuRole === 1) {
    // Doctor VUs
    email = randomItem(DOCTOR_EMAILS);
    token = loginWithRetry(email, PASSWORD);
    if (!token) return;

    doctorWorkflow(token);

  } else {
    // Receptionist VUs
    email = randomItem(RECEPTIONIST_EMAILS);
    token = loginWithRetry(email, PASSWORD);
    if (!token) return;

    receptionistWorkflow(token);
  }
}

function patientWorkflow(token, email) {
  // Dashboard (every 30s)
  let res = get('/v1/dashboard', token, { name: 'get_dashboard' });
  check(res, { 'patient dashboard 200': (r) => r.status === 200 });
  sleep(randomSleep(1, 2));

  // Appointments list (every 60s)
  res = get('/v1/appointments?limit=10', token, { name: 'get_appointments' });
  check(res, { 'patient appointments 200': (r) => r.status === 200 });
  sleep(randomSleep(1, 2));

  // Create an appointment (once per iteration)
  res = post('/v1/appointments', {
    reason: 'K6 load test appointment',
    notes: 'Automated test',
  }, token, { name: 'post_appointment' });
  check(res, { 'patient create appointment 201': (r) => r.status === 201 });
  sleep(randomSleep(1, 3));
}

function doctorWorkflow(token) {
  let res = get('/v1/dashboard', token, { name: 'get_dashboard' });
  check(res, { 'doctor dashboard 200': (r) => r.status === 200 });
  sleep(randomSleep(1, 2));

  res = get('/v1/appointments?limit=10', token, { name: 'get_appointments' });
  check(res, { 'doctor appointments 200': (r) => r.status === 200 });
  sleep(randomSleep(1, 2));
}

function receptionistWorkflow(token) {
  let res = get('/v1/dashboard', token, { name: 'get_dashboard' });
  check(res, { 'receptionist dashboard 200': (r) => r.status === 200 });
  sleep(randomSleep(1, 2));

  res = get('/v1/appointments?limit=10', token, { name: 'get_appointments' });
  check(res, { 'receptionist appointments 200': (r) => r.status === 200 });
  sleep(randomSleep(1, 2));
}
