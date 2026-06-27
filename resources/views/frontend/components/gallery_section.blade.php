{{-- Gallery/Image Grid Component --}}
<section class="gallery-section section-padding" id="{{ $section->section_key }}" style="background: var(--light-bg);">
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

        <div class="row g-3">
            @if($items && $items->count() > 0)
                @foreach($items as $image)
                    @if($image->status == 1 && !empty($image->image))
                        <div class="col-lg-3 col-md-4 col-6">
                            <div style="position: relative; overflow: hidden; border-radius: 10px; aspect-ratio: 1; cursor: pointer;" onclick="openGalleryImage(this)">
                                <img src="{{ asset('assets/uploads/website/'.$image->image) }}" alt="{{ $image->title }}" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;">
                                <div style="position: absolute; inset: 0; background: rgba(0,0,0,0); transition: background 0.3s; display: flex; align-items: center; justify-content: center;">
                                    <i class="fa-solid fa-expand" style="color: #fff; font-size: 24px; opacity: 0; transition: opacity 0.3s;"></i>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    </div>
</section>

<script>
function openGalleryImage(element) {
    const img = element.querySelector('img');
    const modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);display:flex;align-items:center;justify-content:center;z-index:9999;';
    modal.innerHTML = '<img src="' + img.src + '" style="max-width:90%;max-height:90%;border-radius:10px;"><button onclick="this.parentElement.remove()" style="position:absolute;top:20px;right:20px;background:none;border:none;color:#fff;font-size:28px;cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>';
    document.body.appendChild(modal);
}
</script>
