export const BASE_URL = __ENV.BASE_URL || 'http://192.168.0.219:7890';

export const DEFAULT_HEADERS = {
  'Accept': 'application/json',
  'Content-Type': 'application/json',
};

export const THRESHOLDS = {
  http_req_failed: ['rate<0.01'],
  http_req_duration: ['p(95)<800'],
};
