<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_specs', function (Blueprint $table) {
            $table->id();
            // Owner is a Category (shared default specs) or a Product (its own specs).
            $table->morphs('specable');
            $table->string('characteristic');
            $table->string('requirement');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_specs');
    }
};
