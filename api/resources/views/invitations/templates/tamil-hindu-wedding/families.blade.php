{{-- 3 · Families: a kolam draws itself line by line around the parents' names and the family's message. --}}
<section class="scene scene--families" data-scene="families" data-length="170">
    <div class="scene__stage">
        @include('invitations.templates.tamil-hindu-wedding.kolam', ['class' => 'kolam--top'])
        @include('invitations.templates.tamil-hindu-wedding.kolam', ['class' => 'kolam--bottom'])

        <div class="scene__content families">
            <div class="families__line">
                <x-bi as="p" class="families__heading" :text="$page->label('with_blessings')" />
            </div>
            @foreach (['bride', 'groom'] as $side)
                @if ($parents = $page->text($page->get("{$side}.parents")))
                    <div class="families__line families__side">
                        <x-bi as="p" class="families__label" :text="$page->label($side.'_parents')" />
                        <x-bi as="p" class="families__names" :text="$parents" />
                    </div>
                @endif
            @endforeach
            @if ($message = $page->text($page->get('message')))
                <div class="families__line families__message">
                    <x-bi as="p" :text="$message" />
                </div>
            @endif
        </div>
    </div>
</section>
