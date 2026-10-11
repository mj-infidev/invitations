{{-- 1 · Opening: the temple at sunset, the Pillaiyar suzhi (Tamil view only) and the invitation's first words. --}}
<section class="scene scene--opening" data-scene="opening" style="--length: 130">
    <div class="scene__stage">
        <x-scene-art :page="$page" scene="opening" first />

        <div class="scene__content">
            <p class="opening__suzhi rise" lang="ta" aria-hidden="true">உ</p>
            <x-bi as="h1" class="headline rise" :text="$page->label('wedding_invitation')" />
            <x-bi as="p" class="lede rise" :text="$page->label('opening_line')" />
        </div>

        <div class="scroll-hint">
            <x-bi :text="$page->label('scroll_hint')" />
            <svg class="scroll-hint__arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6" /></svg>
        </div>
    </div>
</section>
