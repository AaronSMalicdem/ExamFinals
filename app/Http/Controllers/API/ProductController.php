<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin')->only(['store', 'update', 'destroy']);
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
            Log::error('Validation failed for product store', ['errors' => $validator->errors()]);
            return response()->json($validator->errors(), 422);
        }

        $data = $request->only(['name', 'description', 'price', 'stock_quantity']);
        $data['user_id'] = Auth::id();

        if ($request->hasFile('image')) {
            try {
                $path = $request->file('image')->store('products', 'public');
                $data['image_url'] = Storage::url($path);
            } catch (\Exception $e) {
                Log::error('Image upload failed in store', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Image upload failed'], 500);
            }
        }
        try {
            $product = Product::create($data);
            return response()->json($product, 201);
        } catch (\Exception $e) {
            Log::error('Product creation failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create product'], 500);
        }
    }

    public function show(Product $product)
    {
        return response()->json($product, 200);
    }

    public function update(Request $request, Product $product)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'stock_quantity' => 'sometimes|required|integer|min:0',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for product update', ['errors' => $validator->errors()]);
            return response()->json($validator->errors(), 422);
        }

        $data = $request->only(['name', 'description', 'price', 'stock_quantity']);

        if (empty($data) && !$request->hasFile('image')) {
            return response()->json(['error' => 'No fields provided for update'], 422);
        }

        if ($request->hasFile('image')) {
            try {
                if ($product->image_url) {
                    $oldPath = str_replace(Storage::url(''), '', $product->image_url);
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }
                $path = $request->file('image')->store('products', 'public');
                $data['image_url'] = Storage::url($path);
            } catch (\Exception $e) {
                Log::error('Image upload failed in update', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Image upload failed'], 500);
            }
        }

        try {
            $updated = $product->update($data);
            if (!$updated) {
                Log::warning('No changes applied during product update', ['product_id' => $product->id]);
                return response()->json(['message' => 'No changes applied'], 200);
            }
            return response()->json($product->fresh(), 200);
        } catch (\Exception $e) {
            Log::error('Product update failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update product'], 500);
        }
    }

    public function destroy(Product $product)
    {
        if ($product->image_url) {
            $path = str_replace(Storage::url(''), '', $product->image_url);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

       try {
            $product->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Product deletion failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete product'], 500);
        }
    }
}
