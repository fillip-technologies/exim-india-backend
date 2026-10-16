<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Services\CatalogService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private CatalogService $catalog) {}

    public function index(Request $request)
    {
        $navOnly = $request->boolean('nav') || $request->query('nav') === '1';
        return CategoryResource::collection($this->catalog->categories($navOnly));
    }

    public function show(string $slug)
    {
        return new CategoryResource($this->catalog->category($slug)->load('analysisSpecs', 'sections.items.detailAttributes'));
    }
}
