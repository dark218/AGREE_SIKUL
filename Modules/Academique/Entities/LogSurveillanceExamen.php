<?php

namespace Modules\Academique\Entities;

use Illuminate\Database\Eloquent\Model;

class LogSurveillanceExamen extends Model
{
    protected $table = 'logs_surveillance_examen';
    protected $fillable = [
        'tentative_examen_id',
        'apprenant_id',
        'type_incident',
        'details',
        'user_agent',
        'ip_address',
        'horodatage',
    ];

    protected $casts = [
        'horodatage' => 'datetime',
    ];

    public function tentative()
    {
        return $this->belongsTo(TentativeExamen::class, 'tentative_examen_id');
    }

    public function apprenant()
    {
        return $this->belongsTo(Apprenant::class);
    }
}
