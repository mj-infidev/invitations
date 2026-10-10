{{--
  7 · Welcome: a cartoon couple exchange garlands and fold their hands.
  Each person is built from parts (face, hair, outfit, jewellery, arms) and the
  garlands are separate, all coloured from `data.avatar` (App\Support\Avatar)
  through CSS variables, so a later customizer only changes data. Drawn in the
  finished state: garlands on the other's neck, hands folded.
--}}
@php $avatar = $page->get('avatar'); @endphp
<section class="scene scene--welcome" data-scene="welcome" data-length="170">
    <div class="scene__stage">
        <div class="welcome__halo" aria-hidden="true"></div>

        <svg class="couple-art" viewBox="0 0 400 420" aria-hidden="true"
             style="{{ \App\Support\Avatar::cssVars($avatar) }}"
             data-bride-jewellery="{{ $avatar['bride']['jewellery'] }}"
             data-groom-jewellery="{{ $avatar['groom']['jewellery'] }}">

            {{-- Bride --}}
            <g class="person person--bride">
                <g class="part part--hair part--hair-back">
                    <ellipse cx="130" cy="100" rx="42" ry="44" />
                    <path class="braid" d="M96 116 Q86 200 98 268" />
                    <path class="jasmine" d="M97 122 Q89 192 99 252" />
                </g>
                <g class="part part--outfit">
                    <path class="outfit" d="M104 158 Q130 150 156 158 L172 250 L182 410 H78 L88 250 Z" />
                    <path class="zari" d="M78 394 H182 V410 H78 Z" />
                    <path class="outfit outfit--edged" d="M148 157 L160 163 L100 290 L86 282 Z" />
                </g>
                <g class="part part--face">
                    <rect class="skin" x="122" y="132" width="16" height="28" />
                    <circle class="skin" cx="94" cy="108" r="6" />
                    <circle class="skin" cx="166" cy="108" r="6" />
                    <circle class="skin" cx="130" cy="105" r="36" />
                    <ellipse class="eye" cx="117" cy="106" rx="3" ry="3.6" />
                    <ellipse class="eye" cx="143" cy="106" rx="3" ry="3.6" />
                    <path class="smile" d="M120 120 Q130 128 140 120" />
                    <circle class="kumkum" cx="130" cy="95" r="2.6" />
                </g>
                <g class="part part--hair part--hair-front">
                    <path d="M94 106 Q90 62 130 62 Q170 62 166 106 Q160 78 130 74 Q100 78 94 106 Z" />
                    <path class="parting" d="M130 64 V75" />
                </g>
                <g class="part part--jewellery">
                    <g class="jewel jewel--temple_gold">
                        <path class="gold-line" d="M130 66 V82" />
                        <circle class="gold" cx="130" cy="85" r="3.5" />
                        <path class="gold" d="M90 114 h8 l-1 6 h-6 Z M162 114 h8 l-1 6 h-6 Z" />
                        <circle class="gold" cx="94" cy="124" r="4" />
                        <circle class="gold" cx="166" cy="124" r="4" />
                        <path class="gold-line gold-line--thick" d="M112 158 Q130 190 148 158" />
                        <circle class="pendant" cx="130" cy="180" r="5" />
                    </g>
                    <g class="jewel jewel--simple">
                        <circle class="gold" cx="94" cy="116" r="2.5" />
                        <circle class="gold" cx="166" cy="116" r="2.5" />
                        <path class="gold-line" d="M116 158 Q130 176 144 158" />
                    </g>
                </g>
                <g class="arms arms--down">
                    <path class="arm" d="M106 166 L122 250 M154 166 L138 250" />
                    <path class="sleeve" d="M106 166 L110 184 M154 166 L150 184" />
                </g>
                <g class="arms arms--folded">
                    <path class="arm" d="M106 166 L114 214 L127 196 M154 166 L146 214 L133 196" />
                    <path class="sleeve" d="M106 166 L110 184 M154 166 L150 184" />
                    <path class="skin" d="M125 206 L130 182 L135 206 Z" />
                </g>
            </g>

            {{-- Groom --}}
            <g class="person person--groom">
                <g class="part part--outfit">
                    <path class="shirt" d="M242 160 Q270 150 298 160 L306 262 H234 Z" />
                    <path class="outfit" d="M234 258 H306 L314 410 H226 Z" />
                    <path class="zari" d="M226 396 H314 V410 H226 Z" />
                    <path class="gold-line gold-line--thick" d="M284 258 L290 410" />
                    <path class="outfit outfit--edged" d="M248 158 L260 156 L302 252 L290 260 Z" />
                </g>
                <g class="part part--face">
                    <rect class="skin" x="262" y="132" width="16" height="28" />
                    <circle class="skin" cx="234" cy="108" r="6" />
                    <circle class="skin" cx="306" cy="108" r="6" />
                    <circle class="skin" cx="270" cy="105" r="35" />
                    <ellipse class="eye" cx="257" cy="104" rx="3" ry="3.6" />
                    <ellipse class="eye" cx="283" cy="104" rx="3" ry="3.6" />
                    <path class="mustache" d="M257 117 Q270 110 283 117 Q270 121 257 117 Z" />
                    <path class="smile" d="M263 124 Q270 129 277 124" />
                    <rect class="kumkum" x="268" y="80" width="4" height="12" rx="2" />
                </g>
                <g class="part part--hair">
                    <path d="M235 102 Q230 64 270 64 Q310 64 305 102 Q298 78 270 78 Q242 78 235 102 Z" />
                </g>
                <g class="part part--jewellery">
                    <g class="jewel jewel--chain">
                        <path class="gold-line" d="M256 158 Q270 184 284 158" />
                    </g>
                </g>
                <g class="arms arms--down">
                    <path class="arm" d="M246 166 L262 250 M294 166 L278 250" />
                    <path class="sleeve sleeve--shirt" d="M246 166 L251 192 M294 166 L289 192" />
                </g>
                <g class="arms arms--folded">
                    <path class="arm" d="M246 166 L254 214 L267 196 M294 166 L286 214 L273 196" />
                    <path class="sleeve sleeve--shirt" d="M246 166 L251 192 M294 166 L289 192" />
                    <path class="skin" d="M265 206 L270 182 L275 206 Z" />
                </g>
            </g>

            {{-- Garlands, last so they pass in front of both. Each sits on the other's neck. --}}
            @foreach (['bride' => 270, 'groom' => 130] as $from => $neckX)
                <g transform="translate({{ $neckX }} 152)">
                    <g class="part part--garland garland--from-{{ $from }}">
                        <g class="garland__lift">
                            <path class="garland__flowers" d="M-30 0 C-40 84 40 84 30 0" />
                            <path class="garland__roses" d="M-30 0 C-40 84 40 84 30 0" />
                            <circle class="garland__pendant" cx="0" cy="66" r="6" />
                        </g>
                    </g>
                </g>
            @endforeach
        </svg>

        <div class="scene__content welcome">
            @php
                $bride = $page->text($page->get('bride.name'));
                $groom = $page->text($page->get('groom.name'));
            @endphp
            {{-- One "bride ✿ groom" line per language, from the names typed in it. --}}
            @foreach ($page->languages as $lang)
                @if (isset($bride[$lang], $groom[$lang]))
                    <p class="welcome__names bi" lang="{{ $lang }}">
                        {{ $bride[$lang] }}
                        @include('invitations.templates.tamil-hindu-wedding.ornament')
                        {{ $groom[$lang] }}
                    </p>
                @endif
            @endforeach
            <x-bi as="p" class="welcome__line" :text="$page->label('welcome')" />
            <a class="brand" href="{{ config('invitations.brand.url') }}" target="_blank" rel="noopener">
                <x-bi :text="$page->label('made_with', ['brand' => config('invitations.brand.name')])" />
            </a>
        </div>
    </div>
</section>
