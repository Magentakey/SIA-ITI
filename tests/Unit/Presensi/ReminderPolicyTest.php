<?php

namespace Tests\Unit\Presensi;

use App\Services\Presensi\ReminderPolicy;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class ReminderPolicyTest extends TestCase
{
    private function at(string $time): Carbon
    {
        return Carbon::parse("2026-10-29 {$time}");
    }

    public function test_no_reminder_before_ten_minutes_after_class_end(): void
    {
        $this->assertFalse(ReminderPolicy::shouldRemind($this->at('10:00'), false, null, $this->at('10:09:59')));
    }

    public function test_reminder_starts_exactly_ten_minutes_after_class_end(): void
    {
        $this->assertTrue(ReminderPolicy::shouldRemind($this->at('10:00'), false, null, $this->at('10:10')));
    }

    public function test_no_reminder_once_class_is_finished(): void
    {
        $this->assertFalse(ReminderPolicy::shouldRemind($this->at('10:00'), true, null, $this->at('10:30')));
    }

    public function test_snooze_hides_reminder_until_snooze_expires(): void
    {
        $snoozedUntil = $this->at('10:20');

        $this->assertFalse(ReminderPolicy::shouldRemind($this->at('10:00'), false, $snoozedUntil, $this->at('10:15')));
        $this->assertTrue(ReminderPolicy::shouldRemind($this->at('10:00'), false, $snoozedUntil, $this->at('10:20')));
    }
}
