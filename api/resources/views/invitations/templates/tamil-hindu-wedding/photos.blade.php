{{-- 6 · Photos: the couple's photos arrive one by one in frames. Skipped when there are none to show. --}}
@php
    $photos = $page->photos();
    $placeholders = $page->placeholderPhotoCount();
    $count = count($photos) ?: $placeholders;
    // Each frame's own tilt, so the settled photos look laid on a table rather than gridded.
    $tilts = [-4, 3, 2, -3, -2, 4];
@endphp
@if ($count > 0)
    <section class="scene scene--photos" data-scene="photos" data-length="{{ 60 + 25 * $count }}">
        <div class="scene__stage">
            <div class="scene__content photos" style="--rows: {{ (int) ceil($count / 2) }}">
                <x-bi as="h2" class="photos__heading" :text="$page->label('photos')" />
                <div class="photos__grid photos__grid--{{ $count === 1 ? 'one' : 'many' }}">
                    @foreach ($photos as $i => $photo)
                        <figure class="photo">
                            <div class="photo__frame" style="--tilt: {{ $tilts[$i % 6] }}deg">
                                <img src="{{ $photo['url'] }}" alt="" loading="lazy" decoding="async"
                                     @if ($photo['width']) width="{{ $photo['width'] }}" height="{{ $photo['height'] }}" @endif>
                            </div>
                        </figure>
                    @endforeach
                    @for ($i = 0; $i < $placeholders; $i++)
                        <figure class="photo">
                            <div class="photo__frame photo__frame--empty" style="--tilt: {{ $tilts[$i % 6] }}deg">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h3l2-3h6l2 3h3v13H4z" /><circle cx="12" cy="13" r="4" /></svg>
                                <span>{{ implode(' / ', $page->label('photo')) }} {{ $i + 1 }}</span>
                            </div>
                        </figure>
                    @endfor
                </div>
            </div>
        </div>
    </section>
@endif
