<?php

namespace App\Models\T4;

use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    protected $table = 'lecturers';

    protected $primaryKey = 'lecturer_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}
