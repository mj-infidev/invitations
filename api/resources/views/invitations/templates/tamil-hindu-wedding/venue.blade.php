{{-- 5 · Venue: the mandapam, its name and address, then (if there are any) the other events take their place. --}}
@php $events = collect($page->get('events', []))->filter(fn ($event) => filled($event['date'] ?? null)); @endphp
<section class="scene scene--venue" data-scene="venue" style="--length: {{ $events->isEmpty() ? 150 : 210 }}">
    <div class="scene__stage">
        <x-scene-art :page="$page" scene="venue" />

        <div class="scene__content venue__info">
            <x-bi as="p" class="eyebrow rise" :text="$page->label('venue')" />
            <h2 class="headline headline--medium rise"><x-bi :text="$page->text($page->get('venue.name'))" /></h2>
            <div class="lede venue__address rise"><x-bi as="p" :text="$page->text($page->get('venue.address'))" /></div>
            @if ($mapUrl = $page->mapUrl())
                <div class="rise">
                    <a class="button" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12z" /><circle cx="12" cy="10" r="2.5" /></svg>
                        <x-bi :text="$page->label('open_in_maps')" />
                    </a>
                </div>
            @endif
        </div>

        @if ($events->isNotEmpty())
            <div class="scene__content venue__events">
                <x-bi as="p" class="eyebrow event-in" :text="$page->label('other_events')" />
                @foreach ($events as $event)
                    <div class="event event-in">
                        <x-bi as="h3" class="headline headline--small" :text="$page->text($event['name'] ?? null)" />
                        <div class="lede">
                            <x-bi as="p" :text="$page->date($event['date'])" />
                            <x-bi as="p" :text="$page->time($event['time'] ?? null)" />
                            <x-bi as="p" class="event__place" :text="$page->text($event['place'] ?? null)" />
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
