<?php

namespace App\Http\Controllers\API;

use Illuminate\Routing\Controller; 
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        return response()->json(Auth::user()->products, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'stock_quantity' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->only(['name', 'description', 'price', 'stock_quantity']);
        $data['user_id'] = Auth::id();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = Storage::url($path);
        }

        $product = Product::create($data);
        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        if ($product->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return response()->json($product, 200);
    }

    public function update(Request $request, Product $product)
{
    if ($product->user_id !== Auth::id()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'sometimes|required|numeric|min:0',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'stock_quantity' => 'sometimes|required|integer|min:0',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    $data = array_merge($product->only(['name', 'description', 'price', 'stock_quantity', 'image_url']), $request->only(['name', 'description', 'price', 'stock_quantity']));

    if ($request->hasFile('image')) {
        if ($product->image_url) {
            Storage::disk('public')->delete(str_replace(Storage::url(''), '', $product->image_url));
        }
        $path = $request->file('image')->store('products', 'public');
        $data['image_url'] = Storage::url($path);
    } else {
        $data['image_url'] = $product->image_url;
    }

    $product->update($data);
    return response()->json($product, 200);
}

    public function destroy(Product $product)
    {
        if ($product->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($product->image_url) {
            Storage::disk('public')->delete(str_replace(Storage::url(''), '', $product->image_url));
        }

        $product->delete();
        return response()->json(null, 204);
    }
}