<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Order;
class OrderDetail extends Model
{
    use HasFactory;
    protected $table = 'orderdetail';
    public function product()
    {
        return $this->belongsTo(Order::class);
    }
}
