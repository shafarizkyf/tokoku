import http from "k6/http";
import { check, sleep } from "k6";
import { BASE_URL, DEFAULT_HEADERS } from "./config.js";

export const options = {
  // scenarios: {
  //   cart_load: {
  //     executor: "constant-vus",
  //     vus: 100,
  //     duration: "2m",
  //   },
  // },
  // stages: [
  //   { duration: "10s", target: 0 },
  //   { duration: "10s", target: 300 },
  //   { duration: "30s", target: 300 },
  //   { duration: "10s", target: 0 },
  // ],
  thresholds: {
    http_req_duration: ["p(95)<800"],
    http_req_failed: ["rate<0.01"],
  },
};

const users = JSON.parse(open("./data/users.json"));

export default function () {
  // Each VU gets a stable user
  const user = users[__VU % users.length];

  // Login once per VU
  if (!__ENV[`TOKEN_${__VU}`]) {
    const loginRes = http.post(
      `${BASE_URL}/api/test/login/${user.id}`,
      undefined,
      { headers: { ...DEFAULT_HEADERS, "X-Test-Key": __ENV.TEST_KEY } }
    );

    const token = loginRes.json().token;

    __ENV[`TOKEN_${__VU}`] = token;
  }

  const token = __ENV[`TOKEN_${__VU}`];

  const product_id = Math.floor(Math.random() * 30) + 1;
  const payload = JSON.stringify({
    product_id: product_id,
    product_variation_id: product_id,
    quantity: Math.floor(Math.random() * 3) + 1,
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

  check(res, {
    "status is 200": (r) => r.status === 200,
  });

  sleep(1);
}
