{{-- Programs/Cards Grid Component - Professional Modern Design --}}
<section class="programs-section section-padding" id="{{ $section->section_key }}">
    <div class="container-xl">
        <!-- Section Title -->
        <div class="section-title">
            @if(!empty($section->subtitle))
                <span class="section-badge">
                    <i class="fas fa-book" style="margin-right: 6px;"></i>
                    {{ $section->subtitle }}
                </span>
            @endif
            <h2>{{ $section->title }}</h2>
            <div class="divider mx-auto"></div>
            @if(!empty($section->content))
                <p>{{ $section->content }}</p>
            @endif
        </div>

        <!-- Programs Grid -->
        <div class="row g-4" style="margin-top: 40px;">
            @if($items && $items->count() > 0)
                @foreach($items as $item)
                    @if($item->status == 1)
                        <div class="col-lg-4" style="grid-column: span 4;">
                            <div class="card-item" style="position: relative;">
                                <!-- Program Badge -->
                                @if(!empty($item->badge))
                                    <span style="position: absolute; top: 14px; right: 14px; background: linear-gradient(135deg, var(--success-color), #2ecc71); color: #fff; font-size: 10px; font-weight: 700; letter-spacing: 0.5px; padding: 4px 12px; border-radius: 20px; text-transform: uppercase; z-index: 10;">
                                        <i class="fas fa-check-circle" style="margin-right: 4px;"></i>
                                        {{ $item->badge }}
                                    </span>
                                @endif

                                <!-- Program Icon -->
                                @if(!empty($item->icon))
                                    <div style="width: 56px; height: 56px; background: linear-gradient(135deg, var(--secondary-light), var(--secondary-color)); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                                        <i class="{{ $item->icon }}" style="color: #fff; font-size: 24px;"></i>
                                    </div>
                                @endif

                                <!-- Program Title -->
                                <h5 class="card-title">{{ $item->title }}</h5>

                                <!-- Program Subtitle -->
                                @if(!empty($item->subtitle))
                                    <p style="font-weight: 600; color: var(--secondary-color); font-size: 14px; margin-bottom: 8px;">
                                        {{ $item->subtitle }}
                                    </p>
                                @endif

                                <!-- Program Description -->
                                @if(!empty($item->description))
                                    <p class="card-description">{{ $item->description }}</p>
                                @endif

                                <!-- Program Content/Duration -->
                                @if(!empty($item->content))
                                    <div style="padding: 12px; background: var(--light-bg); border-radius: var(--radius-sm); margin: 16px 0; border-left: 4px solid var(--secondary-color); font-size: 13px; color: var(--secondary-color); font-weight: 600;">
                                        <i class="fas fa-clock" style="margin-right: 6px;"></i>
                                        {{ $item->content }}
                                    </div>
                                @endif

                                <!-- Program Button -->
                                @if(!empty($item->link))
                                    <a href="{{ $item->link }}" target="_blank" class="card-button">
                                        <i class="fas fa-external-link-alt" style="margin-right: 6px;"></i>
                                        {{ $item->button_text ?: 'Learn More' }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            @else
                <div style="grid-column: 1 / -1; background:var(--light-bg); border:2px dashed var(--border-color); border-radius:var(--radius-lg); padding:40px; text-align:center; color:var(--text-secondary);">
                    <i class="fas fa-inbox" style="font-size:32px; margin-bottom:12px; opacity:0.5;"></i>
                    <p style="margin:0; font-size:15px;">No programs added yet.</p>
                </div>
            @endif
        </div>
    </div>
</section>
