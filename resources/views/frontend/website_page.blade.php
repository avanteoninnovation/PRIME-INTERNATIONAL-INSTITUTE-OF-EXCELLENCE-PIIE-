@extends('frontend.index')
@section('content')

@php
    use App\Helpers\WebsiteRenderingHelper;
    
    $ws = $websiteSettings ?? collect();
    $sections = $websiteSections ?? collect();
    $items = $websiteItems ?? collect();
    $allPages = $allPages ?? collect();
    $currentPage = $websitePage ?? null;

    $setting = function ($key, $default = '') use ($ws) {
        return $ws[$key] ?? $default;
    };
@endphp

<!-- Link Modern CSS -->
<link rel="stylesheet" href="{{ asset('css/website.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Navigation Header -->
<nav>
    <div class="container-xl">
        <div>
            <i class="fas fa-graduation-cap" style="font-size: 20px;"></i>
            <span>{{ $setting('institution_name', 'PIIE') }}</span>
        </div>
        <div style="display:flex; align-items:center; gap:0;">
            @forelse($allPages as $navPage)
                <a href="{{ $navPage->slug ? route('website.page', $navPage->slug) : route('landingPage') }}" 
                   class="@if($currentPage && $currentPage->id === $navPage->id) active @endif"
                   title="{{ $navPage->title }}">
                    {{ $navPage->title }}
                </a>
            @empty
                <span style="color:#bbb; padding:18px 20px; font-size:12px;">No pages available</span>
            @endforelse
            <a href="{{ route('download.brochure') }}" 
               style="padding:18px 20px; color:#fff; background:linear-gradient(135deg, #4a90e2 0%, #357abd 100%); border-radius:4px; font-size:14px; font-weight:600; display:flex; align-items:center; gap:8px; transition:all 0.3s ease; margin-left:10px;"
               onmouseover="this.style.background='linear-gradient(135deg, #357abd 0%, #2a5a9e 100%); transform:translateY(-2px);'"
               onmouseout="this.style.background='linear-gradient(135deg, #4a90e2 0%, #357abd 100%);'"
               title="Download our institutional brochure">
                <i class="fas fa-download"></i> Download Brochure
            </a>
        </div>
    </div>
</nav>

<!-- Page Header -->
<div class="page-header">
    <div class="container-xl">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:20px;">
            <div>
                <h1>{{ $websitePage?->title ?? ($websitePage->page_key ?? 'Website Page') }}</h1>
                @if($websitePage?->subtitle)
                    <div class="subtitle">{{ $websitePage->subtitle }}</div>
                @else
                    <div class="subtitle">{{ $setting('institution_name', 'Prime International Institute of Excellence (PIIE)') }}</div>
                @endif
            </div>
            @if($websitePage?->page_key !== 'home')
            <a href="{{ route('landingPage') }}" class="back-btn">
                <i class="fas fa-arrow-left" style="margin-right: 8px;"></i> Back to Home
            </a>
            @endif
        </div>
    </div>
</div>

<!-- Main Content -->
<main>
    @forelse($sections as $section)
        @if($section->status == 1)
            {!! WebsiteRenderingHelper::renderSection(
                $section,
                $items[$section->section_key] ?? collect(),
                $websitePage ?? null,
                $ws ?? collect(),
                $websiteSeo ?? null
            ) !!}
        @endif
    @empty
        <section class="section-padding">
            <div class="container-xl">
                <div style="background:#fff; border:2px dashed var(--border-color); border-radius:10px; padding:40px; text-align:center; color:var(--text-secondary);">
                    <i class="fas fa-inbox" style="font-size:40px; margin-bottom:16px; opacity:0.5;"></i>
                    <p style="margin:0; font-size:16px;">No active sections are configured for this page yet.</p>
                </div>
            </div>
        </section>
    @endforelse
</main>

@endsection
