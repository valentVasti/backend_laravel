<?php

namespace App\Http\Controllers;

use App\Models\Mesin;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class MesinController extends Controller
{
    public function index()
    {
        $mesin = Mesin::orderBy('jenis_mesin')->get();

        return response()->json([
            'success' => true,
            'message' => 'All mesin successfully retrieved!',
            'data' => $mesin
        ], 200);
    }

    public function show($id)
    {
        $mesin = Mesin::find($id);

        if ($mesin == null) {
            return response()->json([
                'success' => false,
                'message' => 'Mesin not found!',
                'data' => $mesin
            ], 404);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Mesin retrieved successfully!',
                'data' => $mesin
            ], 200);
        }
    }

    public function create(Request $request)
    {
        $validation = Validator::make(
            $request->all(),
            [
                'kode_mesin' => 'required|unique:mesin,kode_mesin',
                'jenis_mesin' => 'required|in:PENGERING,PENCUCI',
                'identifier' => 'required|unique:mesin,identifier',
                'durasi_penggunaan' => 'required|numeric',
            ],
            [
                'kode_mesin.required' => 'Kode mesin wajib diisi!',
                'kode_mesin.unique' => 'Kode mesin sudah terdaftar!',
                'jenis_mesin.required' => 'Jenis mesin wajib diisi!',
                'jenis_mesin.in' => 'Jenis mesin harus berupa PENGERING atau PENCUCI!',
                'identifier.required' => 'Identifier mesin wajib diisi!',
                'identifier.unique' => 'Identifier mesin sudah terdaftar!',
                'durasi_penggunaan.required' => 'Durasi penggunaan mesin wajib diisi!',
                'durasi_penggunaan.numeric' => 'Durasi penggunaan mesin harus berupa angka!'
            ]
        );

        if ($validation->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validation->errors()
            ], 400);
        }

        $mesin = Mesin::create([
            'kode_mesin' => $request->kode_mesin,
            'jenis_mesin' => $request->jenis_mesin,
            'identifier' => $request->identifier,
            'durasi_penggunaan' => $request->durasi_penggunaan,
            'status_maintenance' => $request->status_maintenance
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mesin successfully created!',
            'data' => $mesin
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make(
            $request->all(),
            [
                'kode_mesin' => 'required',
                'jenis_mesin' => 'required|in:PENGERING,PENCUCI',
                'identifier' => 'required',
                'durasi_penggunaan' => 'required|numeric',
            ],
            [
                'kode_mesin.required' => 'Kode mesin wajib diisi!',
                'jenis_mesin.required' => 'Jenis mesin wajib diisi!',
                'identifier' => 'Identifier mesin wajib diisi!',
                'durasi_penggunaan.required' => 'Durasi penggunaan mesin wajib diisi!',
                'durasi_penggunaan.numeric' => 'Durasi penggunaan mesin harus berupa angka!',
            ]
        );

        if ($validation->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validation->errors()
            ], 400);
        }

        $mesin = Mesin::find($id);

        if ($mesin == null) {
            return response()->json([
                'success' => false,
                'message' => 'Mesin not found!',
                'data' => $mesin
            ], 404);
        } else {
            $mesin->update([
                'kode_mesin' => $request->kode_mesin,
                'jenis_mesin' => $request->jenis_mesin,
                'identifier' => $request->identifier,
                'durasi_penggunaan' => $request->durasi_penggunaan,
                'status_maintenance' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mesin updated successfully!',
                'data' => $mesin
            ], 200);
        }
    }

    public function destroy($id)
    {
        $mesin = Mesin::find($id);

        if ($mesin == null) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found!',
                'data' => $mesin
            ], 404);
        } else {
            $mesin->delete();

            return response()->json([
                'success' => true,
                'message' => 'Mesin deleted successfully!',
            ], 200);
        }
    }

    public function setStatus($id, $status)
    {
        $mesin = Mesin::find($id);

        if ($mesin == null) {
            return response()->json([
                'success' => false,
                'message' => 'Mesin not found!',
                'data' => $mesin
            ], 404);
        } else {
            $mesin->update([
                'status_maintenance' => $status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mesin status updated successfully!',
                'data' => $mesin
            ], 200);
        }
    }

    public function getActiveMachineByJenis($jenis)
    {
        $mesin = Mesin::where('jenis_mesin', $jenis)->where('status_maintenance', 1)->get();

        return response()->json([
            'success' => true,
            'message' => 'Mesin with jenis ' . $jenis . ' successfully retrieved!',
            'data' => $mesin
        ], 200);
    }
}
