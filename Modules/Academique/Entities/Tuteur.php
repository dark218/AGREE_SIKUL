<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tuteur extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'tuteurs';

    protected $fillable = [
        'user_id',
        'apprenant_id',
        'nom',
        'prenoms',
        'telephone',
        'email',
        'adresse',
        'relation',
        'profession',
        'employeur',
        'numero_urgence',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function apprenant()
    {
        return $this->belongsTo(Apprenant::class);
    }
}
