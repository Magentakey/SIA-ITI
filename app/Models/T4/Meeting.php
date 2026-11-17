<?php

namespace App\Models\T4;

use App\Support\Api\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Meeting extends Model
{
    public const ACTIVE = 'ACTIVE';

    public const ENDED = 'ENDED';

    public const METHODS = ['QR', 'BUTTON', 'BOTH'];

    protected $table = 'meetings';

    protected $primaryKey = 'meeting_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'attendance_open' => 'boolean',
            'meeting_number' => 'integer',
        ];
    }

    public function lecturerAttendance(): HasOne
    {
        return $this->hasOne(LecturerAttendance::class, 'meeting_id', 'meeting_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    /** SR-T4-004: Meeting dianggap berakhir bila diakhiri dosen atau endTime terlewat. */
    public function isEnded(): bool
    {
        return $this->status === self::ENDED || $this->end_time->lte(now());
    }

    /** Pintu absensi efektif terbuka: dibuka dosen dan Meeting belum berakhir. */
    public function isAttendanceOpen(): bool
    {
        return $this->attendance_open && ! $this->isEnded();
    }

    /** @return array<string, mixed> */
    public function toApi(): array
    {
        return [
            'meetingId' => $this->meeting_id,
            'courseId' => $this->course_id,
            'scheduleId' => $this->schedule_id,
            'meetingNumber' => $this->meeting_number,
            'topic' => $this->topic,
            'lecturerId' => $this->lecturer_id,
            'startTime' => ApiResponse::iso($this->start_time),
            'endTime' => ApiResponse::iso($this->end_time),
            'status' => $this->isEnded() ? self::ENDED : self::ACTIVE,
            'attendanceOpen' => $this->isAttendanceOpen(),
            'attendanceMethod' => $this->attendance_method,
        ];
    }
}
