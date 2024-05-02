<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Image extends Model
{
    use HasFactory;


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'Description',
        'ImageURL',
        "user_id",
        "Color",
        "Model",
        "Style",
        "ImageName"
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
