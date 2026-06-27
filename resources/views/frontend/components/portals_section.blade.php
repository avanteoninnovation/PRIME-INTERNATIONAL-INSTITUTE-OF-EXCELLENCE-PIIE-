{{-- Portals/Services Card Grid Component --}}
<section class="portals-section section-padding" id="{{ $section->section_key }}" style="background: var(--light-bg);">
    <div class="container-xl">
        <div class="section-title text-center">
            @if(!empty($section->subtitle))
                <span class="section-badge">{{ $section->subtitle }}</span>
            @endif
            <h2>{{ $section->title }}</h2>
            <div class="divider mx-auto"></div>
            @if(!empty($section->content))
                <p>{{ $section->content }}</p>
            @endif
        </div>

        <div class="row justify-content-center g-4">
            @if($items && $items->count() > 0)
                @foreach($items as $item)
                    @if($item->status == 1)
                        <div class="col-lg-4 col-md-6">
                            <div class="portal-card">
                                <div class="portal-icon">
                                    @if(!empty($item->icon))
                                        <i class="{{ $item->icon }}"></i>
                                    @else
                                        <i class="fa-solid fa-cube"></i>
                                    @endif
                                </div>
                                <h4>{{ $item->title }}</h4>
                                <p>{{ $item->description ?: $item->content }}</p>
                                @if(!empty($item->link))
                                    <a href="{{ $item->link }}" class="portal-link" target="_blank">{{ $item->button_text ?: 'Access' }}</a>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    </div>
</section>

<style>
.portal-card {
    background: #fff;
    border-radius: 10px;
    padding: 32px 24px;
    text-align: center;
    box-shadow: 0 2px 16px rgba(0,0,0,0.07);
    transition: transform 0.2s, box-shadow 0.2s;
    height: 100%;
}
.portal-card:hover { transform: translateY(-4px); box-shadow: 0 8px 30px rgba(0,0,0,0.13); }
.portal-card .portal-icon {
    width: 64px; height: 64px;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 18px;
}
.portal-card .portal-icon i { color: #fff; font-size: 26px; }
.portal-card h4 { color: var(--primary-color); font-weight: 700; font-size: 1.1rem; margin-bottom: 10px; }
.portal-card p { color: var(--text-muted); font-size: 14px; margin-bottom: 18px; }
.portal-card a.portal-link {
    background: var(--secondary-color);
    color: #fff;
    padding: 9px 24px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    display: inline-block;
    transition: background 0.2s;
}
.portal-card a.portal-link:hover { background: #c5621a; }
</style>
