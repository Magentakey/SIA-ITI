<?php

namespace App\Models\T4;

use Illuminate\Database\Eloquent\Model;

class ApiUser extends Model
{
    protected $table = 'api_users';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['password'];
}
