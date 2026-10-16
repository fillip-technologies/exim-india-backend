<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Orders used to live in `contacts` (type = 'order') with their lines in
 * `contact_items`. They now have their own `orders` / `order_items` tables;
 * `contacts` only holds Contact Us messages.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('quantity')->nullable();
            $table->text('address')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['new', 'read', 'replied', 'archived'])->default('new');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('bundle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label_snapshot');
            $table->string('quantity')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // Move existing orders (and their lines) across
        $map = [];
        DB::table('contacts')->where('type', 'order')->orderBy('id')->get()->each(function ($c) use (&$map) {
            $message = collect([
                $c->message,
                $c->product_interest ? 'Product interest: '.$c->product_interest : null,
                $c->company ? 'Company: '.$c->company : null,
            ])->filter()->implode("\n");

            $map[$c->id] = DB::table('orders')->insertGetId([
                'name' => $c->name,
                'email' => $c->email,
                'phone' => $c->phone,
                'product_id' => $c->product_id,
                'quantity' => $c->quantity,
                'address' => $c->address,
                'message' => $message ?: null,
                'status' => $c->status,
                'ip_address' => $c->ip_address,
                'created_at' => $c->created_at,
                'updated_at' => $c->updated_at,
            ]);
        });

        if (Schema::hasTable('contact_items')) {
            DB::table('contact_items')->orderBy('id')->get()->each(function ($i) use ($map) {
                if (! isset($map[$i->contact_id])) {
                    return;
                }
                DB::table('order_items')->insert([
                    'order_id' => $map[$i->contact_id],
                    'product_id' => $i->product_id,
                    'variant_id' => $i->variant_id,
                    'bundle_id' => $i->bundle_id,
                    'label_snapshot' => $i->label_snapshot,
                    'quantity' => $i->quantity,
                    'note' => $i->note,
                    'created_at' => $i->created_at,
                    'updated_at' => $i->updated_at,
                ]);
            });
            Schema::drop('contact_items');
        }

        DB::table('contacts')->where('type', 'order')->delete();

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['type', 'product_id', 'quantity', 'address']);
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->enum('type', ['contact', 'order'])->default('contact')->after('id');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('quantity')->nullable();
            $table->text('address')->nullable();
        });

        Schema::create('contact_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('bundle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label_snapshot');
            $table->string('quantity')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        DB::table('orders')->orderBy('id')->get()->each(function ($o) {
            $contactId = DB::table('contacts')->insertGetId([
                'type' => 'order',
                'name' => $o->name,
                'email' => $o->email,
                'phone' => $o->phone,
                'product_id' => $o->product_id,
                'quantity' => $o->quantity,
                'address' => $o->address,
                'message' => $o->message,
                'status' => $o->status,
                'ip_address' => $o->ip_address,
                'created_at' => $o->created_at,
                'updated_at' => $o->updated_at,
            ]);

            DB::table('order_items')->where('order_id', $o->id)->get()->each(fn ($i) => DB::table('contact_items')->insert([
                'contact_id' => $contactId,
                'product_id' => $i->product_id,
                'variant_id' => $i->variant_id,
                'bundle_id' => $i->bundle_id,
                'label_snapshot' => $i->label_snapshot,
                'quantity' => $i->quantity,
                'note' => $i->note,
                'created_at' => $i->created_at,
                'updated_at' => $i->updated_at,
            ]));
        });

        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
