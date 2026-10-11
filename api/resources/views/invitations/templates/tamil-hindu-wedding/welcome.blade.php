{{-- 7 · Welcome: the couple's first photo in a round gold frame, their names and the closing line. --}}
@php
    $photo = $page->photos()[0] ?? null;
    $bride = $page->text($page->get('bride.name'));
    $groom = $page->text($page->get('groom.name'));
    $and = $page->label('and');
@endphp
<section class="scene scene--welcome" data-scene="welcome" style="--length: 130">
    <div class="scene__stage">
        <x-scene-art :page="$page" scene="welcome" />

        <div class="scene__content welcome">
            @if ($photo)
                <figure class="portrait rise">
                    <img src="{{ $photo['url'] }}" alt="" loading="lazy" decoding="async">
                </figure>
            @elseif ($page->placeholderPhotoCount() > 0)
                <figure class="portrait portrait--empty rise">
                    <x-bi :text="$page->label('your_photo')" />
                </figure>
            @endif

            {{-- One "bride & groom" line per language, from the names typed in it. --}}
            <div class="rise">
                @foreach ($page->languages as $lang)
                    @if (isset($bride[$lang], $groom[$lang]))
                        <p class="welcome__names bi" lang="{{ $lang }}">{{ $bride[$lang] }} <span class="welcome__and">{{ $and[$lang] }}</span> {{ $groom[$lang] }}</p>
                    @endif
                @endforeach
            </div>
            <x-bi as="p" class="lede rise" :text="$page->label('welcome')" />
            <a class="brand rise" href="{{ config('invitations.brand.url') }}" target="_blank" rel="noopener">
                <x-bi :text="$page->label('made_with', ['brand' => config('invitations.brand.name')])" />
            </a>
        </div>
    </div>
</section>
