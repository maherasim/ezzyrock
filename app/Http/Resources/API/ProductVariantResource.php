<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $maxPurchaseQty = (int) ($this->max_purchase_qty ?: ($this->product->max_purchase_qty ?: 99));
        $maxAllowedQty = max(1, min(99, (int) $this->stock, $maxPurchaseQty));

        return [
            'id' => $this->id,
            'product_variant_id' => $this->id,
            'product_attribute_option_id' => $this->product_attribute_option_id,
            'option_value' => optional($this->option)->value,
            'attribute_name' => optional(optional($this->option)->attribute)->name,
            'label' => trim((optional(optional($this->option)->attribute)->name ? optional(optional($this->option)->attribute)->name . ': ' : '') . (optional($this->option)->value ?? ('Option #' . $this->id))),
            'price' => $this->price,
            'price_format' => getPriceFormat($this->price),
            'stock' => $this->stock,
            'max_purchase_qty' => $this->max_purchase_qty,
            'max_allowed_quantity' => $maxAllowedQty,
            'is_available' => $maxAllowedQty > 0,
            'status' => $this->status,
        ];
    }
}
