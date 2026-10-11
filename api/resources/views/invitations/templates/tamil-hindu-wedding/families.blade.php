{{-- 3 · Families: the kolam and lamps, the parents' names and the family's message. --}}
<section class="scene scene--families" data-scene="families" style="--length: 150">
    <div class="scene__stage">
        <x-scene-art :page="$page" scene="families" />

        <div class="scene__content families">
            <x-bi as="h2" class="headline headline--medium rise" :text="$page->label('with_blessings')" />
            @foreach (['bride', 'groom'] as $side)
                @if ($parents = $page->text($page->get("{$side}.parents")))
                    <div class="families__side rise">
                        <x-bi as="p" class="eyebrow" :text="$page->label($side.'_parents')" />
                        <x-bi as="p" class="families__names" :text="$parents" />
                    </div>
                @endif
            @endforeach
            @if ($message = $page->text($page->get('message')))
                <x-bi as="p" class="families__message rise" :text="$message" />
            @endif
        </div>
    </div>
</section>
