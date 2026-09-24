<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AnalysisSpec extends Model
{
    protected $fillable = ['characteristic', 'requirement', 'sort_order'];

    public function specable(): MorphTo
    {
        return $this->morphTo();
    }
}
