<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellProperty extends Model
{
    use HasFactory;

    protected $guarded=[];

    public function property()
    {
        return $this->belongsTo(InvestorsProperty::class, 'property_id','id');
    }

    public function approved_request()
    {
        return $this->belongsTo(ApprovedRequest::class, 'approved_request_id','id');
    }

    public function marketer()
    {
        return $this->belongsTo(Investor::class, 'marketer_id','id');
    }
}
