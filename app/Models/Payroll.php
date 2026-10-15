<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = [
        "reference_no", "employee_id", "account_id", "user_id",
        "amount", "paying_method", "note", "created_at",
        "status", "amount_array", "month",
        "acknowledged_at", "acknowledged_note",
    ];

    protected $dates = ['acknowledged_at'];

   

    public function employee()
    {
    	return $this->belongsTo('App\Models\Employee');
    }
}
