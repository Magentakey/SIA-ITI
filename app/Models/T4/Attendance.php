<?php

namespace App\Models\T4;

use App\Support\Api\ApiResponse;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    public const STATUSES = ['ATTENDED', 'SICK', 'ABSENT', 'PERMISSION'];

    protected $table = 'attendances';

    protected $primaryKey = 'attendance_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['recorded_at' => 'datetime'];
    }

    /** @return array<string, mixed> */
    public function toApi(): array
    {
        return [
            'attendanceId' => $this->attendance_id,
            'meetingId' => $this->meeting_id,
            'studentId' => $this->student_id,
            'status' => $this->status,
            'method' => $this->method,
            'timestamp' => ApiResponse::iso($this->recorded_at),
            'confirmedBy' => $this->confirmed_by,
        ];
    }
}
