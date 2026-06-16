import { check, sleep } from 'k6';
import { get, post } from '../lib/client.js';
import { randomSleep, randomItem, pickRandomByRole } from '../lib/helpers.js';
import { DEFAULT_THRESHOLDS } from '../config/options.js';
import { loginWithRetry } from '../lib/auth.js';

export const options = {
  stages: [
    { duration: '1m', target: 10 },
    { duration: '3m', target: 30 },
    { duration: '2m', target: 0 },
  ],
  thresholds: {
    ...DEFAULT_THRESHOLDS,
    'http_req_duration{name:lifecycle_login}': ['p(95)<2000'],
    'http_req_duration{name:lifecycle_create_appointment}': ['p(95)<800'],
    'http_req_duration{name:lifecycle_set_time}': ['p(95)<800'],
    'http_req_duration{name:lifecycle_complete}': ['p(95)<800'],
  },
};

const PASSWORD = 'Password1!';

export default function () {
  const vuId = __VU;
  const patientEmail = `patient_test_${((vuId - 1) % 200) + 1}@example.com`;
  const doctorEmail = `doctor_test_${((vuId - 1) % 50) + 1}@example.com`;

  // Step 1: Patient logs in
  let patientToken = loginWithRetry(patientEmail, PASSWORD);
  if (!patientToken) return;
  sleep(randomSleep(0.5, 1));

  // Step 2: Patient views doctors
  let res = get('/v1/doctors?limit=20', patientToken);
  check(res, { 'view doctors 200': (r) => r.status === 200 });
  sleep(randomSleep(0.5, 1));

  // Step 3: Patient creates appointment
  res = post('/v1/appointments', {
    reason: 'K6 lifecycle test - routine checkup',
    notes: 'Created during load test',
  }, patientToken, { name: 'lifecycle_create_appointment' });
  check(res, { 'create appointment 201': (r) => r.status === 201 });
  sleep(randomSleep(0.5, 1));

  // Step 4: Doctor logs in
  let doctorToken = loginWithRetry(doctorEmail, PASSWORD);
  if (!doctorToken) return;
  sleep(randomSleep(0.5, 1));

  // Step 5: Doctor views appointments
  res = get('/v1/appointments?limit=10', doctorToken);
  check(res, { 'doctor view appointments 200': (r) => r.status === 200 });
  sleep(randomSleep(0.5, 1));

  // Step 6: Doctor sets time (uses most recent appointment)
  res = get('/v1/appointments?limit=1&status=requested', doctorToken);
  if (res.status === 200) {
    const appointments = JSON.parse(res.body);
    if (appointments.data && appointments.data.length > 0) {
      const apptId = appointments.data[0].id;
      res = post(`/v1/appointments/${apptId}/set-time`, {
        appointment_date: new Date(Date.now() + 86400000).toISOString().split('T')[0],
        start_time: '10:00',
        end_time: '10:30',
      }, doctorToken, { name: 'lifecycle_set_time' });
      check(res, { 'set time 200': (r) => r.status === 200 });
    }
  }
  sleep(randomSleep(0.5, 1));

  // Step 7: Complete appointment
  res = get('/v1/appointments?limit=1&status=accepted', doctorToken);
  if (res.status === 200) {
    const appointments = JSON.parse(res.body);
    if (appointments.data && appointments.data.length > 0) {
      const apptId = appointments.data[0].id;
      res = post(`/v1/appointments/${apptId}/start`, {}, doctorToken);
      check(res, { 'start appointment 200': (r) => r.status === 200 });
      sleep(randomSleep(0.5, 1));

      res = post(`/v1/appointments/${apptId}/complete`, {}, doctorToken, { name: 'lifecycle_complete' });
      check(res, { 'complete appointment 200': (r) => r.status === 200 });
    }
  }
}
