{{-- Generated from the favicon chosen in Setări → Identitate. --}}
@php($favicons = app(\App\Support\Brand::class)->favicons())
@if (isset($favicons['svg']))
    <link rel="icon" href="{{ $favicons['svg'] }}" type="image/svg+xml">
@elseif ($favicons !== [])
    <link rel="icon" href="{{ $favicons['icon32'] }}" sizes="32x32" type="image/png">
    <link rel="icon" href="{{ $favicons['icon96'] }}" sizes="96x96" type="image/png">
    <link rel="apple-touch-icon" href="{{ $favicons['apple'] }}">
@else
    <link rel="icon" href="/favicon.ico" sizes="any">
@endif
