<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends SCBaseController
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        $data = $this->callHook('beforeLoginAttempt');

        $loginData = $data ?? $request->only('email', 'password');
        if (Auth::attempt($loginData)) {

            $user = $this->singleModelResource(Auth::user(), User::class);
            $response = [
                'user' => $user,
                'token' => Auth::user()->createToken($request->device_name)->plainTextToken
            ];
            $this->callHook('afterLogin', $request);
            return response()->json($response, 200);
            
        }

        return response()->json([
            'error' => 'Invalid Credentials'
        ], 401);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                PasswordRule::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'device_name' => 'required',
        ]);

        try {
            $data = $this->callHook('beforeRegister', $request);

            $registerData = $data ?? [
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ];
            $user = User::create($registerData);

            $data = $this->callHook('afterRegister', $request, $user);
            $user = $this->singleModelResource($user, User::class);
            $responseData = $data ?? [
                'user' => $user,
                'token' => $user->createToken($request->device_name)->plainTextToken
            ];

            return response()->json($responseData, 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    public function me(Request $request)
    {
        try {
            return response()->json($request->user(), 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $this->callHook('beforeForgotPasswordEmailSend', $request);
        $status = Password::sendResetLink(
            $request->only('email')
        );
        $this->callHook('afterForgotPasswordEmailSend', $request);
        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)], 200)
            : response()->json(['message' => __($status)], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ]);

        $this->callHook('beforePasswordReset', $request);
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );
        $this->callHook('afterPasswordReset', $request);
        return $status == Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)], 200)
            : response()->json(['message' => __($status)], 400);
    }

    public function confirmPassword(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ]);
        $this->callHook('beforePasswordConfirmation', $request);
        if (!Hash::check($request->password, $request->user()->password)) {
            return response()->json([
                'message' => 'The provided password does not match our records.'
            ], 400);
        }
        $this->callHook('afterPasswordConfirmation', $request);
        return response()->json([
            'message' => 'Password confirmed'
        ], 200);
    }



    public function confirmEmail(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        try {
            $this->callHook('beforeProfileUpdate', $request);
            $user = $request->user();
            $user->name = $request->name;
            $user->save();
            $this->callHook('afterProfileUpdate', $request, $user);
            return response()->json($user, 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => [
                'required',
                PasswordRule::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ]);

        try {
            $this->callHook('beforePasswordUpdate', $request);
            if (!Hash::check($request->current_password, $request->user()->password)) {
                return response()->json([
                    'message' => 'The provided password does not match our records.'
                ], 400);
            }

            $user = $request->user();
            $user->password = bcrypt($request->password);
            $user->save();
            $this->callHook('afterPasswordUpdate', $request);
            return response()->json($user, 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    public function logout(Request $request)
    {
        $this->callHook('beforeLogout', $request);
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                'message' => 'Logged out'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }
}
