import http from 'k6/http';
import { check, sleep } from 'k6';
import { SharedArray } from 'k6/data';
import { BASE_URL, DEFAULT_HEADERS, getRandomInt } from './config.js';

let token; // VU-local memory
let hasCheckedOut = false;
const users = new SharedArray('users', () => {
  return JSON.parse(open('./data/users.json'));
});

export const options = {
  scenarios: {
    checkout_load: {
      executor: 'constant-vus',
      vus: 50,          // start lower than cart
      duration: '2m',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<3000'], // checkout is slower by nature
  },
};


export default function () {
  if (!token) {
    // Each VU sticks to ONE user (important!)
    const user = users[__VU % users.length];

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

    check(loginRes, {
      'login ok': (r) => r.status === 200,
    });

    token = loginRes.json().token;
  }

  if (hasCheckedOut) {
    sleep(10);
    return;
  }

  const variationId = getRandomInt(1, 30);

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
    Authorization: `Bearer ${token}`
  };

  const res = http.post(`${BASE_URL}/api/orders`, payload, { headers });

  check(res, {
    'status is 200': (r) => r.status === 200,
    'checkout success': (r) => {
      const body = r.json();
      return body && body.success === true;
    },
  });

  hasCheckedOut = true;

  // VERY IMPORTANT: simulate real user thinking time
  sleep(3 + Math.random() * 5); // 3–8 seconds
}
