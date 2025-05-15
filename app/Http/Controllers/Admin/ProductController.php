<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSpecification;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index()
    {
        $products = Product::with(['category', 'subCategory'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'productId' => 'required|string|unique:products,product_id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'categoryId' => 'required|string|exists:categories,category_id',
            'subCategoryId' => 'required|string|exists:sub_categories,sub_category_id',
            'price' => 'required|numeric|min:0',
            'costPrice' => 'nullable|numeric|min:0',
            'stockQuantity' => 'required|integer|min:0',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'barcode' => 'nullable|string|max:100|unique:products,barcode',
            'weight' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive,out_of_stock,discontinued',
            'featured' => 'boolean',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'specifications' => 'nullable|array',
            'specifications.*.key' => 'required_with:specifications.*.value|string|max:100',
            'specifications.*.value' => 'required_with:specifications.*.key|string|max:255',
            'variants' => 'nullable|array',
            'variants.*.name' => 'required_with:variants.*.options|string|max:100',
            'variants.*.options' => 'required_with:variants.*.name|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Start a database transaction
            \DB::beginTransaction();

            // Create the product
            $product = Product::create([
                'product_id' => $request->productId,
                'name' => $request->name,
                'description' => $request->description,
                'category_id' => $request->categoryId,
                'sub_category_id' => $request->subCategoryId,
                'price' => $request->price,
                'cost_price' => $request->costPrice,
                'stock_quantity' => $request->stockQuantity,
                'sku' => $request->sku,
                'barcode' => $request->barcode,
                'weight' => $request->weight,
                'status' => $request->status,
                'featured' => $request->featured ?? false,
            ]);

            // Handle images upload
            if ($request->hasFile('images')) {
                $displayOrder = 0;
                foreach ($request->file('images') as $imageFile) {
                    $path = $imageFile->store('products', 'public');

                    ProductImage::create([
                        'product_id' => $product->product_id,
                        'image_path' => $path,
                        'display_order' => $displayOrder++,
                    ]);
                }
            }

            // Handle specifications
            if ($request->has('specifications') && is_array($request->specifications)) {
                foreach ($request->specifications as $spec) {
                    if (!empty($spec['key']) && !empty($spec['value'])) {
                        ProductSpecification::create([
                            'product_id' => $product->product_id,
                            'key' => $spec['key'],
                            'value' => $spec['value'],
                        ]);
                    }
                }
            }

            // Handle variants
            if ($request->has('variants') && is_array($request->variants)) {
                foreach ($request->variants as $variant) {
                    if (!empty($variant['name']) && !empty($variant['options'])) {
                        ProductVariant::create([
                            'product_id' => $product->product_id,
                            'name' => $variant['name'],
                            'options' => $variant['options'],
                        ]);
                    }
                }
            }

            // Commit the transaction
            \DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);

        } catch (\Exception $e) {
            // Roll back the transaction in case of an error
            \DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a new product ID
     */
    /**
     * Generate a new product ID
     */
    /**
     * Generate a new product ID
     */
    public function generateProductId()
    {
        // Check and log the product count
        $productCount = Product::count();
        \Log::info('Product count: ' . $productCount);

        // Check if the products table exists and has records
        try {
            $tableExists = \Schema::hasTable('products');
            \Log::info('Products table exists: ' . ($tableExists ? 'Yes' : 'No'));

            // Try to get the first product to confirm query functionality
            $firstProduct = Product::first();
            \Log::info('First product exists: ' . ($firstProduct ? 'Yes - ID: ' . $firstProduct->product_id : 'No'));
        } catch (\Exception $e) {
            \Log::error('Error checking products table: ' . $e->getMessage());
        }

        // Check if any product exists in the database
        $productExists = $productCount > 0;

        if ($productExists) {
            \Log::info('Products exist in database, generating next ID');

            // Get the highest product ID using raw SQL to ensure proper numeric ordering
            try {
                $highestIdProduct = Product::selectRaw("CAST(SUBSTRING(product_id, 4) AS UNSIGNED) as id_num")
                    ->orderByRaw("CAST(SUBSTRING(product_id, 4) AS UNSIGNED) DESC")
                    ->first();

                \Log::info('Highest ID query result: ' . ($highestIdProduct ? json_encode($highestIdProduct->toArray()) : 'null'));

                if ($highestIdProduct && isset($highestIdProduct->id_num)) {
                    $lastIdNumber = $highestIdProduct->id_num;
                    $newId = 'PRD' . str_pad($lastIdNumber + 1, 4, '0', STR_PAD_LEFT);
                    \Log::info('Generated new ID: ' . $newId . ' from last number: ' . $lastIdNumber);
                } else {
                    // Fallback in case the query didn't work as expected
                    $newId = 'PRD0001';
                    \Log::warning('Falling back to PRD0001 - query returned no results');
                }

                // Ensure this ID doesn't already exist
                while (Product::where('product_id', $newId)->exists()) {
                    $lastIdNumber++;
                    $newId = 'PRD' . str_pad($lastIdNumber + 1, 4, '0', STR_PAD_LEFT);
                    \Log::info('ID already exists, incremented to: ' . $newId);
                }
            } catch (\Exception $e) {
                $newId = 'PRD0001';
                \Log::error('Error in ID generation: ' . $e->getMessage());
            }
        } else {
            $newId = 'PRD0001';
            \Log::info('No products in database, using PRD0001');

            // Try a direct database query to confirm if Product::count() is working
            try {
                $directCount = \DB::table('products')->count();
                \Log::info('Direct DB count of products table: ' . $directCount);
            } catch (\Exception $e) {
                \Log::error('Error with direct DB count: ' . $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'product_id' => $newId,
            'nextId' => $newId,
            'debug' => [
                'product_count' => $productCount,
                'products_exist' => $productExists
            ]
        ]);
    }

    /**
     * Get all categories and subcategories
     */
    public function getCategories()
    {
        $categories = Category::all();
        $subCategories = SubCategory::all();

        return response()->json([
            'status' => 'success',
            'categories' => $categories,
            'sub_categories' => $subCategories
        ]);
    }

    /**
     * Get subcategories for a specific category
     */
    public function getSubCategories($categoryId)
    {
        $subCategories = SubCategory::where('category_id', $categoryId)->get();

        return response()->json([
            'status' => 'success',
            'sub_categories' => $subCategories
        ]);
    }
}
