<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\SMSApiProService;
use App\Services\SMSApiService;
use Illuminate\Http\Request;

class SmsTestController extends Controller
{
    public function testSms(Request $request)
    {
        return  SMSApiProService::sendSmsOTP("2250747780472");
    }
}
