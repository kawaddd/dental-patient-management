<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportJob extends Model
{
    protected $fillable = ['type', 'filename', 'status', 'total_rows', 'success_rows', 'error_rows'];

    public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }
}
