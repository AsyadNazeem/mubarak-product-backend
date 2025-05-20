<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SubcategoryController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\ContactController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make sure your RouteServiceProvider
| is correctly loading this file!
|
*/

// Test route - add this to verify routes are loading
Route::get('/ping', function () {
    return response()->json(['message' => 'API routes are working!']);
});

// Public API routes that can be accessed without authentication
Route::prefix('v1')->group(function () {
    // Product routes for frontend
    Route::get('/products', [ProductController::class, 'getProducts']);
    Route::get('/featured-products', [ProductController::class, 'getFeaturedProducts']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::get('/categories', [ProductController::class, 'getAllCategories']);

    // Future frontend routes can be added here
});

// Contact form route - publicly accessible
Route::post('/contact', [ContactController::class, 'store']);

// Public authentication routes
Route::post('/admin/login', [AuthController::class, 'login']);
Route::post('/admin/register', [AuthController::class, 'register']); // Add this new route for admin registration

// Default authenticated user route
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Admin authenticated routes
Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    // Authentication routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'getUser']);

    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::get('/profile', [ProfileController::class, 'getProfile']);
    Route::post('/profile', [ProfileController::class, 'updateProfile']);

    // Contact messages routes
    Route::get('/contact-messages', [ContactMessageController::class, 'index']);
    Route::patch('/contact-messages/{id}/read', [ContactMessageController::class, 'markAsRead']);
    Route::delete('/contact-messages/{id}', [ContactMessageController::class, 'destroy']);

    // Category routes
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::post('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    Route::get('/users', [App\Http\Controllers\API\UserController::class, 'getAdmins']);
    Route::post('/users', [App\Http\Controllers\API\UserController::class, 'store']);
    Route::get('/users/{id}', [App\Http\Controllers\API\UserController::class, 'show']);
    Route::put('/users/{id}', [App\Http\Controllers\API\UserController::class, 'update']);
    Route::delete('/users/{id}', [App\Http\Controllers\API\UserController::class, 'destroy']);


    // Subcategory routes
    Route::get('/subcategories', [SubcategoryController::class, 'index']);
    Route::post('/subcategories', [SubcategoryController::class, 'store']);
    Route::get('/subcategories/generate-id', [SubcategoryController::class, 'generateId']);
    Route::get('/subcategories/{id}', [SubcategoryController::class, 'show']);
    Route::post('/subcategories/{id}', [SubcategoryController::class, 'update']);
    Route::delete('/subcategories/{id}', [SubcategoryController::class, 'destroy']);
    Route::get('/categories/{categoryId}/subcategories', [SubcategoryController::class, 'getByCategory']);

    // Product routes
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/generate-id', [ProductController::class, 'generateProductId']);
    Route::get('/products/next-id', [ProductController::class, 'generateProductId']); // Add this line to support the current frontend URL
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::post('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Additional product-related routes
    Route::get('/product-categories', [ProductController::class, 'getCategories']);
});
