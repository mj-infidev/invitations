{{-- 5 · Venue: the mandapam slides in, then (if there are any) the other events take its place. --}}
@php $events = collect($page->get('events', []))->filter(fn ($event) => filled($event['date'] ?? null)); @endphp
<section class="scene scene--venue" data-scene="venue" data-length="{{ $events->isEmpty() ? 130 : 190 }}">
    <div class="scene__stage">
        <div class="venue__mandapam" aria-hidden="true">
            <svg viewBox="0 0 300 240">
                <path d="M150 14 L156 30 H144 Z" fill="#e0a93b" />
                <circle cx="150" cy="34" r="6" fill="#e0a93b" />
                <path d="M15 86 L150 38 L285 86 Z" fill="#8c1d3a" />
                <path d="M15 86 L150 38 L285 86" fill="none" stroke="#e0a93b" stroke-width="3" />
                <rect x="22" y="84" width="256" height="14" fill="#e9c27a" />
                <rect x="22" y="94" width="256" height="4" fill="#c8952c" />
                <rect x="40" y="98" width="220" height="112" fill="#fff1d0" opacity=".18" />
                @foreach ([45, 105, 183, 243] as $x)
                    <rect x="{{ $x - 3 }}" y="98" width="18" height="8" fill="#c8952c" />
                    <rect x="{{ $x }}" y="104" width="12" height="100" fill="#e9c27a" />
                    <rect x="{{ $x - 3 }}" y="200" width="18" height="10" fill="#c8952c" />
                @endforeach
                <rect x="20" y="208" width="260" height="14" fill="#c8952c" />
                <rect x="40" y="222" width="220" height="10" fill="#a87b22" />
            </svg>
            @foreach (['left', 'right'] as $side)
                <div class="hanging hanging--{{ $side }}" data-lottie="garland">
                    <svg class="lottie-still" viewBox="0 0 24 100">
                        @for ($y = 6; $y <= 76; $y += 10)
                            <circle cx="12" cy="{{ $y }}" r="5.5" fill="#f59e0b" />
                        @endfor
                        <ellipse cx="12" cy="86" rx="4" ry="6" fill="#b91c1c" />
                    </svg>
                </div>
            @endforeach
        </div>

        @if ($events->isNotEmpty())
            <div class="venue__events">
                <x-bi as="p" class="eyebrow" :text="$page->label('other_events')" />
                @foreach ($events as $event)
                    <div class="event">
                        <x-bi as="p" class="event__name" :text="$page->text($event['name'] ?? null)" />
                        <x-bi as="p" class="event__when" :text="$page->date($event['date'])" />
                        <x-bi as="p" class="event__when" :text="$page->time($event['time'] ?? null)" />
                        <x-bi as="p" class="event__place" :text="$page->text($event['place'] ?? null)" />
                    </div>
                @endforeach
            </div>
        @endif

        <div class="scene__content venue__info">
            <x-bi as="p" class="eyebrow" :text="$page->label('venue')" />
            <h2 class="venue__name"><x-bi :text="$page->text($page->get('venue.name'))" /></h2>
            <div class="venue__address"><x-bi as="p" :text="$page->text($page->get('venue.address'))" /></div>
            @if ($mapUrl = $page->mapUrl())
                <a class="button" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12z" /><circle cx="12" cy="10" r="2.5" /></svg>
                    <x-bi :text="$page->label('open_in_maps')" />
                </a>
            @endif
        </div>
    </div>
</section>
