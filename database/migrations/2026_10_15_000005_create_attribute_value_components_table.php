<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_value_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blend_value_id')->constrained('attribute_values')->cascadeOnDelete();
            $table->foreignId('component_value_id')->constrained('attribute_values')->cascadeOnDelete();
            $table->decimal('ratio', 5, 2)->default(1.00);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['blend_value_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_value_components');
    }
};
