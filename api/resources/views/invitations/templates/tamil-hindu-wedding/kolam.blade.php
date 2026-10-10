{{-- A pulli kolam: a dot grid, then lines drawn around it one at a time. pathLength="1"
     lets the script draw any line with the same dash numbers. --}}
<svg class="kolam {{ $class ?? '' }}" viewBox="-100 -100 200 200" aria-hidden="true">
    @foreach ([-60, -30, 0, 30, 60] as $y)
        @foreach ([-60, -30, 0, 30, 60] as $x)
            @if (abs($x) + abs($y) <= 90)
                <circle class="kolam__dot" cx="{{ $x }}" cy="{{ $y }}" r="3" />
            @endif
        @endforeach
    @endforeach
    <path class="kolam__line" pathLength="1" d="M0 -92 L92 0 L0 92 L-92 0 Z" />
    <path class="kolam__line" pathLength="1" d="M0 -44 A44 44 0 1 1 0 44 A44 44 0 1 1 0 -44" />
    @foreach (range(0, 315, 45) as $angle)
        <path class="kolam__line" pathLength="1" transform="rotate({{ $angle }})"
              d="M0 -14 C16 -30 12 -62 0 -78 C-12 -62 -16 -30 0 -14" />
    @endforeach
    <path class="kolam__line" pathLength="1" d="M-20 -20 H20 V20 H-20 Z" />
</svg>
