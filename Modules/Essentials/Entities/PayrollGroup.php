<?php

namespace Modules\Essentials\Entities;

use Illuminate\Database\Eloquent\Model;

class PayrollGroup extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
    * The table associated with the model.
    *
    * @var string
    */
    protected $table = 'essentials_payroll_groups';

    /**
     * Get the transactions for the payroll group.
     */
    public function payrollGroupTransactions()
    {
        return $this->belongsToMany('App\Models\Transaction', 'essentials_payroll_group_transactions', 'payroll_group_id', 'transaction_id');
    }
}
