<?php

namespace App\Http\Controllers\Presensi;

use App\Http\Controllers\Controller;
use App\Services\Presensi\AttendanceStore;
use Illuminate\View\View;

class AdminPresensiController extends Controller
{
    public function __construct(private AttendanceStore $store) {}

    /** Monitoring presensi seluruh mata kuliah. */
    public function index(): View
    {
        $rows = [];
        foreach ($this->store->courses() as $code => $course) {
            $rows[] = $course + ['recap' => $this->store->courseRecap($code)];
        }

        return view('presensi.admin.index', [
            'rows' => $rows,
            'threshold' => AttendanceStore::LOW_ATTENDANCE_THRESHOLD,
            'totalStudents' => count($this->store->students()),
        ]);
    }
}
