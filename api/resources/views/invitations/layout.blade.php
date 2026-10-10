<!DOCTYPE html>
<html lang="{{ $page->lang() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <title>{{ $page->title() }}</title>
    <meta name="description" content="{{ $page->previewDescription() }}">

    {{-- The WhatsApp card, read without running JavaScript. --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $page->title() }}">
    <meta property="og:description" content="{{ $page->previewDescription() }}">
    <meta property="og:url" content="{{ route('invitation.show', $page->invitation->slug) }}">
    @if ($image = $page->previewImage())
        <meta property="og:image" content="{{ $image }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    @endif

    @yield('head')

    {{-- Before first paint, so the page never lays out twice: .motion turns on the
         pinned, scroll-played layout; without it (reduce motion, or no JS) every
         scene is simply shown in its finished state. --}}
    <script>
        if (window.matchMedia('(prefers-reduced-motion: no-preference)').matches) document.documentElement.classList.add('motion')
    </script>

    @vite($page->viewerEntry())
</head>
<body>
    <main class="invitation">
        @yield('scenes')
    </main>

    @if ($page->music())
        <button type="button" class="music-toggle" data-music
                data-label-play="{{ implode(' / ', $page->label('music_play')) }}"
                data-label-pause="{{ implode(' / ', $page->label('music_pause')) }}"
                aria-pressed="false" aria-label="{{ implode(' / ', $page->label('music_play')) }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path class="music-toggle__note" d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
        </button>
    @endif

    @if ($page->watermark)
        {{-- Unpaid draft. Drawn over every scene; clicks pass through to the page. --}}
        <div class="watermark" aria-hidden="true">
            <div class="watermark__inner">
                @for ($i = 0; $i < 24; $i++)
                    <span>{{ implode(' · ', $page->label('preview')) }}</span>
                @endfor
            </div>
        </div>
    @endif

    <script type="application/json" id="invitation-data">{!! $page->viewerJson() !!}</script>
</body>
</html>
