import http from 'k6/http';
import { check, sleep } from 'k6';
import { SharedArray } from 'k6/data';
import { BASE_URL, DEFAULT_HEADERS } from './config.js';

const searchTerms = new SharedArray('search terms', () =>
  JSON.parse(open('./data/search_terms.json'))
);

export const options = {
  scenarios: {
    search_load: {
      executor: 'ramping-vus',
      startVUs: 10,
      stages: [
        { duration: "30s", target: 50 },
        { duration: "30s", target: 150 },
        { duration: "1m30s", target: 300 },
        { duration: "30s", target: 0 },
      ],
      gracefulRampDown: '30s'
    }
  },

  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<1000'] // search can be slower
  }
};

export default function () {
  const term = searchTerms[Math.floor(Math.random() * searchTerms.length)];
  const res = http.get(`${BASE_URL}/api/search?keyword=${encodeURIComponent(term)}`, {
    headers: {
      ...DEFAULT_HEADERS,
    }
  });

  check(res, {
    "status is 200": (r) => r.status === 200,
    "data is array": () => Array.isArray(JSON.parse(res.body)),
  });

  sleep(1);
}
