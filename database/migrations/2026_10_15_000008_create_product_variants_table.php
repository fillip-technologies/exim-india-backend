<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('signature');
            $table->string('image')->nullable();
            $table->string('moq')->nullable();
            $table->string('supply_ability')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->char('currency', 3)->nullable();
            $table->string('price_unit')->nullable();
            $table->string('stock_status')->default('on_request'); // in_stock, low_stock, out_of_stock, on_request
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'is_active']);
            $table->unique(['product_id', 'signature']);
            $table->index('is_default');
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('products', function (Blueprint $table) {
                $table->foreign('default_variant_id')->references('id')->on('product_variants')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('products', function (Blueprint $table) {
                $table->dropForeign(['default_variant_id']);
            });
        }

        Schema::dropIfExists('product_variants');
    }
};
