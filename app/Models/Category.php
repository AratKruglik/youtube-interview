<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    // ANTI-PATTERN #2: No $fillable property defined
    // This makes ALL attributes mass assignable, which is a security risk
    // Should have: protected $fillable = ['name', 'description', 'color'];

    /**
     * Get the todos for the category
     */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }
}
