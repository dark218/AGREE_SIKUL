<?php

namespace Modules\Administration\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ErrorLog extends Model
{
    protected $table = 'errorlogs';

    protected $fillable = [
        'module',
        'methode',
        'message',
    ];
}
