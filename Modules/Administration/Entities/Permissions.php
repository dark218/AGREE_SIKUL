<?php

namespace Modules\Administration\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Permissions extends BaseModel
{
    use HasFactory;
    protected $table= "permissions";
    protected $fillable = [
        "name",
        "guard_name",
        "libelle",
        'feature_id',
    ];
    public $timestamps = true;
    public function feature():BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }
}
