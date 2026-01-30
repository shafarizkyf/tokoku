<?php

namespace App\Http\Controllers\API;

use App\Helpers\Tripay;
use App\Http\Controllers\Controller;

class PaymentController extends Controller
{
    public function channels()
    {
        $response = Tripay::paymentChannels();

        return $response['data'];
    }
}
