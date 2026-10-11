{{-- 2 · The couple: the decorated entrance, and the names. --}}
@php
    // Names wrap between words, never inside one: the CSS sizes each language's
    // names so its longest word fits. Counted in characters; Tamil letters run
    // about a quarter wider than Latin ones at the same size.
    $longestWord = fn (string $lang) => collect([$page->get("bride.name.{$lang}"), $page->get("groom.name.{$lang}")])
        ->flatMap(fn ($text) => preg_split('/\s+/u', (string) $text))
        ->map(fn ($word) => mb_strlen($word) + 0.25 * preg_match_all('/[\x{0B80}-\x{0BFF}]/u', $word))
        ->max() ?: 1;
@endphp
<section class="scene scene--couple" data-scene="couple" style="--length: 150">
    <div class="scene__stage">
        <x-scene-art :page="$page" scene="couple" />

        <div class="scene__content">
            <h1 class="couple__names" style="--longest-ta: {{ $longestWord('ta') }}; --longest-en: {{ $longestWord('en') }}">
                <span class="couple__name rise"><x-bi :text="$page->text($page->get('bride.name'))" /></span>
                <span class="couple__and rise"><x-bi :text="$page->label('and')" /></span>
                <span class="couple__name rise"><x-bi :text="$page->text($page->get('groom.name'))" /></span>
            </h1>
            <x-bi as="p" class="lede rise" :text="$page->label('getting_married')" />
        </div>
    </div>
</section>
