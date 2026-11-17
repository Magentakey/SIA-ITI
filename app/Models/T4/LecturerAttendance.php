<?php

namespace App\Models\T4;

use App\Support\Api\ApiResponse;
use Illuminate\Database\Eloquent\Model;

class LecturerAttendance extends Model
{
    protected $table = 'lecturer_attendances';

    protected $primaryKey = 'lecturer_attendance_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_present_at_start' => 'boolean',
            'is_present_at_end' => 'boolean',
            'reminder_sent' => 'boolean',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /** isPresent turunan dari dua checkpoint dan tidak bisa diubah langsung (SR-T4-001). */
    public function isPresent(): bool
    {
        return $this->is_present_at_start && $this->is_present_at_end;
    }

    /** @return array<string, mixed> */
    public function toApi(): array
    {
        return [
            'lecturerAttendanceId' => $this->lecturer_attendance_id,
            'meetingId' => $this->meeting_id,
            'lecturerId' => $this->lecturer_id,
            'isPresentAtStart' => $this->is_present_at_start,
            'isPresentAtEnd' => $this->is_present_at_end,
            'isPresent' => $this->isPresent(),
            'reminderSent' => $this->reminder_sent,
            'startedAt' => ApiResponse::iso($this->started_at),
            'endedAt' => ApiResponse::iso($this->ended_at),
        ];
    }
}
