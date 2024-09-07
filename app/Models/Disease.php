<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disease extends Model
{
    use HasFactory;
    protected $primaryKey = 'disease_id';
    const CREATED_AT = 'disease_created_at';
    const UPDATED_AT = 'disease_updated_at';
    protected $fillable = [
        'disease_id',
        'disease_name',
        'disease_thumbnail',
        'general_overview',
        'cause',
        'risk_subjects',
        'diagnosis',
        'treatment-method',
        'disease_is_delete',
        'disease_is_show',
        'disease_created_at',
        'disease_updated_at',
    ];
    public function categories(){
        return $this->belongsToMany(Category::class);
    }
}
