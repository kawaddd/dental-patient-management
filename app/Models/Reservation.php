<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    protected $fillable = ['reservation_id', 'customer_id', 'reserved_at', 'staff', 'status'];

    protected $casts = ['reserved_at' => 'datetime'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function treatmentHistory(): HasOne
    {
        return $this->hasOne(TreatmentHistory::class);
    }
}
