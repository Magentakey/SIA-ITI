<?php

namespace App\Models\T4;

use Illuminate\Database\Eloquent\Model;

class AttendanceAudit extends Model
{
    public const MANUAL_ENTRY = 'MANUAL_ENTRY';

    public const CORRECTION = 'CORRECTION';

    protected $table = 'attendance_audits';

    protected $primaryKey = 'audit_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}
