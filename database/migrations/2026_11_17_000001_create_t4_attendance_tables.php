<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Tabel API Presensi (T4), mengikuti model data SRS-SIA-T4 v1.1 bagian 7.1 (istilah Meeting).
 | api_users, students, lecturers, courses, course_enrollments adalah stand-in data milik Tim 1/Tim 2
 | selama endpoint mereka belum siap. Ganti dengan konsumsi API Tim 1/Tim 2 bila sudah tersedia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_users', function (Blueprint $table) {
            $table->string('user_id')->primary();
            $table->string('identifier')->unique(); // NIM / NIP / kode pegawai
            $table->string('full_name');
            $table->string('role'); // STUDENT | LECTURER | KAPRODI | ADMIN_ACADEMIC
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->string('student_id')->primary();
            $table->string('nim')->unique();
            $table->string('full_name');
            $table->timestamps();
        });

        Schema::create('lecturers', function (Blueprint $table) {
            $table->string('lecturer_id')->primary();
            $table->string('nip')->unique();
            $table->string('full_name');
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->string('course_id')->primary();
            $table->string('course_code')->unique();
            $table->string('course_name');
            $table->string('lecturer_id')->index(); // dosen pengampu
            $table->timestamps();
        });

        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('course_id');
            $table->string('student_id');
            $table->timestamps();
            $table->unique(['course_id', 'student_id']);
        });

        Schema::create('meetings', function (Blueprint $table) {
            $table->string('meeting_id')->primary();
            $table->string('course_id')->index();
            $table->string('schedule_id')->nullable();
            $table->unsignedInteger('meeting_number');
            $table->string('topic')->nullable();
            $table->string('lecturer_id');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('status')->default('ACTIVE'); // ACTIVE | ENDED
            $table->boolean('attendance_open')->default(false);
            $table->string('attendance_method')->default('BOTH'); // QR | BUTTON | BOTH
            $table->timestamps();
        });

        Schema::create('lecturer_attendances', function (Blueprint $table) {
            $table->string('lecturer_attendance_id')->primary();
            $table->string('meeting_id')->unique();
            $table->string('lecturer_id');
            $table->boolean('is_present_at_start')->default(false);
            $table->boolean('is_present_at_end')->default(false);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->string('attendance_id')->primary();
            $table->string('meeting_id');
            $table->string('student_id');
            $table->string('status'); // ATTENDED | SICK | ABSENT | PERMISSION
            $table->string('method'); // QR | BUTTON | MANUAL
            $table->dateTime('recorded_at');
            $table->string('confirmed_by')->nullable(); // userId dosen, dari token
            $table->timestamps();
            $table->unique(['meeting_id', 'student_id']); // satu mahasiswa satu catatan per meeting
        });

        Schema::create('attendance_audits', function (Blueprint $table) {
            $table->string('audit_id')->primary();
            $table->string('attendance_id')->index();
            $table->string('changed_by');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->string('action_type'); // MANUAL_ENTRY | CORRECTION
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['attendance_audits', 'attendances', 'lecturer_attendances', 'meetings', 'course_enrollments', 'courses', 'lecturers', 'students', 'api_users'] as $name) {
            Schema::dropIfExists($name);
        }
    }
};
