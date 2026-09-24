<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Uniform label/value rows. Owner is a Product or a SectionItem.
        Schema::create('detail_attributes', function (Blueprint $table) {
            $table->id();
            $table->morphs('attributable');
            $table->string('label');
            $table->text('value');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_attributes');
    }
};
