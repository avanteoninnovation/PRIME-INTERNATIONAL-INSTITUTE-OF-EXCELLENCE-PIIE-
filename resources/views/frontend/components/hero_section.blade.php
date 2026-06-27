{{-- Hero/Banner Section Component --}}
<section class="hero-section" id="{{ $section->section_key }}" style="background: linear-gradient(135deg, var(--primary-color) 60%, var(--accent-color) 100%); color: #fff; padding: 80px 0 60px;">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-lg-7">
                @if(!empty($section->subtitle))
                    <span class="hero-badge">{{ $section->subtitle }}</span>
                @endif
                <h1>{{ $section->title ?: 'Welcome' }}</h1>
                @if(!empty($section->content))
                    <p style="font-size: 1rem; color: rgba(255,255,255,0.88); margin-bottom: 32px; max-width: 540px;">{{ $section->content }}</p>
                @endif
                @if($items && $items->count() > 0)
                    <div class="hero-btns">
                        @foreach($items as $item)
                            @if($item->status == 1 && !empty($item->link))
                                <a href="{{ $item->link }}" target="_blank" style="background: var(--secondary-color); color: #fff; border: none; padding: 12px 32px; border-radius: 4px; font-weight: 700; font-size: 15px; text-decoration: none; margin-right: 12px; display: inline-block; transition: background 0.2s;">
                                    {{ $item->button_text ?: 'Learn More' }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
            @if(!empty($section->image))
                <div class="col-lg-5 text-center mt-4 mt-lg-0">
                    <img src="{{ asset('assets/uploads/website/'.$section->image) }}" alt="{{ $section->title }}" style="max-width: 100%; border-radius: 12px;">
                </div>
            @endif
        </div>
    </div>
</section>
