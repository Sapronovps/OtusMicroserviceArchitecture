<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        $products = Product::all();

        return response()->json([
            'status' => 'success',
            'todos' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
        ]);

        $product = Product::create([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully',
            'product' => $product
        ]);
    }

    public function show($id)
    {
        $product = Product::find($id);

        return response()->json([
            'status' => 'success',
            'product' => $product
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
           'title' => 'required|string|max:255',
           'description' => 'required|string|max:255',
        ]);

        /** @var Product $product */
        $product = Product::find($id);
        $product->title = $request->title;
        $product->description = $request->description;
        $product->save();

        return response()->json([
           'status' => 'success',
           'message' => 'Product updated successfully',
           'product' => $product,
        ]);
    }

    public function desctroy($id)
    {
        /** @var Product $product */
        $product = Product::find($id);
        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully',
            'product' => $product
        ]);
    }
}
