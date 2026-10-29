<?php

namespace Tests\Unit\Presensi;

use App\Services\Presensi\AttendanceStatus;
use App\Services\Presensi\RecapCalculator;
use PHPUnit\Framework\TestCase;

class RecapCalculatorTest extends TestCase
{
    public function test_summarize_counts_each_status_and_percentage(): void
    {
        $recap = RecapCalculator::summarize(['hadir', 'hadir', 'sakit', 'izin', 'alpa']);

        $this->assertSame(2, $recap['attendedSessions']);
        $this->assertSame(1, $recap['sickSessions']);
        $this->assertSame(1, $recap['permissionSessions']);
        $this->assertSame(1, $recap['absentSessions']);
        $this->assertSame(5, $recap['totalSessions']);
        $this->assertSame(40.0, $recap['totalPercentage']);
    }

    public function test_summarize_with_no_sessions_does_not_divide_by_zero(): void
    {
        $recap = RecapCalculator::summarize([]);

        $this->assertSame(0, $recap['totalSessions']);
        $this->assertSame(0.0, $recap['totalPercentage']);
    }

    public function test_summarize_accepts_enum_values(): void
    {
        $recap = RecapCalculator::summarize([AttendanceStatus::Hadir, AttendanceStatus::Alpa]);

        $this->assertSame(50.0, $recap['totalPercentage']);
    }

    public function test_average_of_percentages(): void
    {
        $this->assertSame(75.0, RecapCalculator::average([100.0, 50.0]));
        $this->assertSame(0.0, RecapCalculator::average([]));
    }
}
