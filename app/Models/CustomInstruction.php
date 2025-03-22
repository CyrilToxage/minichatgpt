<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomInstruction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'about_you',
        'assistant_behavior',
        'custom_commands',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'custom_commands' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the custom instructions.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
