<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class SubcategoryController extends Controller
{
    /**
     * Display a listing of the subcategories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $subCategories = SubCategory::with('category')->get();

        return response()->json([
            'success' => true,
            'data' => $subCategories
        ]);
    }

    /**
     * Store a newly created subcategory in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Log the incoming request for debugging
            \Log::info('SubCategory creation request:', $request->all());

            // Validate request data
            $validator = Validator::make($request->all(), [
                'sub_category_id' => 'required|string|unique:sub_categories,sub_category_id',
                'name' => 'required|string|max:255',
                'category_id' => 'required|string|exists:categories,category_id',
                'description' => 'required|string',
                'status' => 'required|in:active,inactive',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'custom_path' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Handle image upload if present
            $imagePath = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = time() . '_' . $image->getClientOriginalName();

                // Use custom path if provided, otherwise use default path
                $uploadPath = $request->custom_path ?? 'subcategories';

                // Check if the path is absolute or relative
                if (!\Illuminate\Support\Str::startsWith($uploadPath, '/')) {
                    // If it's relative, make it relative to the base_path
                    $fullPath = base_path($uploadPath);
                } else {
                    // If it's absolute, use it directly
                    $fullPath = $uploadPath;
                }

                // Make sure the directory exists
                if (!File::isDirectory($fullPath)) {
                    File::makeDirectory($fullPath, 0755, true);
                }

                // Save the file to the specified path
                $image->move($fullPath, $filename);

                // Store the relative path in the database
                $imagePath = $uploadPath . '/' . $filename;

                \Log::info('Image saved to: ' . $imagePath . ' (Full path: ' . $fullPath . '/' . $filename . ')');
            }

            // Create new subcategory
            $subCategory = SubCategory::create([
                'sub_category_id' => $request->sub_category_id,
                'name' => $request->name,
                'category_id' => $request->category_id,
                'description' => $request->description,
                'status' => $request->status,
                'image_path' => $imagePath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subcategory created successfully',
                'data' => $subCategory
            ], 201);

        } catch (\Exception $e) {
            \Log::error('SubCategory creation error: ' . $e->getMessage());
            \Log::error('Error trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subcategory: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Display the specified subcategory.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $subCategory = SubCategory::where('id', $id)
                ->orWhere('sub_category_id', $id)
                ->with('category')
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $subCategory
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Subcategory not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified subcategory in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $subCategory = SubCategory::where('id', $id)
                ->orWhere('sub_category_id', $id)
                ->firstOrFail();

            // Validate request data
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'category_id' => 'required|string|exists:categories,category_id',
                'description' => 'required|string',
                'status' => 'required|in:active,inactive',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'custom_path' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Handle image upload if present
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($subCategory->image_path && file_exists(base_path($subCategory->image_path))) {
                    File::delete(base_path($subCategory->image_path));
                }

                $image = $request->file('image');
                $filename = time() . '_' . $image->getClientOriginalName();

                // Use custom path if provided, otherwise use default path
                $uploadPath = $request->custom_path ?? 'subcategories';

                // Check if the path is absolute or relative
                if (!\Illuminate\Support\Str::startsWith($uploadPath, '/')) {
                    // If it's relative, make it relative to the base_path
                    $fullPath = base_path($uploadPath);
                } else {
                    // If it's absolute, use it directly
                    $fullPath = $uploadPath;
                }

                // Make sure the directory exists
                if (!File::isDirectory($fullPath)) {
                    File::makeDirectory($fullPath, 0755, true);
                }

                // Save the file to the specified path
                $image->move($fullPath, $filename);

                // Store the relative path in the database
                $imagePath = $uploadPath . '/' . $filename;

                $subCategory->image_path = $imagePath;
            }

            // Update subcategory
            $subCategory->name = $request->name;
            $subCategory->category_id = $request->category_id;
            $subCategory->description = $request->description;
            $subCategory->status = $request->status;
            $subCategory->save();

            return response()->json([
                'success' => true,
                'message' => 'Subcategory updated successfully',
                'data' => $subCategory
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subcategory',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    /**
     * Remove the specified subcategory from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $subCategory = SubCategory::where('id', $id)
                ->orWhere('sub_category_id', $id)
                ->firstOrFail();

            // Delete image if exists
            if ($subCategory->image_path && file_exists(base_path($subCategory->image_path))) {
                File::delete(base_path($subCategory->image_path));
            }

            $subCategory->delete();

            return response()->json([
                'success' => true,
                'message' => 'Subcategory deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subcategory',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    /**
     * Generate a new subcategory ID.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateId()
    {
        try {
            $nextId = SubCategory::generateNextSubCategoryId();

            return response()->json([
                'success' => true,
                'sub_category_id' => $nextId
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in generateId: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get subcategories for a specific category.
     *
     * @param  string  $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByCategory($categoryId)
    {
        $subCategories = SubCategory::where('category_id', $categoryId)
            ->where('status', 'active')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subCategories
        ]);
    }
}
