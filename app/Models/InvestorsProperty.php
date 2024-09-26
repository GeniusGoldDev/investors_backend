<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Ramsey\Uuid\Uuid;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class InvestorsProperty extends   Authenticatable  implements JWTSubject
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = "string";
    protected $primaryKey = "id";

    protected $table="investors_properties";
    protected $guarded = [];

    protected $casts=["image"=> "array", "filename"=> "array", "square_meters_info" => "array"];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

     /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->id = Uuid::uuid4();
        });

    }
}

