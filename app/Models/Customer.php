<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['customer_id', 'name', 'name_kana', 'gender', 'birth_date', 'phone', 'email', 'notes', 'store_id'];

    protected $casts = ['birth_date' => 'date'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function treatmentHistories(): HasMany
    {
        return $this->hasMany(TreatmentHistory::class);
    }
}
