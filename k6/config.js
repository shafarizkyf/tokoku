export const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export const DEFAULT_HEADERS = {
  'Content-Type': 'application/json',
};

export const THRESHOLDS = {
  http_req_failed: ['rate<0.01'],
  http_req_duration: ['p(95)<800'],
};
