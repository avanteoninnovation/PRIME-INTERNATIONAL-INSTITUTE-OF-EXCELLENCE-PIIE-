{{-- FAQ/Accordion Component - Professional Modern Design --}}
<section class="faqs-section section-padding" id="{{ $section->section_key }}">
    <div class="container-xl">
        <div class="row">
            <div class="col-lg-8" style="grid-column: span 8; margin: 0 auto;">
                <!-- Section Title -->
                <div class="section-title">
                    @if(!empty($section->subtitle))
                        <span class="section-badge">
                            <i class="fas fa-question-circle" style="margin-right: 6px;"></i>
                            {{ $section->subtitle }}
                        </span>
                    @endif
                    <h2>{{ $section->title }}</h2>
                    <div class="divider mx-auto"></div>
                    @if(!empty($section->content))
                        <p>{{ $section->content }}</p>
                    @endif
                </div>

                <!-- FAQ Accordion -->
                <div style="margin-top: 40px;">
                    @if($items && $items->count() > 0)
                        @foreach($items as $index => $faq)
                            @if($faq->status == 1)
                                <div style="
                                    background: var(--white-bg);
                                    border: 1px solid var(--border-color);
                                    border-radius: var(--radius-md);
                                    margin-bottom: 16px;
                                    overflow: hidden;
                                    box-shadow: var(--shadow-sm);
                                    transition: all 0.3s ease;
                                "
                                onmouseover="this.style.boxShadow='var(--shadow-md)'; this.style.borderColor='var(--secondary-color)'"
                                onmouseout="this.style.boxShadow='var(--shadow-sm)'; this.style.borderColor='var(--border-color)'">
                                    
                                    <!-- FAQ Header -->
                                    <div style="
                                        padding: 20px 24px;
                                        background: linear-gradient(135deg, var(--lighter-bg), var(--light-bg));
                                        cursor: pointer;
                                        display: flex;
                                        align-items: center;
                                        justify-content: space-between;
                                        user-select: none;
                                        transition: all 0.3s ease;
                                    "
                                    onclick="toggleFAQ(this)"
                                    onmouseover="this.style.background='linear-gradient(135deg, var(--light-bg), var(--lighter-bg))'"
                                    onmouseout="this.style.background='linear-gradient(135deg, var(--lighter-bg), var(--light-bg))'">
                                        <div style="flex: 1; display: flex; align-items: center; gap: 14px;">
                                            <span style="
                                                width: 28px;
                                                height: 28px;
                                                background: linear-gradient(135deg, var(--secondary-color), var(--secondary-light));
                                                border-radius: 50%;
                                                display: flex;
                                                align-items: center;
                                                justify-content: center;
                                                color: #fff;
                                                font-weight: 700;
                                                font-size: 12px;
                                            ">{{ $index + 1 }}</span>
                                            <h5 style="
                                                color: var(--primary-color);
                                                font-weight: 700;
                                                margin: 0;
                                                font-size: 16px;
                                                line-height: 1.4;
                                            ">{{ $faq->title }}</h5>
                                        </div>
                                        <i class="fas fa-chevron-down" style="
                                            color: var(--secondary-color);
                                            transition: transform 0.3s ease;
                                            font-size: 18px;
                                            flex-shrink: 0;
                                            margin-left: 16px;
                                        "></i>
                                    </div>

                                    <!-- FAQ Content -->
                                    <div style="
                                        max-height: 0;
                                        overflow: hidden;
                                        transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                                    ">
                                        <div style="
                                            padding: 24px;
                                            color: var(--text-secondary);
                                            line-height: 1.8;
                                            border-top: 1px solid var(--border-color);
                                            background: var(--white-bg);
                                        ">
                                            @if(!empty($faq->description))
                                                <p style="margin-bottom: 12px;">{{ $faq->description }}</p>
                                            @endif
                                            @if(!empty($faq->content))
                                                <p style="margin: 0;">{{ $faq->content }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @else
                        <div style="background:var(--light-bg); border:2px dashed var(--border-color); border-radius:var(--radius-lg); padding:40px; text-align:center; color:var(--text-secondary);">
                            <i class="fas fa-inbox" style="font-size:32px; margin-bottom:12px; opacity:0.5;"></i>
                            <p style="margin:0; font-size:15px;">No FAQs added yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function toggleFAQ(element) {
    const contentDiv = element.nextElementSibling;
    const icon = element.querySelector('i');
    const isOpen = contentDiv.style.maxHeight && contentDiv.style.maxHeight !== '0px';
    
    // Close all other FAQs
    document.querySelectorAll('.faqs-section [style*="max-height"]').forEach(el => {
        if (el !== contentDiv && el.style.maxHeight && el.style.maxHeight !== '0px') {
            el.style.maxHeight = '0';
            el.parentElement.querySelector('i').style.transform = 'rotate(0deg)';
        }
    });
    
    // Toggle current FAQ
    if (isOpen) {
        contentDiv.style.maxHeight = '0';
        icon.style.transform = 'rotate(0deg)';
    } else {
        contentDiv.style.maxHeight = contentDiv.scrollHeight + 'px';
        icon.style.transform = 'rotate(180deg)';
    }
}
</script>
