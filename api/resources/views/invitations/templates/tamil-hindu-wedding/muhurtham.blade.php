{{-- 4 · Muhurtham: date and time, the Tamil calendar date if the family gave one, and a countdown. --}}
<section class="scene scene--muhurtham" data-scene="muhurtham" data-length="140">
    <div class="scene__stage">
        <svg class="layer muhurtham__frame" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
            <rect x="3" y="2" width="94" height="96" rx="2" vector-effect="non-scaling-stroke" />
            <rect x="5" y="3.5" width="90" height="93" rx="1.5" vector-effect="non-scaling-stroke" />
        </svg>
        @foreach (['left', 'right'] as $side)
            <svg class="bell bell--{{ $side }}" viewBox="0 0 40 120" aria-hidden="true">
                <path d="M20 0 V64" stroke="#e0a93b" stroke-width="2" />
                <path d="M6 100 Q6 66 20 64 Q34 66 34 100 Z" fill="#e0a93b" />
                <rect x="3" y="98" width="34" height="5" rx="2" fill="#c8952c" />
                <circle cx="20" cy="108" r="4" fill="#c8952c" />
            </svg>
        @endforeach

        <div class="scene__content muhurtham">
            <div class="muhurtham__line">
                <x-bi as="p" class="eyebrow" :text="$page->label('muhurtham')" />
            </div>
            <div class="muhurtham__line muhurtham__date">
                <x-bi as="p" :text="$page->date($page->get('muhurtham.date'))" />
            </div>
            <div class="muhurtham__line muhurtham__time">
                <x-bi as="p" :text="$page->time($page->get('muhurtham.time'))" />
            </div>
            @if (in_array('ta', $page->languages, true) && filled($page->get('muhurtham.tamil_date')))
                <p class="muhurtham__line muhurtham__tamil" lang="ta">{{ $page->get('muhurtham.tamil_date') }}</p>
            @endif

            @if ($page->muhurthamAt())
                {{-- Filled and ticked by the viewer script; .is-done swaps the numbers for the message. --}}
                <div class="muhurtham__line countdown" data-countdown>
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
