<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);



        if (Auth::attempt($request->only('email', 'password'))) {

            $response = [
                'user' => Auth::user(),
                'token' => Auth::user()->createToken($request->device_name)->plainTextToken
            ];
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
            'password' => 'required|alpha_num|min:6',
            'device_name' => 'required',
        ]);
        // dd($request->all());
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $response = [
            'user' => $user,
            'token' => $user->createToken($request->device_name)->plainTextToken
        ];

        return response()->json($response, 200);
    }

    public function me(Request $request)
    {
        return response()->json($request->user(), 200);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // $status = Password::sendResetLink(
        //     $request->only('email')
        // );

        // return $status === Password::RESET_LINK_SENT
        //             ? response()->json(['message' => __($status)], 200)
        //             : response()->json(['message' => __($status)], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ]);

        // $status = Password::reset(
        //     $request->only('email', 'password', 'password_confirmation', 'token'),
        //     function ($user, $password) {
        //         $user->forceFill([
        //             'password' => Hash::make($password)
        //         ])->save();
        //     }
        // );

        // return $status == Password::PASSWORD_RESET
        //             ? response()->json(['message' => __($status)], 200)
        //             : response()->json(['message' => __($status)], 400);
    }

    public function confirmEmail(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
        ]);

        // $status = Password::sendResetLink(
        //     $request->only('email')
        // );

        // return $status === Password::RESET_LINK_SENT
        //             ? response()->json(['message' => __($status)], 200)
        //             : response()->json(['message' => __($status)], 400);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $request->user()->id,
        ]);

        $user = $request->user();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        return response()->json($user, 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logged out'
        ], 200);
    }
}