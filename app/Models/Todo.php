<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Todo extends Model
{
    // ANTI-PATTERN #2: No $fillable property defined
    // This makes ALL attributes mass assignable, which is a security risk
    // Should have: protected $fillable = ['title', 'description', 'completed', 'priority', 'user_id', 'category_id'];

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
