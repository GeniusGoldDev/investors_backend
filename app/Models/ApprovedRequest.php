<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
use App\Models\Request;


class ApprovedRequest extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = "string";
    protected $primaryKey = "id";
    protected $guarded = [];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->id = Uuid::uuid4();
        });

        static::created(function ($user) {

            // Request::where('id', $user->request_id)->where('id', '!=', null)->update([
            //     'status' => 'approved'
            // ]);
        });
    }

    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id', 'id');
    }
    public function property()
    {
        return $this->belongsTo(InvestorsProperty::class, 'investor_property_id', 'id');
    }

    public function amount_paid()
    {
        return $this->hasMany(RequestTransaction::class, 'approved_request_id', 'id')
            ->where('status', 'approved');
    }

    public function attached_marketer()
    {
        return $this->hasMany(SellProperty::class, 'approved_request_id', 'id');
    }
}
