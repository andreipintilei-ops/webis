{{--
    A dark hero's backdrop: the animated gradient, or an image under a
    darkening wash. Expects `$backdrop` ("gradient" | "image"), `$image`,
    `$isFirst` and `$fixed`.

    `$fixed`: the opening hero's backdrop is fixed to the screen, behind the
    page (pushed to the layout's `backdrop` stack, outside the smooth-scroll
    wrapper), so the hero and the blocks set on it scroll over it
    (blocks.blade.php). It rests as a card that opens out as the page scrolls,
    and the intro grows it (js/public/hero-intro.ts). Otherwise it fills its
    own section.

    Base colours: the gradient's own (shown until its first frame, and to
    browsers without WebGL2), or a deep blue under an image.
--}}
<div @class([
    'overflow-hidden',
    'fixed inset-0 z-0' => $fixed,
    'absolute inset-0 -z-10' => ! $fixed,
    'bg-[#062334]' => $backdrop === 'gradient',
    'bg-[#0b1d5c]' => $backdrop === 'image',
]) @if ($fixed) data-intro-backdrop @endif>
    <div class="absolute inset-0" data-intro-media>
        @if ($backdrop === 'gradient')
            {{-- Animated WebGL2 gradient (js/public/soffit.ts). --}}
            <canvas data-soffit class="absolute inset-0 block size-full" aria-hidden="true"></canvas>
        @else
            <x-picture :asset="$image" :lcp="$isFirst" sizes="100vw" class="absolute inset-0 size-full object-cover" />
            {{-- Keeps the text readable whatever the image does behind it. --}}
            <div class="absolute inset-0 bg-gradient-to-b from-black/30 via-black/10 to-black/40" aria-hidden="true"></div>
        @endif
    </div>
</div>
