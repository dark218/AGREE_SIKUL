<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PhoneLoginRequest;
use App\Http\Resources\UserResource;
use App\Http\Traits\ApiResponseTrait;
use App\Models\OtpSession;
use App\Models\User;
use App\Models\UserLoginLog;
use App\Services\Generator;
use App\Services\SMSApiProService;
use App\Services\UserLoginService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Business\Resources\MarchandResource;
use Modules\Parametrage\Entities\Fichier;
use Modules\Parametrage\Entities\Pays;
use Modules\ServiceClient\Resources\ClientResource;
use Modules\Wallet\Entities\Wallet;
use Modules\Wallet\Services\WalletService;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;
class AuthController extends Controller
{
    use ApiResponseTrait;


}
