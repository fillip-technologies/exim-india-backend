<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Services\CatalogService;

class CategoryController extends Controller
{
    public function __construct(private CatalogService $catalog) {}

    public function index()
    {
        return CategoryResource::collection($this->catalog->categories());
    }

    public function show(string $slug)
    {
        return new CategoryResource($this->catalog->category($slug)->load('analysisSpecs', 'sections.items.detailAttributes'));
    }
}
