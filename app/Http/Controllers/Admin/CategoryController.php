<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Services\SectionService;

class CategoryController extends Controller
{
    public function __construct(private SectionService $sections) {}

    public function index()
    {
        return Category::ordered()->withCount('products')->get();
    }

    public function store(CategoryRequest $request)
    {
        $data = $request->validated();
        $specs = $data['analysis_specs'] ?? [];
        $sections = $data['sections'] ?? [];
        unset($data['analysis_specs'], $data['sections']);

        $category = Category::create($data);
        $category->syncAnalysisSpecs($specs);
        $this->sections->sync($category, $sections);

        return response()->json($category->load('analysisSpecs', 'sections.items.detailAttributes'), 201);
    }

    public function show(Category $category)
    {
        return $category->load('analysisSpecs', 'sections.items.detailAttributes');
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $data = $request->validated();
        $hasSpecs = array_key_exists('analysis_specs', $data);
        $hasSections = array_key_exists('sections', $data);
        $specs = $data['analysis_specs'] ?? [];
        $sections = $data['sections'] ?? [];
        unset($data['analysis_specs'], $data['sections']);

        $category->update($data);
        if ($hasSpecs) {
            $category->syncAnalysisSpecs($specs);
        }
        if ($hasSections) {
            $this->sections->sync($category, $sections);
        }

        return $category->load('analysisSpecs', 'sections.items.detailAttributes');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->noContent();
    }
}
