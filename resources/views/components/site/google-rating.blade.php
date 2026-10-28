{{--
    The Google Business Profile's rating (CompanySettings: google_rating,
    google_review_count, google_reviews_url), quietly, on two lines: the
    score beside five stars in one colour filled to the rating, and under
    them "Google Reviews" with the number of reviews. A link to the reviews
    when there is one. Nothing without a rating. css/site/google-rating.css.

    `tone`: "dark" (on a dark hero: white) or "light".
--}}
@props(['tone' => 'dark'])

@php
    $company = app(\App\Settings\CompanySettings::class);
    $rating = $company->google_rating;
    $count = $company->google_review_count;
    $url = $company->google_reviews_url;

    // Romanian: 1 recenzie; ending in 01–19 (2, 19, 101…) "recenzii";
    // otherwise (20, 38, 100…) "de recenzii".
    $reviews = match (true) {
        $count === null || $count === 0 => null,
        $count === 1 => '1 recenzie',
        $count % 100 >= 1 && $count % 100 <= 19 => "{$count} recenzii",
        default => "{$count} de recenzii",
    };

    $score = $rating === null ? null : number_format($rating, 1, ',', '');
    $label = $rating === null ? null : "Evaluare Google: {$score} din 5".($reviews ? ", din {$reviews}" : '');
@endphp

@if ($rating !== null)
    <{{ $url ? 'a' : 'p' }}
        @if ($url) href="{{ $url }}" target="_blank" rel="noopener" @endif
        {{ $attributes->class(['google-rating', 'google-rating--'.$tone]) }}
    >
        <span class="sr-only">{{ $label }}</span>
        <span class="google-rating__top" aria-hidden="true">
            {{-- Five stars, the filled row clipped to the rating. --}}
            <span class="google-rating__stars" style="--fill: {{ round($rating / 5 * 100, 1) }}%">
                @foreach (['empty', 'full'] as $row)
                    <span class="google-rating__row google-rating__row--{{ $row }}">
                        @foreach (range(1, 5) as $star)
                            <svg viewBox="0 0 24 24"><path d="M12 2.5l2.94 6.08 6.56.82-4.85 4.53 1.24 6.57L12 17.3l-5.89 3.2 1.24-6.57L2.5 9.4l6.56-.82L12 2.5Z" /></svg>
                        @endforeach
                    </span>
                @endforeach
            </span>
            <span class="google-rating__score">{{ $score }}</span>
        </span>
        <span class="google-rating__meta" aria-hidden="true">Google Reviews{{ $reviews ? ' · '.$reviews : '' }}</span>
    </{{ $url ? 'a' : 'p' }}>
@endif
