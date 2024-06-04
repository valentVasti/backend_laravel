<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request, $page)
    {
        try {
            $validationData = Validator::make(
                [
                    'email' => $request->email,
                    'password' => $request->password
                ],
                [
                    'email' => 'required|email',
                    'password' => 'required|string'
                ],
                [
                    'email.required' => 'Email wajib diisi!',
                    'email.email' => 'Email tidak valid!',
                    'password.required' => 'Password wajib diisi!'
                ]
            );

            if ($validationData->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validationData->errors()
                ], 400);
            }

            $user = User::where('email', $request->email)->first();

            if ($user != null) {
                if ((($page == 'admin') && (($user->role != 'KARYAWAN') && ($user->role != 'ADMIN'))) || (($page == 'user') && ($user->role != 'KONSUMEN'))){
                    return response()->json([
                        'success' => false,
                        'message' => [
                            'role' => ['Role tidak sesuai!']
                        ],
                        'data' => $user
                    ], 400);
                }
                
                $user_password = $user->password;

                if (Hash::check($request->password, $user_password)) {
                    $token = $user->createToken('auth-token')->plainTextToken;

                    return response()->json([
                        'success' => true,
                        'message' => 'Berhasil login!',
                        'token_type' => 'Bearer',
                        'token' => $token,
                        'data' => $user
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => [
                            'password' => ['Password salah!']
                        ]
                    ], 400);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => [
                        'email' => ['Pengguna tidak ditemukan!']
                    ]
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function logout()
    {
        $user = Auth::user();

        $user->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logout!'
        ], 200);
    }

    public function notAuthenticated()
    {
        return response()->json([
            'success' => false,
            'message' => 'User not authenticated'
        ], 403);
    }
}
