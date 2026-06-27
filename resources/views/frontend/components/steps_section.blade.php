{{-- Accordion/Steps Component --}}
<section class="steps-section section-padding" id="{{ $section->section_key }}" style="background: #fff;">
    <div class="container-xl">
        <div class="row">
            <div class="col-lg-8 mx-auto">
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

                <div style="margin-top: 40px;">
                    @if($items && $items->count() > 0)
                        @foreach($items as $step)
                            @if($step->status == 1)
                                <div style="display: flex; align-items: flex-start; margin-bottom: 28px;">
                                    <div style="min-width: 40px; height: 40px; background: var(--primary-color); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; margin-right: 18px; flex-shrink: 0;">
                                        @if(!empty($step->badge))
                                            {{ $step->badge }}
                                        @else
                                            {{ $loop->iteration }}
                                        @endif
                                    </div>
                                    <div>
                                        <h5 style="color: var(--primary-color); font-weight: 700; margin-bottom: 4px; margin-top: 0;">{{ $step->title }}</h5>
                                        @if(!empty($step->description))
                                            <p style="color: #555; font-size: 14px; margin: 0; line-height: 1.6;">{{ $step->description }}</p>
                                        @endif
                                        @if(!empty($step->content))
                                            <p style="color: #667085; font-size: 13px; margin: 8px 0 0;">{{ $step->content }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
