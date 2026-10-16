<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'product' => $this->whenLoaded('product', fn () => $this->product ? [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
            ] : null),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'id' => $i->id,
                'product_id' => $i->product_id,
                'variant_id' => $i->variant_id,
                'bundle_id' => $i->bundle_id,
                'label' => $i->label_snapshot,
                'quantity' => $i->quantity,
                'note' => $i->note,
            ])->values()->all()),
            'quantity' => $this->quantity,
            'address' => $this->address,
            'message' => $this->message,
            'status' => $this->status,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at,
        ];
    }
}
