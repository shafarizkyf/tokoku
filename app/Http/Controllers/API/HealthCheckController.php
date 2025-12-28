<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\MeilisearchCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Facades\Health;

class HealthCheckController extends Controller {

  public function index(Request $request) {
    Health::checks([
      DatabaseCheck::new(),
      MeilisearchCheck::new(),
      RedisCheck::new()
    ]);

    return response()->json([
      'status' => 'ok',
      'message' => 'API is healthy',
    ], 200);
  }

}
