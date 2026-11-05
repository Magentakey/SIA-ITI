<?php

namespace Tests\Unit\Presensi;

use App\Support\PresensiFormat;
use PHPUnit\Framework\TestCase;

class PresensiFormatTest extends TestCase
{
    public function test_percent_is_attended_over_total_rounded_to_two_decimals(): void
    {
        $this->assertSame(90.91, PresensiFormat::percent(10, 11));
        $this->assertSame(81.82, PresensiFormat::percent(9, 11));
        $this->assertSame(100.0, PresensiFormat::percent(10, 10));
    }

    public function test_percent_with_no_meetings_does_not_divide_by_zero(): void
    {
        $this->assertSame(0.0, PresensiFormat::percent(0, 0));
    }

    public function test_label_uses_indonesian_format_without_trailing_zeros(): void
    {
        $this->assertSame('90,91', PresensiFormat::label(90.91));
        $this->assertSame('90', PresensiFormat::label(90.0));
        $this->assertSame('100', PresensiFormat::label(100.0));
        $this->assertSame('0', PresensiFormat::label(0.0));
        $this->assertSame('50,5', PresensiFormat::label(50.5));
    }

    public function test_eligibility_uses_placeholder_threshold_inclusive(): void
    {
        $this->assertTrue(PresensiFormat::isEligible(75.0));
        $this->assertFalse(PresensiFormat::isEligible(74.99));
        $this->assertTrue(PresensiFormat::isEligible(60.0, 60.0));
    }

    public function test_average_of_percentages(): void
    {
        $this->assertSame(80.0, PresensiFormat::average([80.0, 100.0, 90.0, 70.0, 50.0, 90.0]));
        $this->assertSame(0.0, PresensiFormat::average([]));
    }

    public function test_status_tone_follows_foundation_mapping(): void
    {
        $this->assertSame('success', PresensiFormat::tone('Hadir'));
        $this->assertSame('info', PresensiFormat::tone('Sakit'));
        $this->assertSame('warning', PresensiFormat::tone('Izin'));
        $this->assertSame('danger', PresensiFormat::tone('Alpa'));
        $this->assertSame('neutral', PresensiFormat::tone('Tidak dikenal'));
    }
}
