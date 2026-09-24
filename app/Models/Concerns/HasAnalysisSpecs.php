<?php

namespace App\Models\Concerns;

use App\Models\AnalysisSpec;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAnalysisSpecs
{
    public function analysisSpecs(): MorphMany
    {
        return $this->morphMany(AnalysisSpec::class, 'specable')->orderBy('sort_order')->orderBy('id');
    }

    /** Replace all spec rows with the given [{characteristic, requirement}] list. */
    public function syncAnalysisSpecs(array $specs): void
    {
        $this->analysisSpecs()->delete();

        $rows = [];
        foreach (array_values($specs) as $i => $spec) {
            $rows[] = [
                'characteristic' => $spec['characteristic'],
                'requirement' => $spec['requirement'],
                'sort_order' => $i + 1,
            ];
        }

        $this->analysisSpecs()->createMany($rows);
    }
}
