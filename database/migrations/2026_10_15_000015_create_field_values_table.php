<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_definition_id')->constrained()->cascadeOnDelete();
            $table->string('owner_type');
            $table->unsignedBigInteger('owner_id');
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 18, 4)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->index(['owner_type', 'owner_id']);
            $table->unique(['field_definition_id', 'owner_type', 'owner_id'], 'field_values_owner_unique');
            $table->index(['field_definition_id', 'value_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_values');
    }
};
