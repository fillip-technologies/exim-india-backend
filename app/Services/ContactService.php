<?php

namespace App\Services;

use App\Mail\ContactReceived;
use App\Models\Bundle;
use App\Models\Contact;
use App\Models\ContactItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Mail;

class ContactService
{
    public function store(string $type, array $data, ?string $ip = null): Contact
    {
        $itemsData = $data['items'] ?? null;
        unset($data['items']);

        $contact = Contact::create([
            ...$data,
            'type' => $type,
            'status' => 'new',
            'ip_address' => $ip,
        ]);

        if (! empty($itemsData) && is_array($itemsData)) {
            foreach ($itemsData as $item) {
                $productId = $item['product_id'] ?? $item['productId'] ?? null;
                $variantId = $item['variant_id'] ?? $item['variantId'] ?? null;
                $bundleId = $item['bundle_id'] ?? $item['bundleId'] ?? null;
                $quantity = (string) ($item['quantity'] ?? '1');
                $note = $item['note'] ?? null;

                $label = 'Item';
                if ($variantId) {
                    $variant = ProductVariant::with('product', 'attributeValues')->find($variantId);
                    if ($variant) {
                        $productId = $productId ?: $variant->product_id;
                        $colorPack = $variant->attributeValues->pluck('label')->implode(' – ');
                        $label = ($variant->product?->name ?? 'Product') . ($colorPack ? " – {$colorPack}" : '');
                    }
                } elseif ($productId) {
                    $prod = Product::find($productId);
                    if ($prod) {
                        $label = $prod->name;
                    }
                } elseif ($bundleId) {
                    $bundle = Bundle::find($bundleId);
                    if ($bundle) {
                        $label = $bundle->name;
                    }
                }

                ContactItem::create([
                    'contact_id'     => $contact->id,
                    'product_id'     => $productId,
                    'variant_id'     => $variantId,
                    'bundle_id'      => $bundleId,
                    'label_snapshot' => $label,
                    'quantity'       => $quantity,
                    'note'           => $note,
                ]);
            }
        } elseif (! empty($contact->product_id)) {
            $prod = Product::find($contact->product_id);
            ContactItem::create([
                'contact_id'     => $contact->id,
                'product_id'     => $contact->product_id,
                'label_snapshot' => $prod?->name ?? 'Product Inquiry',
                'quantity'       => $contact->quantity ?: '1',
            ]);
        }

        $this->notify($contact);

        return $contact;
    }

    private function notify(Contact $contact): void
    {
        $to = config('mail.contact_notify_to');

        if (! $to) {
            return;
        }

        Mail::to($to)->queue(new ContactReceived($contact->load('product', 'items')));
    }
}
