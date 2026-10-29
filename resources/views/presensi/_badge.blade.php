@php
    $value = $status instanceof \App\Services\Presensi\AttendanceStatus ? $status->value : $status;
    $map = [
        'hadir' => ['Hadir', 'bg-emerald-50 text-emerald-700'],
        'sakit' => ['Sakit', 'bg-amber-50 text-amber-700'],
        'izin' => ['Izin', 'bg-sky-50 text-sky-700'],
        'alpa' => ['Alpa', 'bg-red-50 text-red-700'],
        'belum' => ['Belum dimulai', 'bg-slate-100 text-slate-600'],
        'berlangsung' => ['Berlangsung', 'bg-emerald-50 text-emerald-700'],
        'selesai' => ['Selesai', 'bg-slate-100 text-slate-600'],
    ];
    [$label, $class] = $map[$value] ?? ['Belum tercatat', 'bg-slate-100 text-slate-500'];
@endphp
<span class="inline-block rounded-full px-3 py-0.5 text-xs font-medium {{ $class }}">{{ $label }}</span>
