<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('label');
            $table->string('code')->nullable();
            $table->string('hex', 9)->nullable();
            $table->text('gradient')->nullable();
            $table->string('image')->nullable();
            $table->string('tone')->nullable();
            $table->decimal('numeric_value', 12, 3)->nullable();
            $table->string('unit')->nullable();
            $table->string('group_name')->nullable();
            $table->boolean('is_blend')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['attribute_id', 'slug']);
            $table->index('is_active');
            $table->index('sort_order');
            $table->index('code');
            $table->index('group_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};
