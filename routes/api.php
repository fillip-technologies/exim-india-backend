<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

// Public API
Route::get('/categories', [Api\CategoryController::class, 'index']);
Route::get('/categories/{slug}', [Api\CategoryController::class, 'show']);
Route::get('/categories/{slug}/products', [Api\ProductController::class, 'index']);
Route::get('/categories/{slug}/products/{productSlug}', [Api\ProductController::class, 'show']);
Route::get('/products', [Api\ProductController::class, 'catalog']);
Route::get('/products/{productSlug}', [Api\ProductController::class, 'showDirect']);
Route::get('/testimonials', [Api\TestimonialController::class, 'index']);

Route::middleware('throttle:5,1')->group(function () {
    Route::post('/contact', [Api\ContactController::class, 'store']);
    Route::post('/orders', [Api\OrderController::class, 'store']);
});

// Admin API
Route::prefix('admin')->group(function () {
    Route::post('/login', [Admin\AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/logout', [Admin\AuthController::class, 'logout']);
        Route::get('/me', [Admin\AuthController::class, 'me']);

        Route::apiResource('categories', Admin\CategoryController::class);
        Route::apiResource('products', Admin\ProductController::class);
        Route::put('products/{product}/options', [Admin\ProductOptionController::class, 'update']);
        Route::post('products/{product}/variants/generate', [Admin\ProductVariantController::class, 'generate']);
        Route::get('products/{product}/variants', [Admin\ProductVariantController::class, 'index']);
        Route::post('products/{product}/variants', [Admin\ProductVariantController::class, 'store']);
        Route::put('variants/{variant}', [Admin\ProductVariantController::class, 'update']);
        Route::delete('variants/{variant}', [Admin\ProductVariantController::class, 'destroy']);
        Route::put('products/{product}/fields', [Admin\ProductFieldController::class, 'update']);

        Route::apiResource('attributes', Admin\AttributeController::class);
        Route::apiResource('attributes.values', Admin\AttributeValueController::class);
        Route::apiResource('bundles', Admin\BundleController::class);
        Route::apiResource('fields', Admin\FieldDefinitionController::class);
        Route::put('fields/{field}/assignments', [Admin\FieldDefinitionController::class, 'assignments']);

        Route::apiResource('testimonials', Admin\TestimonialController::class);
        Route::apiResource('contacts', Admin\ContactController::class)->only(['index', 'show', 'update', 'destroy']);
        Route::post('/uploads', [Admin\UploadController::class, 'store']);
    });
});
