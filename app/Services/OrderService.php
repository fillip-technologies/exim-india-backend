<?php

namespace App\Services;

use App\Mail\OrderReceived;
use App\Models\Bundle;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderService
{
    public function store(array $data, ?string $ip = null): Order
    {
        $itemsData = $data['items'] ?? null;
        unset($data['items']);

        $order = DB::transaction(function () use ($data, $itemsData, $ip) {
            $order = Order::create([
                ...$data,
                'status' => 'new',
                'ip_address' => $ip,
            ]);

            if (! empty($itemsData) && is_array($itemsData)) {
                foreach ($itemsData as $item) {
                    $this->createItem($order, $item);
                }
            } elseif ($order->product_id) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $order->product_id,
                    'label_snapshot' => Product::find($order->product_id)?->name ?? 'Product Inquiry',
                    'quantity' => $order->quantity ?: '1',
                ]);
            }

            return $order;
        });

        $to = config('mail.contact_notify_to');
        if ($to) {
            Mail::to($to)->queue(new OrderReceived($order->load('product', 'items')));
        }

        return $order;
    }

    private function createItem(Order $order, array $item): void
    {
        $productId = $item['product_id'] ?? $item['productId'] ?? null;
        $variantId = $item['variant_id'] ?? $item['variantId'] ?? null;
        $bundleId = $item['bundle_id'] ?? $item['bundleId'] ?? null;

        $label = 'Item';
        if ($variantId) {
            $variant = ProductVariant::with('product', 'attributeValues')->find($variantId);
            if ($variant) {
                $productId = $productId ?: $variant->product_id;
                $colorPack = $variant->attributeValues->pluck('label')->implode(' – ');
                $label = ($variant->product?->name ?? 'Product').($colorPack ? " – {$colorPack}" : '');
            }
        } elseif ($productId) {
            $label = Product::find($productId)?->name ?? $label;
        } elseif ($bundleId) {
            $label = Bundle::find($bundleId)?->name ?? $label;
        }

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'bundle_id' => $bundleId,
            'label_snapshot' => $label,
            'quantity' => (string) ($item['quantity'] ?? '1'),
            'note' => $item['note'] ?? null,
        ]);
    }
}
