<?php

declare(strict_types=1);

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSkuAttribute extends Model
{
    protected $fillable = [
        'product_sku_id',
        'attribute_name',
        'attribute_value',
    ];

    /**
     * @return BelongsTo<ProductSku, ProductSkuAttribute>
     */
    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }
}
