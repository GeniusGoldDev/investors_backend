<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

class Request extends Model
{
    use HasFactory;
    protected $fillable=['name', 'investor_id'];
    public $incrementing = false;
    protected $keyType = "string";
    protected $primaryKey = "id";

    protected $with =['investor'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->id = Uuid::uuid4();
        });

    }

    public function request_conversation()
    {
        return $this->hasMany(RequestConversation::class, 'request_id','id');
    }

    public function investor() :BelongsTo
    {
        return $this->belongsTo(Investor::class, 'investor_id', 'id');
    }
    public function property() :BelongsTo
    {
        return $this->belongsTo(InvestorsProperty::class, 'investor_property_id', 'id');
    }

}
