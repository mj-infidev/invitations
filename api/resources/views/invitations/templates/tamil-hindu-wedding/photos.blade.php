{{-- 6 · Photos: the couple's photos, one by one, in thin gold frames. Skipped when there are none to show. --}}
@php
    $photos = $page->photos();
    $placeholders = $page->placeholderPhotoCount();
    $count = count($photos) ?: $placeholders;
@endphp
@if ($count > 0)
    <section class="scene scene--photos" data-scene="photos" style="--length: {{ 90 + 20 * $count }}">
        <div class="scene__stage">
            <x-scene-art :page="$page" scene="photos" />

            <div class="scene__content photos" style="--count: {{ $count }}; --rows: {{ (int) ceil($count / ($count > 4 ? 3 : 2)) }}">
                <x-bi as="h2" class="headline headline--medium rise" :text="$page->label('photos')" />
                <div class="photos__grid photos__grid--{{ $count === 1 ? 'one' : ($count > 4 ? 'three' : 'two') }}">
                    @foreach ($photos as $photo)
                        <figure class="photo">
                            <img src="{{ $photo['url'] }}" alt="" loading="lazy" decoding="async"
                                 @if ($photo['width']) width="{{ $photo['width'] }}" height="{{ $photo['height'] }}" @endif>
                        </figure>
                    @endforeach
                    @for ($i = 0; $i < $placeholders; $i++)
                        <figure class="photo photo--empty">
                            <x-bi :text="$page->label('photo', ['n' => $i + 1])" />
                        </figure>
                    @endfor
                </div>
            </div>
        </div>
    </section>
@endif
