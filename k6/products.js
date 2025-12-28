import http from 'k6/http';
import { sleep, check } from 'k6';
import { BASE_URL, THRESHOLDS } from './config.js';

export const options = {
  stages: [
    { duration: '30s', target: 10 },
    { duration: '1m', target: 30 },
    { duration: '30s', target: 0 },
  ],
  thresholds: THRESHOLDS,
};

export default function () {
  const res = http.get(`${BASE_URL}/api/products`);

  check(res, {
    '200 OK': (r) => r.status === 200,
  });

  sleep(1);
}
