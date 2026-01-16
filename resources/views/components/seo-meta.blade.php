@props([
    'title' => null,
    'description' => 'Task Manager is a simple and efficient task management tool that helps you stay organized and productive. Create, manage, and track your tasks with ease.',
    'keywords' => 'task manager, todo list, productivity, task tracking, project management, organization',
    'canonical' => null,
    'ogImage' => null,
    'ogType' => 'website',
])

@php
    $appName = config('app.name', 'Task Manager');
    $appUrl = config('app.url');
    $pageTitle = $title ? "$title - $appName" : $appName;
    $canonicalUrl = $canonical ?? url()->current();
    $ogImageUrl = $ogImage ?? asset('images/og-image.png');
@endphp

{{-- Primary Meta Tags --}}
<meta name="description" content="{{ $description }}">
<meta name="keywords" content="{{ $keywords }}">
<meta name="author" content="{{ $appName }}">
<meta name="robots" content="index, follow">
<link rel="canonical" href="{{ $canonicalUrl }}">

{{-- Open Graph / Facebook --}}
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:site_name" content="{{ $appName }}">
@if(file_exists(public_path('images/og-image.png')))
<meta property="og:image" content="{{ $ogImageUrl }}">
@endif

{{-- Twitter --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="{{ $canonicalUrl }}">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $description }}">
@if(file_exists(public_path('images/og-image.png')))
<meta name="twitter:image" content="{{ $ogImageUrl }}">
@endif

{{-- Additional SEO --}}
<meta name="application-name" content="{{ $appName }}">
<meta name="theme-color" content="#2cbe4e">
