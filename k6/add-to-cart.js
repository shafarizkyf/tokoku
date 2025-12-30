import http, { get } from "k6/http";
import { check, sleep } from "k6";
import { BASE_URL, DEFAULT_HEADERS, THRESHOLDS, getRandomInt } from "./config.js";

export const options = {
  // scenarios: {
  //   cart_load: {
  //     executor: "constant-vus",
  //     vus: 50,
  //     duration: "2m",
  //   },
  // },
  stages: [
    { duration: '30s', target: 100 },
    { duration: '1m', target: 300 },
    { duration: '30s', target: 0 },
  ],
  thresholds: THRESHOLDS
};

let token; // VU-local memory
const users = JSON.parse(open("./data/users.json"));

export default function () {
  if (!token) {
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

    if (loginRes.status !== 200) {
      console.error(`VU ${__VU} - Login failed: ${loginRes.status} - ${loginRes.body}`);
      return;
    }

    token = loginRes.json().token;
  }

  const product_id = getRandomInt(1, 60);
  const payload = JSON.stringify({
    product_id: product_id,
    product_variation_id: product_id,
    quantity: getRandomInt(1, 3),
  });

  const res = http.post(
    `${BASE_URL}/api/carts`,
    payload,
    {
      headers: {
        ...DEFAULT_HEADERS,
        Authorization: `Bearer ${token}`,
      },
    }
  );

  if (res.status !== 200) {
    console.error(`VU ${__VU} - Failed to add to cart: ${res.status} ${token} - ${res.body}`);
  }

  check(res, {
    "status is 200": (r) => r.status === 200,
  });

  sleep(1);
}
