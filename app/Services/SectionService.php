<?php

namespace App\Services;

use App\Models\Category;

class SectionService
{
    /**
     * Replace all of a category's sections with the given list:
     * [{key, title, items: [{title, subtitle?, color_hex?, attributes: [{label, value}]}]}]
     */
    public function sync(Category $category, array $sections): void
    {
        $category->sections()->delete();

        foreach (array_values($sections) as $i => $data) {
            $section = $category->sections()->create([
                'key' => $data['key'],
                'title' => $data['title'],
                'sort_order' => $i + 1,
            ]);

            foreach (array_values($data['items'] ?? []) as $j => $itemData) {
                $item = $section->items()->create([
                    'title' => $itemData['title'],
                    'subtitle' => $itemData['subtitle'] ?? null,
                    'color_hex' => $itemData['color_hex'] ?? null,
                    'sort_order' => $j + 1,
                ]);
                $item->syncDetailAttributes($itemData['attributes'] ?? []);
            }
        }
    }
}
