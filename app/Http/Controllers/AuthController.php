<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;

class AuthController extends Controller
{
    //
    public function register(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'tipe' => 'required',
        ]);
        if ($validation->fails()) {
            return response()->json($validation->errors(), 202);
        } else {

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'tipe' => $request->tipe,
            ]);

            //$token = $user->createToken('LaravelAuth')->accessToken;
            $token = Auth::attempt(['email' => $request->email, 'password' => $request->password]);
            return $this->respondWithToken($token);
        }
    }
    public function login(Request $request)
    {
        $data = [
            'email' => $request->email,
            'password' => $request->password,
        ];
        if ($token = Auth::attempt($data)) {
            // $token = Auth::user()->createToken('LaravelAuthApp')->accessToken;
            return $this->respondWithToken($token);
        } else {
            return response()->json(['error' => 'Unauthorised'], 401);
        }
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ], 200);
    }
}
