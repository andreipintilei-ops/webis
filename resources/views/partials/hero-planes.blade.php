{{--
    The planes: a decorative graphic at the right of an internal page's hero
    (components/site/page-hero, the contact page) — three glass planes (far
    blue, tall green, red in front) over a light column and a glowing beam.
    Inline, so its groups can move: the planes float on slow, offset waves;
    the column and the beam stay still (css/site/hero-planes.css), and they
    pause while the hero is off screen (js/public/hero-planes.ts). Not on the
    home page, which has its own hero.

    In the hero, aligned to the content column's right edge; its foot runs
    under the section that rises over the hero. Hidden below 768px.

    `variant`: only 'default' for now.
--}}
@php
    $variant = $variant ?? 'default';
@endphp

<div class="hero-planes hero-planes--{{ $variant }}" aria-hidden="true" data-hero-planes>
    <div class="hero-planes__frame">
<svg xmlns="http://www.w3.org/2000/svg" viewBox="-150 100 770 980" fill="none" class="webis-planes hero-planes__svg" aria-hidden="true" focusable="false">
<g>
<g class="plane plane--far"><path d="M-307 562.918L166.256 319.417" stroke="url(#paint0_linear_107_533)" stroke-width="2.4"/>
<g filter="url(#filter0_f_107_533)">
<path d="M120.364 343.767L349.822 222.016V673.21L120.364 802.122V343.767Z" fill="url(#paint1_linear_107_533)"/>
</g>
<path d="M120.364 343.767L349.822 222.016" stroke="#5C95FF" stroke-opacity="0.55" stroke-width="2.4" stroke-linecap="round"/>
<path d="M350.682 222.016H348.961V594.43H350.682V222.016Z" fill="url(#paint2_linear_107_533)"/></g>
<g class="plane plane--green"><path d="M410.055 211.989L589.318 118.886V615.915L410.055 709.019V211.989Z" fill="url(#paint4_linear_107_533)"/>
<g filter="url(#filter2_f_107_533)">
<path d="M410.055 211.989L589.318 118.886V214.854L410.055 307.958V211.989Z" fill="url(#paint5_linear_107_533)"/>
</g>
<g filter="url(#filter3_f_107_533)">
<path d="M410.055 211.989L589.318 118.886" stroke="#B4FFAE" stroke-opacity="0.75" stroke-width="14.4" stroke-linecap="round"/>
</g>
<path d="M410.055 211.989L589.318 118.886" stroke="#B4FFAE" stroke-width="3.6" stroke-linecap="round"/>
<path d="M410.914 211.99H409.193V541.432H410.914V211.99Z" fill="url(#paint6_linear_107_533)"/>
<path d="M590.178 118.886H588.457V448.329H590.178V118.886Z" fill="url(#paint7_linear_107_533)"/></g>
<g class="plane plane--red"><path d="M321.14 518.515L560.636 386.737V802.122L321.14 933.899V518.515Z" fill="url(#paint8_linear_107_533)"/>
<path d="M321.14 518.514L560.636 386.737V673.209L321.14 802.122V518.514Z" fill="url(#paint9_linear_107_533)"/>
<g filter="url(#filter4_f_107_533)">
<path d="M321.14 518.514L560.636 386.737V472.679L321.14 604.456V518.514Z" fill="url(#paint10_linear_107_533)"/>
</g>
<g filter="url(#filter5_f_107_533)">
<path d="M321.14 518.514L560.636 386.737" stroke="#FFC09C" stroke-opacity="0.75" stroke-width="14.4" stroke-linecap="round"/>
</g>
<path d="M321.14 518.514L560.636 386.737" stroke="#FFC09C" stroke-width="3.6" stroke-linecap="round"/>
<path d="M322 518.515H320.279V804.987H322V518.515Z" fill="url(#paint11_linear_107_533)"/>
<path d="M561.496 386.737H559.775V673.209H561.496V386.737Z" fill="url(#paint12_linear_107_533)"/>
</g>
<g class="plane-column"><path d="M321.14 673.21L560.636 541.433V1080H321.14V673.21Z" fill="url(#paint13_linear_107_533)"/>
<path d="M561.496 541.433H559.775V1080H561.496V541.433Z" fill="url(#paint14_linear_107_533)"/>
<path d="M321.14 673.21L560.636 541.433V673.21L321.14 802.122V673.21Z" fill="#8A5CFF" fill-opacity="0.12"/></g>
<path d="M166.256 773.475L600 532.8V802.122L438.736 916.711L166.256 773.475Z" fill="url(#floorFade)"/>
<g class="plane-beam"><g filter="url(#filter6_f_107_533)">
<path d="M-307 1024.14L324.008 673.21L618 508.488" stroke="url(#paint15_linear_107_533)" stroke-width="24"/>
</g>
<path d="M-307 1024.14L324.008 673.21L618 508.488" stroke="url(#paint16_linear_107_533)" stroke-width="4.8"/></g>
</g><defs>
<filter id="filter0_f_107_533" x="116.364" y="218.016" width="237.457" height="588.106" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
<feFlood flood-opacity="0" result="BackgroundImageFix"/>
<feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape"/>
<feGaussianBlur stdDeviation="2" result="effect1_foregroundBlur_107_533"/>
</filter>
<filter id="filter1_f_107_533" x="567.482" y="111.376" width="149.795" height="98.0972" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
<feFlood flood-opacity="0" result="BackgroundImageFix"/>
<feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape"/>
<feGaussianBlur stdDeviation="7" result="effect1_foregroundBlur_107_533"/>
</filter>
<filter id="filter2_f_107_533" x="390.055" y="98.886" width="219.264" height="229.072" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
<feFlood flood-opacity="0" result="BackgroundImageFix"/>
<feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape"/>
<feGaussianBlur stdDeviation="10" result="effect1_foregroundBlur_107_533"/>
</filter>
<filter id="filter3_f_107_533" x="388.854" y="97.6846" width="221.666" height="135.506" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
<feFlood flood-opacity="0" result="BackgroundImageFix"/>
<feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape"/>
<feGaussianBlur stdDeviation="7" result="effect1_foregroundBlur_107_533"/>
</filter>
<filter id="filter4_f_107_533" x="301.14" y="366.737" width="279.496" height="257.719" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
<feFlood flood-opacity="0" result="BackgroundImageFix"/>
<feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape"/>
<feGaussianBlur stdDeviation="10" result="effect1_foregroundBlur_107_533"/>
</filter>
<filter id="filter5_f_107_533" x="299.938" y="365.536" width="281.898" height="174.18" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
<feFlood flood-opacity="0" result="BackgroundImageFix"/>
<feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape"/>
<feGaussianBlur stdDeviation="7" result="effect1_foregroundBlur_107_533"/>
</filter>
<filter id="filter6_f_107_533" x="-326.832" y="484.02" width="964.697" height="564.606" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
<feFlood flood-opacity="0" result="BackgroundImageFix"/>
<feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape"/>
<feGaussianBlur stdDeviation="7" result="effect1_foregroundBlur_107_533"/>
</filter>
<linearGradient id="paint0_linear_107_533" x1="-150" y1="0" x2="166" y2="0" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#4F8DFF" stop-opacity="0"/><stop offset="0.6" stop-color="#4F8DFF" stop-opacity="0.35"/><stop offset="1" stop-color="#4F8DFF" stop-opacity="0.6"/></linearGradient>
<linearGradient id="paint1_linear_107_533" x1="120.364" y1="222.016" x2="120.364" y2="802.122" gradientUnits="userSpaceOnUse">
<stop stop-color="#3B7DFF" stop-opacity="0.32"/>
<stop offset="0.6" stop-color="#3B7DFF" stop-opacity="0.08"/>
<stop offset="1" stop-color="#3B7DFF" stop-opacity="0"/>
</linearGradient>
<linearGradient id="paint2_linear_107_533" x1="348.961" y1="222.016" x2="348.961" y2="594.43" gradientUnits="userSpaceOnUse">
<stop stop-color="#5C95FF" stop-opacity="0.55"/>
<stop offset="1" stop-color="#5C95FF" stop-opacity="0"/>
</linearGradient>
<linearGradient id="paint3_linear_107_533" x1="587.884" y1="131.777" x2="587.884" y2="658.886" gradientUnits="userSpaceOnUse">
<stop stop-color="#2E74FF" stop-opacity="0.85"/>
<stop offset="0.5" stop-color="#2E74FF" stop-opacity="0.35"/>
<stop offset="1" stop-color="#2E74FF" stop-opacity="0.05"/>
</linearGradient>
<linearGradient id="paint4_linear_107_533" x1="410.055" y1="118.886" x2="410.055" y2="709.019" gradientUnits="userSpaceOnUse">
<stop stop-color="#52F55E"/>
<stop offset="0.3" stop-color="#34D64A" stop-opacity="0.88"/>
<stop offset="0.7" stop-color="#20A83C" stop-opacity="0.35"/>
<stop offset="1" stop-color="#20A83C" stop-opacity="0"/>
</linearGradient>
<linearGradient id="paint5_linear_107_533" x1="410.055" y1="118.886" x2="410.055" y2="307.958" gradientUnits="userSpaceOnUse">
<stop stop-color="#8CFF95" stop-opacity="0.4"/>
<stop offset="1" stop-color="#8CFF95" stop-opacity="0"/>
</linearGradient>
<linearGradient id="paint6_linear_107_533" x1="409.193" y1="211.99" x2="409.193" y2="541.432" gradientUnits="userSpaceOnUse">
<stop stop-color="#B9FFC2" stop-opacity="0.9"/>
<stop offset="1" stop-color="#B9FFC2" stop-opacity="0"/>
</linearGradient>
<linearGradient id="paint7_linear_107_533" x1="588.457" y1="118.886" x2="588.457" y2="448.329" gradientUnits="userSpaceOnUse">
<stop stop-color="#B9FFC2" stop-opacity="0.9"/>
<stop offset="1" stop-color="#B9FFC2" stop-opacity="0"/>
</linearGradient>
<linearGradient id="paint8_linear_107_533" x1="321.14" y1="386.737" x2="321.14" y2="933.899" gradientUnits="userSpaceOnUse">
<stop stop-color="#FF4527"/>
<stop offset="0.25" stop-color="#FF4A2C" stop-opacity="0.92"/>
<stop offset="0.55" stop-color="#E2364A" stop-opacity="0.5"/>
<stop offset="1" stop-color="#A8307A" stop-opacity="0"/>
</linearGradient>
<linearGradient id="paint9_linear_107_533" x1="321.14" y1="-5.73782e-05" x2="560.636" y2="-5.73782e-05" gradientUnits="userSpaceOnUse">
<stop stop-color="#FF8A2A" stop-opacity="0.6"/>
<stop offset="0.55" stop-color="#FF8A2A" stop-opacity="0.2"/>
<stop offset="1" stop-color="#FF8A2A" stop-opacity="0"/>
</linearGradient>
<linearGradient id="paint10_linear_107_533" x1="321.14" y1="386.737" x2="321.14" y2="604.456" gradientUnits="userSpaceOnUse">
<stop stop-color="#FFB088" stop-opacity="0.4"/>
<stop offset="1" stop-color="#FFB088" stop-opacity="0"/>
</linearGradient>
<linearGradient id="paint11_linear_107_533" x1="320.279" y1="518.515" x2="320.279" y2="804.987" gradientUnits="userSpaceOnUse">
<stop stop-color="#FFB89C" stop-opacity="0.85"/>
<stop offset="1" stop-color="#FFB89C" stop-opacity="0"/>
</linearGradient>
<linearGradient id="paint12_linear_107_533" x1="559.775" y1="386.737" x2="559.775" y2="673.209" gradientUnits="userSpaceOnUse">
<stop stop-color="#FFB89C" stop-opacity="0.85"/>
<stop offset="1" stop-color="#FFB89C" stop-opacity="0"/>
</linearGradient>
<linearGradient id="paint13_linear_107_533" x1="321.14" y1="541.433" x2="321.14" y2="1080" gradientUnits="userSpaceOnUse">
<stop stop-color="#3A78FF" stop-opacity="0.85"/>
<stop offset="0.5" stop-color="#2F6AF5" stop-opacity="0.45"/>
<stop offset="1" stop-color="#2F6AF5" stop-opacity="0"/>
</linearGradient>
<linearGradient id="paint14_linear_107_533" x1="559.775" y1="541.433" x2="559.775" y2="1080" gradientUnits="userSpaceOnUse">
<stop stop-color="#7FB2FF" stop-opacity="0.6"/>
<stop offset="1" stop-color="#7FB2FF" stop-opacity="0.05"/>
</linearGradient>
<linearGradient id="paint15_linear_107_533" x1="-150" y1="0" x2="600" y2="0" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#3FA0FF" stop-opacity="0"/><stop offset="0.35" stop-color="#3FA0FF" stop-opacity="0.25"/><stop offset="0.72" stop-color="#5CC2FF" stop-opacity="0.75"/><stop offset="0.86" stop-color="#5CC2FF" stop-opacity="0.6"/><stop offset="1" stop-color="#5CC2FF" stop-opacity="0"/></linearGradient>
<linearGradient id="paint16_linear_107_533" x1="-150" y1="0" x2="600" y2="0" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#E8F7FF" stop-opacity="0"/><stop offset="0.35" stop-color="#E8F7FF" stop-opacity="0.25"/><stop offset="0.66" stop-color="#F2FBFF" stop-opacity="0.95"/><stop offset="0.8" stop-color="#FFFFFF" stop-opacity="1"/><stop offset="1" stop-color="#FFFFFF" stop-opacity="0"/></linearGradient>
<linearGradient id="floorFade" x1="166" y1="0" x2="600" y2="0" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#3B7BFF" stop-opacity="0.1"/><stop offset="0.75" stop-color="#3B7BFF" stop-opacity="0.08"/><stop offset="1" stop-color="#3B7BFF" stop-opacity="0"/></linearGradient></defs></svg>
    </div>
</div>
