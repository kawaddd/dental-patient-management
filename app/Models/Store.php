<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = ['store_code', 'name'];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
