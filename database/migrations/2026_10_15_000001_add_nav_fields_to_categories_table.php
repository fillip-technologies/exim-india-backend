<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('show_in_nav')->default(true)->after('is_active');
            $table->string('nav_label')->nullable()->after('show_in_nav');
            $table->string('page_template')->default('standard')->after('nav_label');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['show_in_nav', 'nav_label', 'page_template']);
        });
    }
};
