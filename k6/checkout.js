import http from 'k6/http';
import { check, sleep } from 'k6';
import { SharedArray } from 'k6/data';
import { BASE_URL, DEFAULT_HEADERS, THRESHOLDS, getRandomInt } from './config.js';

const users = new SharedArray('users', () => {
  return JSON.parse(open('./data/users.json'));
});

export const options = {
  scenarios: {
    checkout_load: {
      executor: 'per-vu-iterations',
      vus: 200,              // Total unique users
      iterations: 1,         // Each user does exactly 1 checkout
      maxDuration: '5m',     // A safety timeout
    },
  },
  thresholds: {
    http_req_duration: ['p(95)<300'], // checkout is slower by nature
  },
};


export default function () {
  // Only happens once per iteration
  const user = users[(__VU - 1) % users.length];

  const loginRes = http.post(
    `${BASE_URL}/api/test/login/${user.id}`,
    null,
    {
      headers: {
        ...DEFAULT_HEADERS,
        'X-Test-Key': __ENV.TEST_KEY,
      },
    }
  );

  check(loginRes, { 'login ok': (r) => r.status === 200 });
  const token = loginRes.json().token;

  if (loginRes.status !== 200) {
    console.error(`VU ${__VU} - Login failed: ${loginRes.status} - ${loginRes.body}`);
    return;
  }

  const variationId = getRandomInt(1, 60);
  const payload = JSON.stringify({
    items: [
      {
        product_variation_id: variationId,
        quantity: getRandomInt(1, 3),
      },
    ],
    payment_method: 'BNIVA',
    shipping: {
      receiver_name: 'Kiki',
      province_id: '33',
      regency_id: '3302',
      district_id: '330227',
      village_id: '3302271003',
      postal_code: '53125',
      address: 'Jl. Test',
      shipping_note: 'gerbang putih',
      address_full:
      'Jl. Test, Sumampir, Purwokerto Utara, KAB. BANYUMAS, JAWA TENGAH (53125)',
      phone_number: '081234567890',
    },
    delivery: {
      shipping_name: 'JNE',
      service_name: 'JNEFlat',
    },
  });

  const headers = {
    ...DEFAULT_HEADERS,
    Authorization: `Bearer ${token}`,
    'X-Test-Key': __ENV.TEST_KEY,
  };

  const res = http.post(`${BASE_URL}/api/orders`, payload, { headers });

  check(res, {
    'checkout success': (r) => r.status === 200,
  });

  if (res.status !== 200) {
    console.error(`VU ${__VU} failed: ${res.status}`);
  }

  sleep(1);
}
