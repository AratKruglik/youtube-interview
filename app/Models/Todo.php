<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Todo extends Model
{
    /**
     * Get the user that owns the todo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category that owns the todo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
