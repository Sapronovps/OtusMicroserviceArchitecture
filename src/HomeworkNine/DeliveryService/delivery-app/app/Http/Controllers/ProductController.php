<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
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
            'price' => 'required|integer|max:255',
            'quantity' => 'required|integer|max:255',
        ]);

        $product = Product::create([
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'quantity' => $request->quantity
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Товар "' . $request->title . '" успешно добавлен на остатки',
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
            'price' => 'required|integer|max:255',
            'quantity' => 'required|integer|max:255',
        ]);

        /** @var Product $product */
        $product = Product::find($id);
        $product->title = $request->title;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->quantity += $request->quantity;
        $product->save();

        return response()->json([
           'status' => 'success',
           'message' => 'Данные о товаре "' . $product->title . '" успешно обновлены',
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
