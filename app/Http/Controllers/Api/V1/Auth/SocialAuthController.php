<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\CentralLogics\CustomerLogic;
use Carbon\CarbonInterval;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\SMS_module;
use App\Models\BusinessSetting;
use Illuminate\Support\Carbon;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Modules\Gateways\Traits\SmsGateway;

class SocialAuthController extends Controller
{
    public function social_register(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Social Register Request received:', [
            'medium' => $request->medium,
            'email' => $request->email,
            'phone' => $request->phone,
            'unique_id' => $request->unique_id,
            'token_snippet' => substr($request->token, 0, 15) . '...'
        ]);

        $validator = Validator::make($request->all(), [
            'token'     => 'required',
            'unique_id' => 'required',
            'email'     => 'required_if:medium,google,facebook|unique:users,email',
            'phone'     => 'required|unique:users,phone',
            'medium'    => 'required|in:google,facebook,apple',
        ]);

        if ($validator->fails()) {
            \Illuminate\Support\Facades\Log::warning('Social Register validation failed:', [
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $client    = new Client();
        $token     = $request['token'];
        $email     = $request['email'];
        $unique_id = $request['unique_id'];

        try {
            if ($request['medium'] == 'google') {
                $res  = $client->request('GET', 'https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=' . $token);
                $data = json_decode($res->getBody()->getContents(), true);
                \Illuminate\Support\Facades\Log::info('Google register token verification response:', ['data' => $data]);
            } elseif ($request['medium'] == 'facebook') {
                $res  = $client->request('GET', 'https://graph.facebook.com/' . $unique_id . '?access_token=' . $token . '&&fields=name,email,id');
                $data = json_decode($res->getBody()->getContents(), true);
                \Illuminate\Support\Facades\Log::info('Facebook register token verification response:', ['data' => $data]);
            } elseif ($request['medium'] == 'apple') {
                $user = User::where('temp_token', $unique_id)->first();
                $data = ['email' => $user->email];
                \Illuminate\Support\Facades\Log::info('Apple register verification:', ['email' => $user->email ?? null]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Social Register exception occurred:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'wrong credential.', 'message' => $e->getMessage()], 403);
        }

        // ========== APPLE ==========
        if ($request['medium'] == 'apple') {
            $fast_name = implode('@', explode('@', $data['email'], -1));
            $last_name = '';

            $user = User::where('email', $data['email'])->where('phone', $request->phone)->where('temp_token', $unique_id)->first();
            if (!isset($user)) {
                $check_duplicate_ref = WalletTransaction::where('reference', $request->phone)->first();
                if ($check_duplicate_ref) {
                    return response()->json(['errors' => ['code' => 'ref_code', 'message' => 'Referral code already used']]);
                }
                $user           = User::where('email', $data['email'])->where('temp_token', $unique_id)->first();
                $user->phone    = $request->phone;
                $user->password = bcrypt($request->phone);
                $user->ref_code = Helpers::generate_referer_code();
                $user->save();

                if ($request->ref_code) {
                    $referar_user = User::where('ref_code', '=', $request->ref_code)->first();
                    $ref_status   = BusinessSetting::where('key', 'ref_earning_status')->first()->value;
                    if ($ref_status != '1') {
                        return response()->json(['errors' => [['code' => 'ref_code', 'message' => translate('messages.referer_disable')]]], 405);
                    }
                    if (!$referar_user) {
                        return response()->json(['errors' => [['code' => 'ref_code', 'message' => translate('messages.referer_code_not_found')]]], 405);
                    }
                    $user->ref_by = $referar_user->id;
                    $user->save();
                }
            } else {
                return response()->json(['errors' => [['code' => 'auth-004', 'message' => translate('messages.email_already_exists')]]], 403);
            }

            $loginData            = ['phone' => $user->phone, 'password' => $user->phone];
            $customer_verification = BusinessSetting::where('key', 'customer_verification')->first()->value;
            if (auth()->attempt($loginData)) {
                $token = auth()->user()->createToken('RestaurantCustomerAuth')->accessToken;
                if (!auth()->user()->status) {
                    return response()->json(['errors' => [['code' => 'auth-003', 'message' => translate('messages.your_account_is_blocked')]]], 403);
                }
                if ($customer_verification && !auth()->user()->is_phone_verified && env('APP_MODE') != 'demo') {
                    $otp_result = $this->sendOtp($request['phone']);
                    if ($otp_result !== true) return $otp_result;
                }
                return response()->json(['token' => $token, 'is_phone_verified' => auth()->user()->is_phone_verified], 200);
            }
            return response()->json(['errors' => [['code' => 'auth-001', 'message' => 'Unauthorized.']]], 401);
        }

        // ========== FACEBOOK ==========
        if ($request['medium'] == 'facebook') {
            // Facebook சில நேரம் email return பண்றதில்லை — அப்போ request email use பண்ணு
            $fb_email = $data['email'] ?? $email;
            $fb_id    = $data['id']    ?? $unique_id;

            // Facebook email வந்தா மட்டும் compare பண்ணு
            if (isset($data['email']) && strcmp($email, $data['email']) !== 0) {
                return response()->json(['error' => translate('messages.email_does_not_match')]);
            }

            $name      = explode(' ', $data['name'] ?? $email);
            $fast_name = count($name) > 1 ? implode(" ", array_slice($name, 0, -1)) : implode(" ", $name);
            $last_name = count($name) > 1 ? end($name) : '';

            $user = User::where('email', $fb_email)->first();
            if (!isset($user)) {
                $check_duplicate_ref = WalletTransaction::where('reference', $request->phone)->first();
                if ($check_duplicate_ref) {
                    return response()->json(['errors' => ['code' => 'ref_code', 'message' => 'Referral code already used']]);
                }
                $user = User::create([
                    'f_name'       => $fast_name,
                    'l_name'       => $last_name,
                    'email'        => $fb_email,
                    'phone'        => $request->phone,
                    'password'     => bcrypt($fb_id),
                    'login_medium' => $request['medium'],
                    'social_id'    => $fb_id,
                ]);
                $user->ref_code = Helpers::generate_referer_code();
                $user->save();

                if ($request->ref_code) {
                    $referar_user = User::where('ref_code', '=', $request->ref_code)->first();
                    $ref_status   = BusinessSetting::where('key', 'ref_earning_status')->first()->value;
                    if ($ref_status != '1') {
                        return response()->json(['errors' => [['code' => 'ref_code', 'message' => translate('messages.referer_disable')]]], 405);
                    }
                    if (!$referar_user) {
                        return response()->json(['errors' => [['code' => 'ref_code', 'message' => translate('messages.referer_code_not_found')]]], 405);
                    }
                    $user->ref_by = $referar_user->id;
                    $user->save();
                }
            } else {
                return response()->json(['errors' => [['code' => 'auth-004', 'message' => translate('messages.email_already_exists')]]], 403);
            }
        }

        // ========== GOOGLE ==========
        if ($request['medium'] == 'google') {
            if (strcmp($email, $data['email'] ?? '') !== 0 || (!isset($data['id']) && !isset($data['kid']))) {
                return response()->json(['error' => translate('messages.email_does_not_match')]);
            }
            $name      = explode(' ', $data['name'] ?? '');
            $fast_name = count($name) > 1 ? implode(" ", array_slice($name, 0, -1)) : implode(" ", $name);
            $last_name = count($name) > 1 ? end($name) : '';
            $pk        = $data['id'] ?? $data['kid'];

            $user = User::where('email', $email)->first();
            if (!isset($user)) {
                $check_duplicate_ref = WalletTransaction::where('reference', $request->phone)->first();
                if ($check_duplicate_ref) {
                    return response()->json(['errors' => ['code' => 'ref_code', 'message' => 'Referral code already used']]);
                }
                $user = User::create([
                    'f_name'       => $fast_name,
                    'l_name'       => $last_name,
                    'email'        => $email,
                    'phone'        => $request->phone,
                    'password'     => bcrypt($pk),
                    'login_medium' => $request['medium'],
                    'social_id'    => $pk,
                ]);
                $user->ref_code = Helpers::generate_referer_code();
                $user->save();

                if ($request->ref_code) {
                    $referar_user = User::where('ref_code', '=', $request->ref_code)->first();
                    $ref_status   = BusinessSetting::where('key', 'ref_earning_status')->first()->value;
                    if ($ref_status != '1') {
                        return response()->json(['errors' => [['code' => 'ref_code', 'message' => translate('messages.referer_disable')]]], 405);
                    }
                    if (!$referar_user) {
                        return response()->json(['errors' => [['code' => 'ref_code', 'message' => translate('messages.referer_code_not_found')]]], 405);
                    }
                    $user->ref_by = $referar_user->id;
                    $user->save();
                }
            } else {
                return response()->json(['errors' => [['code' => 'auth-004', 'message' => translate('messages.email_already_exists')]]], 403);
            }
        }

        // ========== Login after register (Google + Facebook) ==========
        $customer_verification = BusinessSetting::where('key', 'customer_verification')->first()->value;
        if (auth()->loginUsingId($user->id)) {
            $token = auth()->user()->createToken('RestaurantCustomerAuth')->accessToken;
            if (!auth()->user()->status) {
                return response()->json(['errors' => [['code' => 'auth-003', 'message' => translate('messages.your_account_is_blocked')]]], 403);
            }
            if ($customer_verification && !auth()->user()->is_phone_verified && env('APP_MODE') != 'demo') {
                $otp_result = $this->sendOtp($request['phone']);
                if ($otp_result !== true) return $otp_result;
            }
            return response()->json(['token' => $token, 'is_phone_verified' => auth()->user()->is_phone_verified], 200);
        }
        return response()->json(['errors' => [['code' => 'auth-001', 'message' => 'Unauthorized.']]], 401);
    }


    public function social_login(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Social Login Request received:', [
            'medium' => $request->medium,
            'email' => $request->email,
            'unique_id' => $request->unique_id,
            'token_snippet' => substr($request->token, 0, 15) . '...'
        ]);

        $validator = Validator::make($request->all(), [
            'token'     => 'required',
            'unique_id' => 'required',
            'email'     => 'required_if:medium,google,facebook',
            'medium'    => 'required|in:google,facebook,apple',
        ]);

        if ($validator->fails()) {
            \Illuminate\Support\Facades\Log::warning('Social Login validation failed:', [
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $client    = new Client();
        $token     = $request['token'];
        $email     = $request['email'];
        $unique_id = $request['unique_id'];

        try {
            if ($request['medium'] == 'google') {
                $res  = $client->request('GET', 'https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=' . $token);
                $data = json_decode($res->getBody()->getContents(), true);
                \Illuminate\Support\Facades\Log::info('Google login token verification response:', ['data' => $data]);
            } elseif ($request['medium'] == 'facebook') {
                $res  = $client->request('GET', 'https://graph.facebook.com/' . $unique_id . '?access_token=' . $token . '&&fields=name,email,id');
                $data = json_decode($res->getBody()->getContents(), true);
                \Illuminate\Support\Facades\Log::info('Facebook login token verification response:', ['data' => $data]);
            } elseif ($request['medium'] == 'apple') {
                $apple_login = \App\Models\BusinessSetting::where(['key' => 'apple_login'])->first();
                if ($apple_login) {
                    $apple_login = json_decode($apple_login->value)[0];
                }
                $teamId      = $apple_login->team_id;
                $keyId       = $apple_login->key_id;
                $sub         = $apple_login->client_id;
                $aud         = 'https://appleid.apple.com';
                $iat         = strtotime('now');
                $exp         = strtotime('+60days');
                $keyContent  = file_get_contents('storage/apple-login/' . $apple_login->service_file);

                $token = JWT::encode([
                    'iss' => $teamId,
                    'iat' => $iat,
                    'exp' => $exp,
                    'aud' => $aud,
                    'sub' => $sub,
                ], $keyContent, 'ES256', $keyId);

                $redirect_uri = $apple_login->redirect_url ?? 'www.example.com/apple-callback';
                $res = Http::asForm()->post('https://appleid.apple.com/auth/token', [
                    'grant_type'    => 'authorization_code',
                    'code'          => $unique_id,
                    'redirect_uri'  => $redirect_uri,
                    'client_id'     => $sub,
                    'client_secret' => $token,
                ]);

                $claims = explode('.', $res['id_token'])[1];
                $data   = json_decode(base64_decode($claims), true);
                \Illuminate\Support\Facades\Log::info('Apple login token verification claims:', ['data' => $data]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Social Login exception occurred:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'wrong credential.', 'message' => $e->getMessage()], 403);
        }

        // ✅ FIXED: Facebook email இல்லாம வரும் போது handle
        if (!isset($claims)) {
            if ($request['medium'] == 'facebook') {
                if (!isset($data['email'])) {
                    // Facebook email return பண்ணல — social_id அல்லது request email மூலம் find பண்ணு
                    $user = User::where('social_id', $unique_id)
                        ->orWhere('email', $email)
                        ->first();
                } else {
                    if (strcmp($email, $data['email']) != 0) {
                        return response()->json(['error' => translate('messages.email_does_not_match')], 403);
                    }
                    $user = User::where('email', $data['email'])->first();
                }
            } else {
                // Google
                if (strcmp($email, $data['email'] ?? '') != 0 || (!isset($data['id']) && !isset($data['kid']))) {
                    return response()->json(['error' => translate('messages.email_does_not_match')], 403);
                }
                $user = User::where('email', $data['email'])->first();
            }
        } else {
            // Apple
            $user = User::where('email', $data['email'])->first();
        }

        if ($request['medium'] == 'apple') {
            try {
                if (!isset($user)) {
                    $user = new User();
                }
                $user->f_name       = implode('@', explode('@', $data['email'], -1));
                $user->l_name       = '';
                $user->email        = $data['email'];
                $user->login_medium = $request['medium'];
                $user->temp_token   = $unique_id;
                $user->save();
            } catch (\Throwable $e) {
                return response()->json(['error' => 'wrong credential.', 'message' => $e->getMessage()], 403);
            }
        }

        if (!isset($user)) {
            return response()->json(['token' => null, 'is_phone_verified' => 0], 200);
        }

        if ($request['medium'] == 'apple' && $user->phone == null) {
            return response()->json(['token' => null, 'is_phone_verified' => 0], 200);
        }

        $customer_verification = BusinessSetting::where('key', 'customer_verification')->first()->value;
        if (auth()->loginUsingId($user->id)) {
            $token = auth()->user()->createToken('RestaurantCustomerAuth')->accessToken;
            if (!auth()->user()->status) {
                return response()->json(['errors' => [['code' => 'auth-003', 'message' => translate('messages.your_account_is_blocked')]]], 403);
            }
            if ($customer_verification && !auth()->user()->is_phone_verified && env('APP_MODE') != 'demo') {
                $otp_interval_time        = 60;
                $phone_verification_data  = DB::table('phone_verifications')->where('phone', $user->phone)->first();
                if (isset($phone_verification_data) && Carbon::parse($phone_verification_data->updated_at)->DiffInSeconds() < $otp_interval_time) {
                    $time = $otp_interval_time - Carbon::parse($phone_verification_data->updated_at)->DiffInSeconds();
                    return response()->json(['errors' => [['code' => 'otp', 'message' => translate('messages.please_try_again_after_') . $time . ' ' . translate('messages.seconds')]]], 405);
                }
                $otp = rand(1000, 9999);
                DB::table('phone_verifications')->updateOrInsert(['phone' => $user->phone], [
                    'token'         => $otp,
                    'otp_hit_count' => 0,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
                $published_status        = 0;
                $payment_published_status = config('get_payment_publish_status');
                if (isset($payment_published_status[0]['is_published'])) {
                    $published_status = $payment_published_status[0]['is_published'];
                }
                $response = ($published_status == 1) ? SmsGateway::send($user->phone, $otp) : SMS_module::send($user->phone, $otp);
                if ($response != 'success') {
                    return response()->json(['errors' => [['code' => 'otp', 'message' => translate('messages.faield_to_send_sms')]]], 403);
                }
            }
            if ($user->ref_code == null && isset($user->id)) {
                $ref_code = Helpers::generate_referer_code($user);
                DB::table('users')->where('phone', $user->phone)->update(['ref_code' => $ref_code]);
            }
            return response()->json(['token' => $token, 'phone' => $user->phone, 'is_phone_verified' => $user->is_phone_verified, 'res' => $res ?? null], 200);
        }

        return response()->json(['errors' => [['code' => 'auth-001', 'message' => 'Unauthorized.']]], 401);
    }


    // ========== Helper: OTP Send ==========
    private function sendOtp($phone)
    {
        $otp_interval_time       = 60;
        $phone_verification_data = DB::table('phone_verifications')->where('phone', $phone)->first();

        if (isset($phone_verification_data) && Carbon::parse($phone_verification_data->updated_at)->DiffInSeconds() < $otp_interval_time) {
            $time = $otp_interval_time - Carbon::parse($phone_verification_data->updated_at)->DiffInSeconds();
            return response()->json(['errors' => [['code' => 'otp', 'message' => translate('messages.please_try_again_after_') . $time . ' ' . translate('messages.seconds')]]], 405);
        }

        $otp = rand(1000, 9999);
        DB::table('phone_verifications')->updateOrInsert(
            ['phone' => $phone],
            ['token' => $otp, 'created_at' => now(), 'updated_at' => now()]
        );

        $published_status        = 0;
        $payment_published_status = config('get_payment_publish_status');
        if (isset($payment_published_status[0]['is_published'])) {
            $published_status = $payment_published_status[0]['is_published'];
        }

        $response = ($published_status == 1) ? SmsGateway::send($phone, $otp) : SMS_module::send($phone, $otp);
        if ($response != 'success') {
            return response()->json(['errors' => [['code' => 'otp', 'message' => translate('messages.faield_to_send_sms')]]], 403);
        }

        return true;
    }
}