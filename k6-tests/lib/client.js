import http from 'k6/http';
import { BASE_URL, HEADERS_JSON } from '../config/options.js';

export function get(path, token = null) {
  const headers = { ...HEADERS_JSON };
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }
  return http.get(`${BASE_URL}${path}`, { headers });
}

export function post(path, body, token = null) {
  const headers = { ...HEADERS_JSON };
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }
  return http.post(`${BASE_URL}${path}`, JSON.stringify(body), { headers });
}

export function put(path, body, token = null) {
  const headers = { ...HEADERS_JSON };
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }
  return http.put(`${BASE_URL}${path}`, JSON.stringify(body), { headers });
}

export function del(path, token = null) {
  const headers = { ...HEADERS_JSON };
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }
  return http.del(`${BASE_URL}${path}`, null, { headers });
}
