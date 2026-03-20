<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentHistory extends Model
{
    protected $fillable = [
        'customer_id',
        'reservation_id',
        'treated_at',
        'treatment_type',
        'treatment_area',
        'staff',
        'notes',
    ];

    protected $casts = ['treated_at' => 'datetime'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('treatment_type', 'like', "%{$type}%");
    }

    public function scopeByArea(Builder $query, string $area): Builder
    {
        return $query->where('treatment_area', 'like', "%{$area}%");
    }
}
