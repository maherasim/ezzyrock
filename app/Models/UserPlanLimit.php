<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPlanLimit extends Model
{
    use HasFactory;
    protected $table = 'user_plan_limits';
    protected $fillable = [
         'plan_id', 'plan_limitation'
    ];
    protected $casts = [
        'plan_id'    => 'integer',
    ];
    public function getPlanLimitationAttribute($value)
    {
        $val = isset($value) ? json_decode($value, true) : null;
        return $val;
    }

    public function setPlanLimitationAttribute($value)
    {
        $this->attributes['plan_limitation'] = isset($value) ? json_encode($value) : null;
    }
}