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
use Carbon\Carbon;
use File;

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
                $now = Carbon::now()->format('Ymd_His');
                $productSlug = Str::slug($request->name);

                // Create directory in Laravel's public folder
                $publicPath = public_path('ProductImages');
                if (!File::exists($publicPath)) {
                    File::makeDirectory($publicPath, 0755, true);
                }

                foreach ($request->file('images') as $imageFile) {
                    // Create a filename with product name and timestamp
                    $filename = "{$productSlug}_{$now}_{$displayOrder}.{$imageFile->extension()}";

                    // Save to public directory
                    $imageFile->move($publicPath, $filename);

                    // Save the relative path in the database that will be used by frontend
                    $relativePath = "ProductImages/{$filename}";

                    ProductImage::create([
                        'product_id' => $product->product_id,
                        'image_path' => $relativePath,
                        'display_order' => $displayOrder++,
                    ]);

                    // Copy to frontend directory using shell command - only if running in local environment
                    if (app()->environment('local')) {
                        try {
                            $frontendPath = base_path('../frontend/src/assets/ProductImages');
                            // Create frontend directory if it doesn't exist
                            if (!File::exists($frontendPath)) {
                                File::makeDirectory($frontendPath, 0755, true);
                            }
                            // Copy the file from public to frontend
                            File::copy("{$publicPath}/{$filename}", "{$frontendPath}/{$filename}");
                        } catch (\Exception $e) {
                            \Log::warning("Could not copy image to frontend: {$e->getMessage()}");
                            // Continue execution even if the copy fails
                        }
                    }
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
    public function getAllCategories()
    {
        $categories = Category::pluck('name')->toArray();

        // Add "All" as the first option if not already in the list
        if (!in_array('All', $categories)) {
            array_unshift($categories, 'All');
        }

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    /**
     * Get products filtered by category
     */
    public function getProducts(Request $request)
    {
        try {
            // Get category filter from request, default to showing all products
            $category = $request->input('category', 'All');

            // Check if requesting featured products only
            $featured = $request->input('featured');

            // Start with a base query
            $query = Product::with(['images', 'category', 'subCategory'])
                ->where('status', 'active');

            // Filter by featured if requested
            if ($featured !== null) {
                $query->where('featured', (int)$featured);
            }

            // Apply category filter if not "All"
            if ($category !== 'All') {
                // Join with the categories table to filter by category name
                $query->whereHas('category', function($q) use ($category) {
                    $q->where('name', $category);
                });
            }

            // Get the products
            $products = $query->get();

            // Format the response data
            $formattedProducts = $products->map(function ($product) {
                // Get the first image or use a placeholder
                $imagePath = $product->images->first()
                    ? asset('src/assets/' . $product->images->first()->image_path)
                    : asset('images/placeholder.jpg');

                return [
                    'id' => $product->product_id,
                    'name' => $product->name,
                    'description' => substr($product->description, 0, 100) . '...', // truncate description
                    'price' => 'MVR ' . number_format($product->price, 2),
                    'category' => $product->category ? $product->category->name : 'Uncategorized',
                    'image' => $imagePath,
                    'rating' => 4.5, // You can implement actual ratings in the future
                    'bestseller' => $product->featured, // Using featured as bestseller
                    'stockQuantity' => $product->stock_quantity
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $formattedProducts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get featured products for homepage
     */
    public function getFeaturedProducts()
    {
        try {
            $query = Product::with(['images', 'category', 'subCategory'])
                ->where('status', 'active')
                ->where('featured', 1);  // Only get products where featured = 1

            // Get the products
            $products = $query->get();

            // Format the response data
            $formattedProducts = $products->map(function ($product) {
                // Get the first image or use a placeholder
                $imagePath = $product->images->first()
                    ? asset('src/assets/' . $product->images->first()->image_path)
                    : asset('images/placeholder.jpg');

                return [
                    'id' => $product->product_id,
                    'name' => $product->name,
                    'description' => substr($product->description, 0, 100) . '...', // truncate description
                    'price' => 'MVR ' . number_format($product->price, 2),
                    'category' => $product->category ? $product->category->name : 'Uncategorized',
                    'image' => $imagePath,
                    'rating' => 4.5, // You can implement actual ratings in the future
                    'bestseller' => true, // These are featured products
                    'stockQuantity' => $product->stock_quantity
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $formattedProducts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch featured products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manually copy images to frontend directory
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncImagesToFrontend(Request $request)
    {
        try {
            $sourceDir = public_path('ProductImages');
            $targetDir = $request->input('frontendPath', base_path('../frontend/src/assets/ProductImages'));

            // Create target directory if it doesn't exist
            if (!File::exists($targetDir)) {
                File::makeDirectory($targetDir, 0755, true);
            }

            // Get all files from source directory
            $files = File::files($sourceDir);
            $copiedCount = 0;

            foreach ($files as $file) {
                $filename = basename($file);
                $targetPath = "{$targetDir}/{$filename}";

                // Copy file if it doesn't exist in target directory
                if (!File::exists($targetPath)) {
                    File::copy($file, $targetPath);
                    $copiedCount++;
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => "{$copiedCount} images synchronized to frontend",
                'source' => $sourceDir,
                'target' => $targetDir
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to sync images to frontend',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
