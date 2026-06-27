{{-- Partnerships/Affiliations Component --}}
<section class="partnerships-section section-padding" id="{{ $section->section_key }}" style="background: #fff;">
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

        @if($items && $items->count() > 0)
            <div class="row g-4 align-items-center">
                @foreach($items as $partner)
                    @if($partner->status == 1)
                        <div class="col-lg-3 col-md-4 col-6 text-center">
                            <div style="background: var(--light-bg); border-radius: 10px; padding: 30px; transition: transform 0.2s;">
                                @if(!empty($partner->image))
                                    <img src="{{ asset('assets/uploads/website/'.$partner->image) }}" alt="{{ $partner->title }}" style="max-width: 100%; height: 80px; object-fit: contain; margin-bottom: 12px;">
                                @else
                                    <i class="fa-solid fa-handshake" style="font-size: 48px; color: var(--primary-color); margin-bottom: 12px; display: block;"></i>
                                @endif
                                <h5 style="color: var(--primary-color); font-weight: 700; font-size: 14px; margin: 0;">{{ $partner->title }}</h5>
                                @if(!empty($partner->description))
                                    <p style="color: var(--text-muted); font-size: 13px; margin-top: 8px; margin-bottom: 0;">{{ $partner->description }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <div style="background: var(--light-bg); border-radius: 10px; padding: 50px; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-handshake" style="font-size: 48px; color: #c8d4e8; margin-bottom: 16px; display: block;"></i>
                <h5 style="color: var(--primary-color);">No Partners Listed</h5>
                <p style="margin: 0; font-size: 14px;">Partnership information coming soon.</p>
            </div>
        @endif
    </div>
</section>
