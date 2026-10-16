<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->string('type')->default('text'); // text, textarea, number, select, multiselect, boolean, url, file, color
            $table->json('options')->nullable();
            $table->string('unit')->nullable();
            $table->string('group_name')->nullable();
            $table->string('applies_to')->default('product'); // product, variant
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_public')->default(true);
            $table->boolean('is_searchable')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('applies_to');
            $table->index('group_name');
            $table->index('is_filterable');
            $table->index('is_public');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_definitions');
    }
};
