{{-- 4 · Muhurtham: date and time, the Tamil calendar date if the family gave one, and a countdown. --}}
<section class="scene scene--muhurtham" data-scene="muhurtham" style="--length: 150">
    <div class="scene__stage">
        <x-scene-art :page="$page" scene="muhurtham" />

        <div class="scene__content">
            <x-bi as="p" class="eyebrow rise" :text="$page->label('muhurtham')" />
            <x-bi as="h2" class="headline headline--medium rise" :text="$page->date($page->get('muhurtham.date'))" />
            <div class="lede rise">
                <x-bi as="p" :text="$page->time($page->get('muhurtham.time'))" />
                @if (in_array('ta', $page->languages, true) && filled($page->get('muhurtham.tamil_date')))
                    <p lang="ta">{{ $page->get('muhurtham.tamil_date') }}</p>
                @endif
            </div>

            @if ($page->muhurthamAt())
                {{-- Filled and ticked by the viewer script; .is-done swaps the numbers for the message. --}}
                <div class="countdown rise" data-countdown>
                    <div class="countdown__units">
                        @foreach (['days', 'hours', 'minutes', 'seconds'] as $unit)
                            <div class="countdown__unit">
                                <span class="countdown__value" data-unit="{{ $unit }}">–</span>
                                <x-bi class="countdown__label" :text="$page->label($unit)" />
                            </div>
                        @endforeach
                    </div>
                    <div class="countdown__done">
                        <x-bi as="p" :text="$page->label('countdown_done')" />
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
