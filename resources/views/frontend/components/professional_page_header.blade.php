<!-- Professional Page Header Component -->
<!-- Usage: @include('frontend.components.professional_page_header', compact('websitePage', 'breadcrumbs')) -->

@php
    $pageTitle = $websitePage?->title ?? 'Page';
    $pageSubtitle = $websitePage?->subtitle ?? '';
    $pageImage = $websitePage?->page_image ? asset('assets/uploads/website/' . $websitePage->page_image) : null;
    $overlayOpacity = $websitePage?->overlay_opacity ?? 40;
    $ctaText = $websitePage?->cta_button_text;
    $ctaLink = $websitePage?->cta_button_link;
    $isHomePage = $websitePage?->page_key === 'home' || $websitePage?->slug === 'home';
    
    // Default background if no image
    $defaultBg = 'linear-gradient(135deg, #1a3a6b 0%, #2d5a9e 100%)';
    $bgStyle = $pageImage 
        ? "background-image: url('$pageImage'); background-size: cover; background-position: center;"
        : "background: $defaultBg;";
@endphp

<header class="page-header" style="{{ $bgStyle }}">
    <!-- Overlay -->
    <div class="page-header-overlay" style="opacity: {{ $overlayOpacity / 100 }};"></div>
    
    <!-- Content Container -->
    <div class="page-header-content">
        <div class="container-xl">
            <!-- Breadcrumb -->
            @if(!$isHomePage && isset($breadcrumbs) && count($breadcrumbs) > 0)
            <nav class="breadcrumb-nav" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    @foreach($breadcrumbs as $crumb)
                        @if($loop->last)
                            <li class="breadcrumb-item active" aria-current="page">
                                {{ $crumb['title'] }}
                            </li>
                        @else
                            <li class="breadcrumb-item">
                                <a href="{{ $crumb['url'] }}">{{ $crumb['title'] }}</a>
                            </li>
                        @endif
                    @endforeach
                </ol>
            </nav>
            @endif
            
            <!-- Page Title & Subtitle -->
            <div class="page-header-text">
                <h1 class="page-header-title">{{ $pageTitle }}</h1>
                
                @if($pageSubtitle)
                <p class="page-header-subtitle">{{ $pageSubtitle }}</p>
                @endif
            </div>
            
            <!-- CTA Button -->
            @if($ctaText && $ctaLink && !$isHomePage)
            <div class="page-header-cta">
                <a href="{{ $ctaLink }}" class="btn-cta">
                    {{ $ctaText }}
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            @endif
        </div>
    </div>
</header>

<style>
.page-header {
    position: relative;
    padding: 100px 0 60px;
    color: white;
    text-align: center;
    overflow: hidden;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(ellipse at center, transparent 0%, rgba(0, 0, 0, 0.3) 100%);
    z-index: 1;
}

.page-header-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(26, 58, 107, 0.6);
    z-index: 0;
}

.page-header-content {
    position: relative;
    z-index: 2;
}

.breadcrumb-nav {
    margin-bottom: 30px;
    text-align: center;
}

.breadcrumb {
    display: inline-flex;
    list-style: none;
    padding: 0;
    margin: 0;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 25px;
    padding: 8px 20px;
    backdrop-filter: blur(10px);
}

.breadcrumb-item {
    font-size: 14px;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.9);
}

.breadcrumb-item:not(:last-child)::after {
    content: '/';
    margin: 0 8px;
    color: rgba(255, 255, 255, 0.7);
}

.breadcrumb-item a {
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    transition: color 0.3s ease;
}

.breadcrumb-item a:hover {
    color: #f39c12;
}

.breadcrumb-item.active {
    color: #f39c12;
    font-weight: 600;
}

.page-header-text {
    margin-bottom: 30px;
}

.page-header-title {
    font-size: 56px;
    font-weight: 800;
    margin: 0 0 16px;
    text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
    letter-spacing: -1px;
    animation: fadeInUp 0.6s ease;
}

.page-header-subtitle {
    font-size: 20px;
    font-weight: 400;
    color: rgba(255, 255, 255, 0.95);
    margin: 0;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
    text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.2);
    animation: fadeInUp 0.8s ease 0.1s both;
}

.page-header-cta {
    animation: fadeInUp 1s ease 0.2s both;
}

.btn-cta {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 32px;
    background: linear-gradient(135deg, #f39c12, #e67e22);
    color: white;
    font-weight: 600;
    font-size: 16px;
    border-radius: 30px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 8px 20px rgba(243, 156, 18, 0.3);
    cursor: pointer;
    border: none;
}

.btn-cta:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(243, 156, 18, 0.4);
    background: linear-gradient(135deg, #e67e22, #d35400);
}

.btn-cta i {
    font-size: 14px;
    transition: transform 0.3s ease;
}

.btn-cta:hover i {
    transform: translateX(3px);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        padding: 60px 0 40px;
    }
    
    .page-header-title {
        font-size: 36px;
        margin-bottom: 12px;
    }
    
    .page-header-subtitle {
        font-size: 16px;
    }
    
    .breadcrumb {
        font-size: 12px;
        padding: 6px 16px;
    }
    
    .breadcrumb-item:not(:last-child)::after {
        margin: 0 4px;
    }
    
    .btn-cta {
        padding: 12px 24px;
        font-size: 14px;
    }
}

@media (max-width: 576px) {
    .page-header {
        padding: 50px 0 30px;
    }
    
    .page-header-title {
        font-size: 28px;
        margin-bottom: 10px;
    }
    
    .page-header-subtitle {
        font-size: 14px;
    }
    
    .breadcrumb {
        display: none;
    }
}
</style>
