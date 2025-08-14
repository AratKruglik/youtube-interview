<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /**
     * Get the todos for the category
     */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }
}
