<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Typeutilisateur extends BaseModel
{
    use HasFactory;
    protected $table="typeutilisateur";
    protected $fillable = [
        "id",
        "libelle",
        "code",
    ];

    public $timestamps = true;


    public function user():HasMany
    {
        return $this->hasMany(User::class);
    }
}
