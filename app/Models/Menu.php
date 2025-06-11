<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;
    protected $table = 'menu';

    public function children()
    {
        return $this->hasMany(Menu::class, 'table_id', 'id')
                    ->where('status', 1);
     }
}
