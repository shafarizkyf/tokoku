export const BASE_URL = __ENV.BASE_URL || 'http://192.168.0.219:7890';

export const DEFAULT_HEADERS = {
  'Accept': 'application/json',
  'Content-Type': 'application/json',
};

export const THRESHOLDS = {
  http_req_failed: ['rate<0.01'],
  http_req_duration: ['p(95)<800'],
  // Add p50 (Median) to see if the "average" experience is also slow
  'http_req_duration{expected_response:true}': ['p(50)<400'],
  // Check "Waiting" time (TTFB) to see if the server is processing slowly
  'http_req_waiting': ['p(95)<600'],
};

export const getRandomInt = (min, max) => {
  const minCeiled = Math.ceil(min);
  const maxFloored = Math.floor(max);
  return Math.floor(Math.random() * (maxFloored - minCeiled) + minCeiled);
};
