<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class RequestTransaction extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = "string";
    protected $table = "request_transaction";
    protected $guarded=[];


    protected $primaryKey = "id";

    public static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->id = Uuid::uuid4();
        });

    }

    

    
}
