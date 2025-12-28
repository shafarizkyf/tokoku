import http from 'k6/http';
import { check } from 'k6';
import { BASE_URL, THRESHOLDS } from './config.js';

export const options = {
  vus: 1,
  duration: '10s',
  thresholds: THRESHOLDS,
};

export default function () {
  const res = http.get(`${BASE_URL}/api/health`);

  check(res, {
    'status is 200': (r) => r.status === 200,
  });
}
