<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PhpParser\Node\Stmt\Switch_;

class OTPController extends Controller
{

    public function generateOTP(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string',
        ]);
        $user = User::where('phone', $validated['phone_number'])->first();

        if ($user) {
            if ($user->is_contact_verified) {
                return response()->json(['status' => 'failure', 'message' => 'User is already verified'], 400);
            }
    
            $otp = random_int(1000, 9999);
            $cacheKey = "otp_{$validated['phone_number']}";
            Cache::put($cacheKey, $otp, 180);

            $data = [
                'message'=>'Your OTP is '.'' .$otp,
            ];

            return response()->json(['status' => 'OTP sent successfully',"OTP_MESSAGE"=>$data,"Vaild"=>"This OTP is Vaild For 1 Minute"]);
        }else{
            return response()->json(['status' => 'failure','message'=>"INVAILD PHONE NUMBER"], 400);
        }
       
    }

    public function verifyOTP(Request $request)
    {
        try {
            
            $validated = $request->validate([
                'phone_number' => 'required|string',
                'otp' => 'required|integer',
            ]);
    
            $cachedOTP = Cache::get("otp_{$validated['phone_number']}");
            if ($cachedOTP && $cachedOTP == $validated['otp']) {
                Cache::forget("otp_{$validated['phone_number']}");
                $user = User::where('phone', $validated['phone_number'])->first();

                if ($user) {
                    $user->is_contact_verified = true;
                    $user->save(); // Save the change
                }
    
                return response()->json(['status' => 'success', 'otp' => $validated['otp']]);
            }
    
            return response()->json(['status' => 'failure','message'=>"INVAILD OTP"], 400);

        } catch (Exception $e) {
            // Handle exceptions and return an appropriate response
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while verifying the OTP.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
}
