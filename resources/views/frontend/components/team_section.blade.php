{{-- Team/Leadership Component - Professional Modern Design --}}
<section class="team-section section-padding" id="{{ $section->section_key }}">
    <div class="container-xl">
        <!-- Section Title -->
        <div class="section-title">
            @if(!empty($section->subtitle))
                <span class="section-badge">
                    <i class="fas fa-users" style="margin-right: 6px;"></i>
                    {{ $section->subtitle }}
                </span>
            @endif
            <h2>{{ $section->title }}</h2>
            <div class="divider mx-auto"></div>
            @if(!empty($section->content))
                <p>{{ $section->content }}</p>
            @endif
        </div>

        <!-- Team Grid -->
        <div class="row g-4" style="margin-top: 40px;">
            @if($items && $items->count() > 0)
                @foreach($items as $item)
                    @if($item->status == 1)
                        <div class="col-lg-4" style="grid-column: span 4;">
                            <div class="card-item team-card">
                                <!-- Team Image -->
                                <div style="margin: -24px -24px 16px -24px; height: 200px; background: linear-gradient(135deg, var(--secondary-light), var(--secondary-color)); border-radius: var(--radius-md) var(--radius-md) 0 0; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                    @if(!empty($item->image))
                                        <img src="{{ asset('assets/uploads/website/'.$item->image) }}" 
                                             alt="{{ $item->title }}" 
                                             style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;"
                                             onmouseover="this.style.transform='scale(1.1)'"
                                             onmouseout="this.style.transform='scale(1)'">
                                    @else
                                        <i class="fas fa-user-tie" style="font-size: 60px; color: rgba(255,255,255,0.5);"></i>
                                    @endif
                                </div>

                                <!-- Team Info -->
                                <h5 class="team-name">{{ $item->title }}</h5>
                                
                                @if(!empty($item->subtitle))
                                    <p class="team-role">
                                        <i class="fas fa-briefcase" style="margin-right: 6px;"></i>
                                        {{ $item->subtitle }}
                                    </p>
                                @endif

                                @if(!empty($item->description))
                                    <p class="team-bio">{{ $item->description }}</p>
                                @endif

                                @if(!empty($item->content))
                                    <p style="font-size: 13px; color: var(--text-secondary); margin-top: 12px;">
                                        {{ substr($item->content, 0, 100) }}{{ strlen($item->content) > 100 ? '...' : '' }}
                                    </p>
                                @endif

                                <!-- Social Links or CTA -->
                                @if(!empty($item->link))
                                    <div style="margin-top: 16px;">
                                        <a href="{{ $item->link }}" target="_blank" class="card-button" style="font-size: 12px;">
                                            <i class="fas fa-envelope" style="margin-right: 6px;"></i> Contact
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            @else
                <div style="grid-column: 1 / -1; background:var(--light-bg); border:2px dashed var(--border-color); border-radius:var(--radius-lg); padding:40px; text-align:center; color:var(--text-secondary);">
                    <i class="fas fa-inbox" style="font-size:32px; margin-bottom:12px; opacity:0.5;"></i>
                    <p style="margin:0; font-size:15px;">No team members added yet.</p>
                </div>
            @endif
        </div>
    </div>
</section>
