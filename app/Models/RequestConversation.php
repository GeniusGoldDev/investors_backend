<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class RequestConversation extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = "string";
    protected $primaryKey = "id";
    protected $guarded=[];
    protected $table = "request_conversation";
    protected $hidden = [
        'convo_1_id',
        'convo_2_id',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->id = Uuid::uuid4();
        });

    }

}
