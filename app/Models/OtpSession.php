<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpSession extends Model
{
    use HasFactory;
    
    protected $table = 'otp_session';
    
    protected $fillable = [
        'phone_number',
        'otp',
        'start_date',
        'end_date',
        'tentative'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->end_date->isPast();
    }

    public function getRemainingSeconds(): int
    {
        return max(0, $this->end_date->diffInSeconds(now()));
    }
}
