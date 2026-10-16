<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Orders follow a fulfilment workflow instead of the contact inbox statuses.
 *   new → confirmed → processing → shipped → delivered   (or cancelled)
 */
return new class extends Migration
{
    private const NEW = ['new', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
    private const OLD = ['new', 'read', 'replied', 'archived'];

    public function up(): void
    {
        $this->swap(self::NEW, ['read' => 'new', 'replied' => 'confirmed', 'archived' => 'cancelled']);
    }

    public function down(): void
    {
        $this->swap(self::OLD, ['confirmed' => 'replied', 'processing' => 'replied', 'shipped' => 'replied', 'delivered' => 'replied', 'cancelled' => 'archived']);
    }

    /** Widen to a string, remap the values, then narrow to the new enum. */
    private function swap(array $statuses, array $map): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->string('status', 20)->default('new')->change());

        foreach ($map as $from => $to) {
            DB::table('orders')->where('status', $from)->update(['status' => $to]);
        }

        Schema::table('orders', fn (Blueprint $t) => $t->enum('status', $statuses)->default('new')->change());
    }
};
