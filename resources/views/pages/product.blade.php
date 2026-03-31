@extends('layouts.app')

@section('title', $seo['title'] ?? ($siteName.' · Produkt'))

@push('meta')
    @if (! empty($seo['metaDescription']))
        <meta name="description" content="{{ $seo['metaDescription'] }}">
    @endif
    @if (! empty($seo['canonical']))
        <link rel="canonical" href="{{ $seo['canonical'] }}">
    @endif
    <meta property="og:type" content="{{ $seo['ogType'] ?? 'website' }}">
    <meta property="og:title" content="{{ $seo['ogTitle'] ?? ($seo['title'] ?? $siteName) }}">
    @if (! empty($seo['ogDescription']) || ! empty($seo['metaDescription']))
        <meta property="og:description" content="{{ $seo['ogDescription'] ?? $seo['metaDescription'] }}">
    @endif
    @if (! empty($seo['ogUrl']))
        <meta property="og:url" content="{{ $seo['ogUrl'] }}">
    @endif
    @if (! empty($seo['ogImage']))
        <meta property="og:image" content="{{ $seo['ogImage'] }}">
    @endif
    @if (is_array($seo['productOg'] ?? null) && filled($seo['productOg']['amount'] ?? null) && filled($seo['productOg']['currency'] ?? null))
        <meta property="product:price:amount" content="{{ $seo['productOg']['amount'] }}">
        <meta property="product:price:currency" content="{{ $seo['productOg']['currency'] }}">
    @endif
@endpush

@section('content')
    <livewire:shop.product-detail :slug="$slug" />
@endsection
