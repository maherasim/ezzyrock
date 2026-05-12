<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPlan extends Model
{
    use HasFactory;
    protected $table = 'user_plan';
    protected $fillable = [
        'title', 'identifier', 'playstore_identifier','appstore_identifier','type', 'amount','status','duration','description','trial_period','plan_type','module'
    ];
    protected $casts = [
        'amount'    => 'double',
        'status'    => 'integer',
        'trial_period'    => 'integer',
    ];
    public function planlimit(){
        return $this->belongsTo(UserPlanLimit::class,'id', 'plan_id');
    }
    public function staticdata(){
        return $this->belongsTo(StaticData::class,'plan_type', 'id');
    }
    public function scopeList($query)
    {
        return $query->orderBy('updated_at', 'desc');
    }
}