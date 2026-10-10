{{-- 1 · Opening: Pillaiyar suzhi, the kuthuvilakku lights, the gopuram rises in layers. --}}
@php
    // Gopuram tiers, ground up: [bottom y, top y, bottom half-width, top half-width, niches].
    $tiers = [[290, 245, 100, 88, 5], [245, 205, 85, 73, 4], [205, 170, 70, 58, 3], [170, 140, 55, 44, 3], [140, 115, 41, 32, 2]];
@endphp
<section class="scene scene--opening" data-scene="opening" data-length="160">
    <div class="scene__stage">
        <div class="layer opening__dawn"></div>
        <div class="opening__sun"></div>

        <svg class="opening__gopuram" viewBox="0 0 300 360" aria-hidden="true">
            <g class="gopuram__part">
                <rect x="40" y="290" width="220" height="70" fill="#a85a32" />
                <rect x="40" y="290" width="220" height="8" fill="#7d3f20" />
                <path d="M130 360 V318 Q150 296 170 318 V360 Z" fill="#2a0a0f" />
            </g>
            @foreach ($tiers as [$bottom, $top, $bottomHalf, $topHalf, $niches])
                <g class="gopuram__part">
                    <path d="M{{ 150 - $bottomHalf }} {{ $bottom }} L{{ 150 - $topHalf }} {{ $top }} H{{ 150 + $topHalf }} L{{ 150 + $bottomHalf }} {{ $bottom }} Z"
                          fill="{{ $loop->even ? '#c7794b' : '#b96a3e' }}" />
                    <rect x="{{ 150 - $bottomHalf }}" y="{{ $bottom - 6 }}" width="{{ $bottomHalf * 2 }}" height="6" fill="#7d3f20" />
                    @for ($n = 0; $n < $niches; $n++)
                        @php $x = 150 - $topHalf + ($topHalf * 2) * ($n + 0.5) / $niches; @endphp
                        <rect x="{{ $x - 5 }}" y="{{ $top + 9 }}" width="10" height="{{ $bottom - $top - 20 }}" rx="5" fill="#6e2f17" />
                    @endfor
                </g>
            @endforeach
            <g class="gopuram__part">
                <path d="M105 115 V100 Q150 74 195 100 V115 Z" fill="#c7794b" />
                @foreach ([118, 134, 150, 166, 182] as $x)
                    <path d="M{{ $x - 5 }} 92 Q{{ $x }} 78 {{ $x + 5 }} 92 Z M{{ $x - 1 }} 80 L{{ $x }} 70 L{{ $x + 1 }} 80 Z" fill="#e0a93b" />
                @endforeach
            </g>
        </svg>

        <div class="layer opening__ground"></div>

        <div class="opening__suzhi" aria-hidden="true">உ</div>

        <div class="scene__content opening__title">
            <x-bi as="p" :text="$page->label('wedding_invitation')" />
        </div>

        <div class="lamp" aria-hidden="true">
            <div class="lamp__glow"></div>
            {{-- Two wicks. The still flame shows until the Lottie loop loads, and with reduced motion. --}}
            @foreach (['left', 'right'] as $side)
                <div class="lamp__flame lamp__flame--{{ $side }}" data-lottie="flame">
                    <svg class="lottie-still" viewBox="0 0 40 64">
                        <path d="M20 2 C24 14 38 30 36 44 C34 56 28 62 20 62 C12 62 6 56 4 44 C2 30 16 14 20 2 Z" fill="#f58c1e" />
                        <path d="M20 24 C22 30 29 42 28 48 C27 56 24 60 20 60 C16 60 13 56 12 48 C11 42 18 30 20 24 Z" fill="#ffd54a" />
                    </svg>
                </div>
            @endforeach
            <svg class="lamp__body" viewBox="0 0 120 220">
                <circle cx="60" cy="18" r="8" fill="#e0a93b" />
                <path d="M58 4 L60 -4 L62 4 Z" fill="#e0a93b" />
                <rect x="56" y="24" width="8" height="24" fill="#c8952c" />
                <path d="M10 50 Q60 82 110 50 Q60 60 10 50 Z" fill="#e0a93b" />
                <ellipse cx="60" cy="51" rx="50" ry="5" fill="#f6d27a" />
                <rect x="55" y="62" width="10" height="120" fill="#c8952c" />
                <ellipse cx="60" cy="96" rx="12" ry="6" fill="#e0a93b" />
                <ellipse cx="60" cy="140" rx="10" ry="5" fill="#e0a93b" />
                <path d="M18 214 Q60 168 102 214 Z" fill="#c8952c" />
                <ellipse cx="60" cy="214" rx="44" ry="6" fill="#e0a93b" />
            </svg>
        </div>

        <div class="scroll-hint">
            <x-bi :text="$page->label('scroll_hint')" />
            <svg class="scroll-hint__arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6" /></svg>
        </div>
    </div>
</section>
