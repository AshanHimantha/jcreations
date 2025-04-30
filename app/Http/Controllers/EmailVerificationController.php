<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;


class EmailVerificationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/send-verification-code",
     *     summary="Send verification code to email",
     *     description="Sends a verification code to the provided email address",
     *     operationId="sendVerificationCode",
     *     tags={"Email Verification"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Email and verification code",
     *         @OA\JsonContent(
     *             required={"email", "code"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verification code sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Verification code sent successfully"),
     *             @OA\Property(property="success", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
     *                     type="array",
     *                     @OA\Items(type="string", example="The email field is required")
     *                 ),
     *                 @OA\Property(
     *                     property="code",
     *                     type="array",
     *                     @OA\Items(type="string", example="The code must be 6 characters")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Too many requests",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Please wait before requesting another code"),
     *             @OA\Property(property="success", type="boolean", example=false)
     *         )
     *     )
     * )
     */
    public function sendVerificationCode(Request $request)
    {
        // Validate the request
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:4',
        ]);

        $email = $request->email;
        $code = $request->code;
        
        // Rate limiting - prevent too many emails to the same address
        $cacheKey = 'email_sent_' . $email;
        if (Cache::has($cacheKey)) {
            return response()->json([
                'message' => 'Please wait before requesting another code',
                'success' => false
            ], 429);
        }
        
        // Store the code in cache with an expiry of 60 minutes
        Cache::put('verification_code_' . $email, $code, now()->addMinutes(5));
        
        // Add rate limiting - prevent multiple emails in quick succession
        Cache::put($cacheKey, true, now()->addMinutes(2));
        
        // Send the email
        Mail::to($email)->send(new VerificationCodeMail($code));
        
        return response()->json([
            'message' => 'Verification code sent successfully',
            'success' => true
        ], 200);
    }
}