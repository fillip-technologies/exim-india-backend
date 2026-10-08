<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->string('group_key')->nullable();
            $table->string('group_name')->nullable();
            $table->string('color_hex', 9)->nullable();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->string('moq')->nullable();
            $table->string('supply_ability')->nullable();
            $table->string('port')->nullable();
            $table->string('cas_no')->nullable();
            $table->text('other_names')->nullable();
            $table->string('mf')->nullable();
            $table->string('einecs_no')->nullable();
            $table->string('fema_no')->nullable();
            $table->string('place_of_origin')->nullable();
            $table->string('types')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('model_number')->nullable();
            $table->string('grade')->nullable();
            $table->string('color_desc')->nullable();
            $table->text('application_summary')->nullable();
            $table->string('purity')->nullable();
            $table->string('shelf_life')->nullable();
            $table->text('packaging_details')->nullable();
            $table->string('delivery_detail')->nullable();
            $table->text('storage')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['category_id', 'slug']);
            $table->index('is_active');
            $table->index('group_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
