{{-- Pratinjau status layar (sementara, untuk review desain). Hapus saat data nyata terhubung. --}}
<div class="devbar">Pratinjau status layar:
    @foreach ($items as $item)
        <a href="{{ url($path) }}?state={{ $item }}" class="{{ $current === $item ? 'on' : '' }}">{{ $item }}</a>
    @endforeach
</div>
