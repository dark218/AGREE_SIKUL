<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        "title",
        "body",
        "data",
        "user_id",
        "is_read",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
