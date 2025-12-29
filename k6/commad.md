k6 run --env K6_WEB_DASHBOARD=true --env K6_WEB_DASHBOARD_EXPORT=k6/report/products.html k6/products.js
k6 run --env K6_WEB_DASHBOARD=true --env K6_WEB_DASHBOARD_EXPORT=k6/report/search.html k6/search.js
k6 run --env TEST_KEY=a55141b7b5ec6886d716840856ab9a1c --env K6_WEB_DASHBOARD=true --env K6_WEB_DASHBOARD_EXPORT=k6/report/add-to-cart.html k6/add-to-cart.js
k6 run --env TEST_KEY=a55141b7b5ec6886d716840856ab9a1c --env K6_WEB_DASHBOARD=true --env K6_WEB_DASHBOARD_EXPORT=k6/report/checkout.html k6/checkout.js
