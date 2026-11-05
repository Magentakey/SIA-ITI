@php $tone = $tone ?? 'neutral'; @endphp
<span class="badge badge-{{ $tone }}">@if ($tone === 'warning')@include('presensi._icon', ['name' => 'warning', 'size' => 12])@else<i class="dot"></i>@endif{{ $label }}</span>
