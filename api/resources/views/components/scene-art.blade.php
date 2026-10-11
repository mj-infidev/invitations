{{--
  A scene's full-screen picture with its dark gradient, from InvitationPage::sceneArt():
  <x-scene-art :page="$page" scene="couple" />
  Landscape screens get the desktop picture, upright ones the portrait. Lazy, so
  it loads as the guest gets near; the viewer keeps it unrendered (so unloaded)
  until its scene is one away. The first scene loads at once.
--}}
@props(['page', 'scene', 'first' => false])
@if ($art = $page->sceneArt($scene))
    <picture class="scene__art" aria-hidden="true">
        <source media="(min-aspect-ratio: 1/1)" srcset="{{ $art['desktop'] }}" sizes="100vw">
        <img src="{{ $art['src'] }}" srcset="{{ $art['phone'] }}" sizes="100vw" alt="" decoding="async"
             @if ($first) fetchpriority="high" @else loading="lazy" @endif>
    </picture>
@endif
<div class="scene__shade" aria-hidden="true"></div>
