<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index()
    {
        $product = Product::all();

        return response()->json([
            'success' => true,
            'message' => "All product retrieved successfully!",
            'data' => $product
        ], 200);
    }

    public function show($id)
    {
        $product = Product::find($id);

        if ($product == null) {
            return response()->json([
                'success' => false,
                'message' => "Product not found!",
                'data' => $product
            ], 404);
        } else {
            return response()->json([
                'success' => true,
                'message' => "Product retrieved successfully!",
                'data' => $product
            ], 200);
        }
    }

    public function create(Request $request)
    {
        $validation = Validator::make(
            $request->all(),
            [
                'product_name' => 'required',
                'price' => 'required|numeric',
            ],
            [
                'product_name.required' => 'Nama produk wajib diisi!',
                'price.required' => 'Harga produk wajib diisi!',
                'price.numeric' => 'Harga produk harus berupa angka!'
            ]
        );

        if ($validation->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validation->errors()
            ], 400);
        }

        $product = Product::create([
            'product_name' => $request->product_name,
            'price' => $request->price,
            'status' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product added successfully!',
            'data' => $product
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make(
            $request->all(),
            [
                'product_name' => 'required',
                'price' => 'required|numeric',
            ],
            [
                'product_name.required' => 'Nama produk wajib diisi!',
                'price.required' => 'Harga produk wajib diisi!',
                'price.numeric' => 'Harga produk harus berupa angka!'
            ]
        );

        if ($validation->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validation->errors()
            ], 400);
        }

        $product = Product::find($id);

        if ($product == null) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found!',
                'data' => $product
            ], 404);
        } else {
            $product->update([
                'product_name' => $request->product_name,
                'price' => $request->price
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully!',
                'data' => $product
            ], 200);
        }
    }

    public function destroy($id)
    {
        $product = Product::find($id);

        if ($product == null) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found!',
                'data' => $product
            ], 404);
        } else {
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully!',
            ], 200);
        }
    }
}
