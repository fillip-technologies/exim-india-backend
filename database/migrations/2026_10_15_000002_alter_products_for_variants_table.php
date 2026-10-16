<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('short_name')->nullable()->after('name');
            $table->string('tagline')->nullable()->after('short_name');
            $table->text('usage_guide')->nullable()->after('description');
            $table->string('variant_mode')->default('none')->after('storage');
            $table->unsignedBigInteger('default_variant_id')->nullable()->after('variant_mode');
            $table->boolean('is_featured')->default(false)->after('is_active');

            if (Schema::hasColumn('products', 'color_hex')) {
                $table->dropColumn('color_hex');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('color_hex', 9)->nullable();
            $table->dropColumn([
                'short_name',
                'tagline',
                'usage_guide',
                'variant_mode',
                'default_variant_id',
                'is_featured',
            ]);
        });
    }
};
