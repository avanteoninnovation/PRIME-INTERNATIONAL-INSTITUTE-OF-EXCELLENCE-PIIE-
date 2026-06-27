{{-- News/Blog Grid Component --}}
<section class="news-section section-padding" id="{{ $section->section_key }}" style="background: #fff;">
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
            <div class="row g-4">
                @foreach($items as $news)
                    @if($news->status == 1)
                        <div class="col-lg-4 col-md-6">
                            <div style="background: #fff; border: 1px solid #e8edf5; border-radius: 10px; overflow: hidden; transition: box-shadow 0.2s; height: 100%;">
                                @if(!empty($news->image))
                                    <img src="{{ asset('assets/uploads/website/'.$news->image) }}" alt="{{ $news->title }}" style="width: 100%; height: 200px; object-fit: cover;">
                                @endif
                                <div style="padding: 20px;">
                                    <h5 style="color: var(--primary-color); font-weight: 700; margin-bottom: 8px;">{{ $news->title }}</h5>
                                    @if(!empty($news->subtitle))
                                        <p style="color: var(--secondary-color); font-size: 12px; font-weight: 600; margin-bottom: 8px;">{{ $news->subtitle }}</p>
                                    @endif
                                    @if(!empty($news->description))
                                        <p style="color: #555; font-size: 14px; margin-bottom: 12px;">{{ substr($news->description, 0, 100) }}{{ strlen($news->description) > 100 ? '...' : '' }}</p>
                                    @endif
                                    @if(!empty($news->link))
                                        <a href="{{ $news->link }}" target="_blank" style="color: var(--secondary-color); font-weight: 600; text-decoration: none;">{{ $news->button_text ?: 'Read More' }} →</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <div style="background: var(--light-bg); border-radius: 10px; padding: 50px; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-newspaper" style="font-size: 48px; color: #c8d4e8; margin-bottom: 16px; display: block;"></i>
                <h5 style="color: var(--primary-color);">No News Available</h5>
                <p style="margin: 0; font-size: 14px;">Check back later for latest updates.</p>
            </div>
        @endif
    </div>
</section>
