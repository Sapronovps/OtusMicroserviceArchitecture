<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderDetails extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'product_id', 'quantity'];

    public function order(): HasOne
    {
        return $this->hasOne(Orders::class);
    }
}
