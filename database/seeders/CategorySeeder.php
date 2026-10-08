<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Services\SectionService;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $rows = json_decode(file_get_contents(__DIR__.'/data/categories.json'), true);
        $sections = app(SectionService::class);

        foreach ($rows as $row) {
            $specs = $row['analysis_specs'] ?? [];
            $sectionRows = $row['sections'] ?? [];
            unset($row['analysis_specs'], $row['sections']);

            $category = Category::updateOrCreate(['slug' => $row['slug']], $row);
            $category->syncAnalysisSpecs($specs);
            $sections->sync($category, $sectionRows);
        }
    }
}
