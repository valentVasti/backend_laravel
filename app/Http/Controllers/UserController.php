<?php

namespace App\Http\Controllers;

use App\Models\QueuedQueue;
use App\Models\TempQueue;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function getLoggedUser()
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'message' => 'User logged in data successfully retrieved!',
            'data' => $user
        ], 200);
    }

    public function getUserByRole($role)
    {
        $role = strtoupper($role);
        $user = User::where('role', $role)->get();

        return response()->json([
            'success' => true,
            'message' => 'User data successfully retrieved!',
            'data' => $user
        ], 200);
    }

    public function create(Request $request)
    {
        $validateData = Validator::make(
            [
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'phone_num' => $request->phone_num,
                'role' => $request->role
            ],
            [
                'name' => 'required|string',
                'email' => 'required|string|email|unique:users,email',
                'password' => 'required|string',
                'phone_num' => 'required|numeric|starts_with:0|digits_between:11,14|unique:users,phone_num',
                'role' => 'required',
            ],
            [
                'name.required' => 'Nama wajib diisi!',
                'email.required' => 'Email wajib diisi!',
                'email.email' => 'Format email tidak valid!',
                'email.unique' => 'Email sudah terdaftar!',
                'password.required' => 'Password wajib diisi!',
                'phone_num.required' => 'Nomor Telepon wajib diisi!',
                'phone_num.starts_with' => 'Nomor Telepon wajib dimulai dengan angka 0!',
                'phone_num.digits_between' => 'Nomor Telepon memiliki min. 11 dan maks. 14 digit!',
                'phone_num.unique' => 'Nomor telepon sudah terdaftar!',
                'role.required' => 'Role harus diisi!',
            ]
        );

        if ($validateData->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validateData->errors()
            ], 400);
        }

        $new_user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
            'phone_num' => $request->phone_num
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User successfully registered',
            'data' => $new_user
        ], 200);
    }

    public function show($id)
    {
        $user = User::find($id);

        if ($user == null) {
            return response()->json([
                'success' => false,
                'message' => 'User not found!'
            ], 404);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'User data successfully retrieved!',
                'data' => $user
            ]);
        }
    }

    public function register(Request $request)
    {
        $validateData = Validator::make(
            [
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'phone_num' => $request->phone_num,
                'role' => $request->role
            ],
            [
                'name' => 'required|string',
                'email' => 'required|string|email|unique:users,email',
                'password' => 'required|string',
                'phone_num' => 'required|numeric|starts_with:0|digits_between:11,14|unique:users,phone_num',
                'role' => 'required|string|in:KARYAWAN,KONSUMEN',
            ],
            [
                'name.required' => 'Nama wajib diisi!',
                'email.required' => 'Email wajib diisi!',
                'email.email' => 'Format email tidak valid!',
                'email.unique' => 'Email sudah terdaftar!',
                'password.required' => 'Password wajib diisi!',
                'phone_num.required' => 'Nomor Telepon wajib diisi!',
                'phone_num.starts_with' => 'Nomor Telepon wajib dimulai dengan angka 0!',
                'phone_num.digits_between' => 'Nomor Telepon memiliki min. 11 dan maks. 14 digit!',
                'phone_num.unique' => 'Nomor telepon sudah terdaftar!',
                'role.required' => 'Role harus diisi!',
                'role.in' => 'Role hanya KARYAWAN atau KONSUMEN!',
            ]
        );

        if ($validateData->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validateData->errors()
            ], 400);
        }

        $new_user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
            'phone_num' => $request->phone_num
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User successfully registered',
            'data' => $new_user
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if ($user == null) {
            return response()->json([
                'success' => false,
                'message' => 'User not found!',
            ], 404);
        }

        $validateData = Validator::make(
            [
                'name' => $request->name,
                'email' => $request->email,
                'phone_num' => $request->phone_num,
            ],
            [
                'name' => 'string',
                'email' => 'string|email',
                'phone_num' => 'number|starts_with:0|digits_between:11,14|unique:users,phone_num',
            ],
            [
                'email.email' => 'Format email tidak valid!',
                'phone_num.starts_with' => 'Nomor Telepon wajib dimulai dengan angka 0!',
                'phone_num.digits_between' => 'Nomor Telepon memiliki min. 11 dan maks. 14 digit!',
                'phone_num.unique' => 'Nomor telepon sudah terdaftar!',
            ]
        );

        if ($validateData->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validateData
            ], 400);
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone_num' => $request->phone_num
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User successfully updated',
            'data' => $user
        ], 200);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if ($user == null) {
            return response()->json([
                'success' => false,
                'message' => 'User not found!',
                'data' => 'null'
            ], 404);
        }

        // nge check apakah user punya transaction di queue
        $queue = QueuedQueue::whereHas('transaction', function ($query) use ($id) {
            $query->where('user_id', $id);
        })->get();

        if ($queue->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'User ini memiliki antrian yang sedang berjalan!',
                'data' => $queue
            ], 400);
        } else {
            $queue = TempQueue::whereHas('transaction', function ($query) use ($id) {
                $query->where('user_id', $id);
            })->get();

            if ($queue->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ini memiliki antrian yang sedang berjalan!',
                    'data' => $queue
                ], 400);
            } else {
                $user->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'User with ID ' . $id . ' successfully deleted!',
                    'data' => $user
                ]);
            }
        }
    }
}
