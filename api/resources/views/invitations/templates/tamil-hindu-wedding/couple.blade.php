{{-- 2 · The couple: thoranam and banana trees frame the entrance; the names fade in. --}}
@php
    // Mango leaves and marigolds along the thoranam rope, a curve from (0,10) through (400,10).
    $ropeY = fn (float $x) => 10 + 40 * ($x / 400) * (1 - $x / 400);

    // Names wrap between words, never inside one: the CSS sizes each language's
    // names so its longest word fits. Counted in characters; Tamil letters run
    // about a quarter wider than Latin ones at the same size.
    $longestWord = fn (string $lang) => collect([$page->get("bride.name.{$lang}"), $page->get("groom.name.{$lang}")])
        ->flatMap(fn ($text) => preg_split('/\s+/u', (string) $text))
        ->map(fn ($word) => mb_strlen($word) + 0.25 * preg_match_all('/[\x{0B80}-\x{0BFF}]/u', $word))
        ->max() ?: 1;
@endphp
<section class="scene scene--couple" data-scene="couple" data-length="150">
    <div class="scene__stage">
        <svg class="layer couple__arch" viewBox="0 0 280 400" preserveAspectRatio="xMidYMax meet" aria-hidden="true">
            <path fill-rule="evenodd" fill="#8c1d3a"
                  d="M0 400 V140 A140 140 0 0 1 280 140 V400 Z M16 400 V140 A124 124 0 0 1 264 140 V400 Z" />
            <path fill="none" stroke="#e0a93b" stroke-width="3" d="M24 400 V140 A116 116 0 0 1 256 140 V400" />
        </svg>

        @foreach (['left', 'right'] as $side)
            <svg class="banana banana--{{ $side }}" viewBox="0 0 120 400" aria-hidden="true">
                <g @if ($side === 'right') transform="translate(120 0) scale(-1 1)" @endif>
                    <path d="M44 400 L50 150 H66 L70 400 Z" fill="#7d9a3c" />
                    <path d="M50 150 H66 L68 250 H48 Z" fill="#6b8a30" />
                    @foreach ([-70, -40, -12, 18, 48] as $angle)
                        <g transform="rotate({{ $angle }} 58 150)">
                            <path d="M58 150 C40 120 44 50 58 10 C72 50 76 120 58 150 Z" fill="{{ $loop->odd ? '#2f7d32' : '#3f9a43' }}" />
                            <path d="M58 150 L58 18" stroke="#1b5e20" stroke-width="2" />
                        </g>
                    @endforeach
                    @foreach ([[62, 200], [72, 210], [58, 214], [68, 224]] as [$x, $y])
                        <path d="M{{ $x }} {{ $y }} q10 4 14 18 q-10 -6 -14 -18 Z" fill="#f6c445" />
                    @endforeach
                </g>
            </svg>
        @endforeach

        <svg class="thoranam" viewBox="0 0 400 100" preserveAspectRatio="xMidYMin slice" aria-hidden="true">
            <path d="M0 10 Q200 50 400 10" fill="none" stroke="#7a4a1e" stroke-width="3" />
            @for ($x = 8; $x < 400; $x += 16)
                @php $y = $ropeY($x); @endphp
                @if (intdiv($x - 8, 16) % 3 === 2)
                    <circle cx="{{ $x }}" cy="{{ $y + 8 }}" r="7" fill="#f59e0b" />
                    <circle cx="{{ $x }}" cy="{{ $y + 8 }}" r="3" fill="#c2410c" />
                @else
                    <path d="M{{ $x }} {{ $y }} c6 10 6 30 0 42 c-6 -12 -6 -32 0 -42 Z"
                          fill="{{ intdiv($x - 8, 16) % 2 ? '#2f7d32' : '#43a047' }}" />
                @endif
            @endfor
        </svg>

        <div class="scene__content couple__names">
            <x-bi as="p" class="couple__role" :text="$page->label('bride')" />
            <h1 class="couple__heading" style="--longest-ta: {{ $longestWord('ta') }}; --longest-en: {{ $longestWord('en') }}">
                <span class="couple__name"><x-bi :text="$page->text($page->get('bride.name'))" /></span>
                <span class="couple__and">@include('invitations.templates.tamil-hindu-wedding.ornament')</span>
                <span class="couple__name"><x-bi :text="$page->text($page->get('groom.name'))" /></span>
            </h1>
            <x-bi as="p" class="couple__role" :text="$page->label('groom')" />
        </div>
    </div>
</section>
