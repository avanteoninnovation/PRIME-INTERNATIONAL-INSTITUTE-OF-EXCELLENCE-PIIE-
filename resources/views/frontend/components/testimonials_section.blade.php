{{-- Testimonials Component --}}
<section class="testimonials-section section-padding" id="{{ $section->section_key }}" style="background: var(--light-bg);">
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

        <div class="row g-4 justify-content-center">
            @if($items && $items->count() > 0)
                @foreach($items as $testimonial)
                    @if($testimonial->status == 1)
                        <div class="col-lg-4 col-md-6">
                            <div style="background: #fff; border-radius: 10px; padding: 28px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); height: 100%;">
                                @if(!empty($testimonial->badge))
                                    <div style="margin-bottom: 12px;">
                                        @for($i = 0; $i < intval($testimonial->badge); $i++)
                                            <i class="fa-solid fa-star" style="color: var(--secondary-color); font-size: 14px; margin-right: 2px;"></i>
                                        @endfor
                                    </div>
                                @endif
                                
                                @if(!empty($testimonial->description))
                                    <p style="color: #555; font-style: italic; margin-bottom: 18px; line-height: 1.8;">{{ $testimonial->description }}</p>
                                @endif

                                <div style="display: flex; align-items: center; gap: 12px;">
                                    @if(!empty($testimonial->image))
                                        <img src="{{ asset('assets/uploads/website/'.$testimonial->image) }}" alt="{{ $testimonial->title }}" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">
                                    @else
                                        <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--primary-color); display: flex; align-items: center; justify-content: center;">
                                            <i class="fa-solid fa-user" style="color: #fff;"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <h5 style="color: var(--primary-color); font-weight: 700; margin: 0; font-size: 14px;">{{ $testimonial->title }}</h5>
                                        @if(!empty($testimonial->subtitle))
                                            <p style="color: var(--text-muted); font-size: 13px; margin: 2px 0 0;">{{ $testimonial->subtitle }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    </div>
</section>
