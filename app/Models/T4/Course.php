<?php

namespace App\Models\T4;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $table = 'courses';

    protected $primaryKey = 'course_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'course_id', 'course_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'course_enrollments', 'course_id', 'student_id', 'course_id', 'student_id');
    }
}
