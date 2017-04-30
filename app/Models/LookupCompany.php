<?php

namespace App\Models;

use Eloquent;

/**
 * Class ExpenseCategory.
 */
class LookupCompany extends LookupModel
{
    /**
     * @var array
     */
    protected $fillable = [
        'db_server_id',
        'company_id',
    ];

}