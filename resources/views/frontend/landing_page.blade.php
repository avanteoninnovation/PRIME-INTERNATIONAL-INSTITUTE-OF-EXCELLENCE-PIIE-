@extends('frontend.index')
@section('content')

@php
    $ws = $websiteSettings ?? collect();
    $sections = $websiteSections ?? collect();
    $items = $websiteItems ?? collect();

    $setting = function ($key, $default = '') use ($ws) {
        return $ws[$key] ?? $default;
    };

    $sectionField = function ($key, $field, $default = '') use ($sections) {
        if (!isset($sections[$key])) {
            return $default;
        }

        return $sections[$key]->{$field} ?: $default;
    };

    $sectionItems = function ($key) use ($items) {
        return $items[$key] ?? collect();
    };

    $sectionParagraphs = function ($key, $field = 'content', $default = '') use ($sectionField) {
        $text = trim((string) $sectionField($key, $field, $default));

        if ($text === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split("/(?:\r\n|\r|\n){2,}/", $text) ?: [])));
    };

    $overviewSections = [
        ['key' => 'origin_history', 'icon' => 'fa-landmark'],
        ['key' => 'institutional_character', 'icon' => 'fa-medal'],
        ['key' => 'educational_philosophy', 'icon' => 'fa-lightbulb'],
        ['key' => 'why_choose_us', 'icon' => 'fa-compass'],
    ];

    $experienceSections = [
        ['key' => 'online_learning_odel', 'icon' => 'fa-laptop-house'],
        ['key' => 'student_support_services', 'icon' => 'fa-hands-helping'],
        ['key' => 'international_students', 'icon' => 'fa-earth-africa'],
    ];

    $welcomeItems = $sectionItems('welcome_messages');
    $leadershipItems = $sectionItems('leadership_team');
    $partnerItems = $sectionItems('partnerships_affiliations');
    $logoAsset = asset('assets/uploads/logo/logo.png');
    $footerLogoAsset = asset('assets/uploads/logo/logo1.png');
@endphp

<style>
/* ===== PIIE Landing Page Styles ===== */
:root {
    --primary-color: #1466AF;
    --secondary-color: #F15F23;
    --accent-color: #7CA7CD;
    --light-bg: #f4f7fb;
    --text-muted: #6c757d;
}

/* Top Bar */
.top-bar {
    background: var(--primary-color);
    color: rgba(255,255,255,0.85);
    font-size: 12.5px;
    padding: 7px 0;
    border-bottom: 2px solid var(--secondary-color);
}
.top-bar .tb-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: rgba(255,255,255,0.85);
    text-decoration: none;
    margin-right: 22px;
    transition: color 0.2s;
}
.top-bar .tb-item:hover { color: #fff; }
.top-bar .tb-item i { color: var(--secondary-color); font-size: 11px; }
.top-bar .tb-right { text-align: right; }
.top-bar .tb-right .tb-item { margin-right: 0; margin-left: 18px; }

/* Header */
.header-area {
    background: #fff;
    box-shadow: 0 2px 18px rgba(26,58,107,0.10);
    position: sticky;
    top: 0;
    z-index: 9999;
    padding: 0;
}
.header-inner { padding: 12px 0; gap: 18px; }

.piie-brand {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    flex-shrink: 0;
}
.piie-brand-mark {
    width: 136px;
    max-width: 100%;
    height: auto;
    display: block;
}
.piie-brand-copy {
    display: flex;
    flex-direction: column;
    line-height: 1.1;
}
.piie-brand-copy strong {
    color: var(--primary-color);
    font-size: 18px;
    font-weight: 900;
    letter-spacing: 0.5px;
}
.piie-brand-copy span {
    color: #5f6d7b;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.4px;
    text-transform: uppercase;
}

/* Nav */
.primary-menu { display: flex; align-items: center; list-style: none; margin: 0; padding: 0; gap: 2px; }
.primary-menu .nav-item { position: relative; }
.primary-menu a.nav-link {
    color: #3a3a3a !important;
    font-weight: 600;
  
    font-size: 13.5px;
    text-decoration: none;
    transition: color 0.2s;
    display: block;
    border-radius: 6px;
    white-space: nowrap;
}
.primary-menu a.nav-link:hover {
    color: var(--primary-color) !important;
    background: var(--light-bg);
}
.primary-menu a.nav-link.active {
    color: var(--primary-color) !important;
    background: var(--light-bg);
}

/* Header Buttons */
.header-actions { display: flex; align-items: center; gap: 10px; justify-content: flex-end; flex-shrink: 0; }
.btn-login {
    background: transparent;
    color: var(--primary-color) !important;
    border: 2px solid var(--primary-color);
    padding: 7px 20px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 13.5px;
    text-decoration: none;
    transition: all 0.2s;
    white-space: nowrap;
}
.btn-login:hover { background: var(--primary-color); color: #fff !important; }
.btn-apply {
    background: var(--secondary-color);
    color: #fff !important;
    border: 2px solid var(--secondary-color);
    padding: 7px 20px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 13.5px;
    text-decoration: none;
    transition: all 0.2s;
    white-space: nowrap;
}
.btn-apply:hover { background: #c5621a; border-color: #c5621a; }
.login-btn { /* legacy compat */
    background: var(--secondary-color);
    color: #fff !important;
    padding: 7px 20px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 13.5px;
    text-decoration: none;
    transition: background 0.2s;
    white-space: nowrap;
}
.login-btn:hover { background: #c5621a; }

/* Hero Section */
.hero-section {
    background: linear-gradient(135deg, var(--primary-color) 60%, var(--accent-color) 100%);
    color: #fff;
    padding: 80px 0 60px;
    position: relative;
    overflow: hidden;
}
.hero-section::before {
    content: '';
    position: absolute;
    right: -60px; top: -60px;
    width: 380px; height: 380px;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
}
.hero-section h1 { font-size: 2.4rem; font-weight: 800; margin-bottom: 12px; line-height: 1.2; }
.hero-section .tagline { font-size: 1.1rem; font-weight: 500; color: var(--secondary-color); margin-bottom: 8px; letter-spacing: 1px; }
.hero-section .motto { font-size: 0.95rem; color: rgba(255,255,255,0.75); margin-bottom: 28px; font-style: italic; }
.hero-section p { font-size: 1rem; color: rgba(255,255,255,0.88); margin-bottom: 32px; max-width: 540px; }
.hero-btns .btn-primary-custom {
    background: var(--secondary-color);
    color: #fff;
    border: none;
    padding: 12px 32px;
    border-radius: 4px;
    font-weight: 700;
    font-size: 15px;
    text-decoration: none;
    margin-right: 12px;
    display: inline-block;
    transition: background 0.2s;
}
.hero-btns .btn-outline-custom {
    background: transparent;
    color: #fff;
    border: 2px solid rgba(255,255,255,0.6);
    padding: 10px 28px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 15px;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
}
.hero-btns .btn-outline-custom:hover { background: rgba(255,255,255,0.15); }
.hero-badge {
    display: inline-block;
    background: rgba(255,255,255,0.18);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    padding: 4px 14px;
    border-radius: 20px;
    margin-bottom: 18px;
    text-transform: uppercase;
}
.hero-stats { margin-top: 40px; }
.hero-stats .stat-item { text-align: center; }
.hero-stats .stat-item h3 { font-size: 2rem; font-weight: 800; color: var(--secondary-color); margin: 0; }
.hero-stats .stat-item span { font-size: 13px; color: rgba(255,255,255,0.75); }

/* Section Common */
.section-padding { padding: 70px 0; }
.section-title { margin-bottom: 50px; }
.section-title h2 { font-size: 2rem; font-weight: 800; color: var(--primary-color); margin-bottom: 10px; }
.section-title p { color: var(--text-muted); max-width: 600px; }
.section-title .section-badge {
    display: inline-block;
    background: var(--secondary-color);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    padding: 4px 14px;
    border-radius: 20px;
    margin-bottom: 12px;
    text-transform: uppercase;
}
.divider { width: 60px; height: 4px; background: var(--secondary-color); border-radius: 2px; margin: 14px 0; }

/* About Section */
.about-section { background: #fff; }
.about-section .about-text h3 { color: var(--primary-color); font-weight: 700; font-size: 1.5rem; }
.about-section .about-text p { color: #444; line-height: 1.8; }
.about-highlight {
    background: var(--light-bg);
    border-left: 4px solid var(--secondary-color);
    padding: 18px 22px;
    border-radius: 0 8px 8px 0;
    margin-bottom: 18px;
}
.about-highlight h5 { color: var(--primary-color); font-weight: 700; margin-bottom: 4px; }
.about-highlight p { margin: 0; color: #555; font-size: 14px; }

/* Editorial Sections */
.story-section,
.messages-section,
.faq-home-section { background: #fff; }
.insight-card,
.narrative-panel,
.message-card,
.experience-card,
.partner-tile {
    background: #fff;
    border: 1px solid #e7edf5;
    border-radius: 16px;
    padding: 28px;
    height: 100%;
    box-shadow: 0 10px 30px rgba(13,58,92,0.06);
}
.insight-card {
    display: flex;
    gap: 18px;
    align-items: flex-start;
}
.insight-icon,
.experience-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: #fff;
    flex-shrink: 0;
    font-size: 22px;
}
.insight-kicker,
.partner-meta {
    display: inline-block;
    color: var(--secondary-color);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    margin-bottom: 8px;
}
.insight-body h3,
.narrative-panel h3,
.message-card h4,
.experience-card h3,
.partner-tile h4 {
    color: var(--primary-color);
    font-weight: 800;
    margin-bottom: 10px;
}
.insight-body p,
.narrative-panel p,
.message-card p,
.experience-card p,
.partner-tile p {
    color: #4c5967;
    line-height: 1.8;
    margin: 0;
}
.narrative-panel p + p,
.experience-card p + p { margin-top: 14px; }
.message-card {
    position: relative;
    padding-top: 34px;
}
.message-card::before {
    content: '\201C';
    position: absolute;
    top: 10px;
    left: 22px;
    color: rgba(20, 102, 175, 0.12);
    font-size: 52px;
    line-height: 1;
    font-weight: 800;
}
.message-role {
    color: var(--secondary-color);
    font-weight: 700;
    font-size: 13px;
    margin-bottom: 10px;
}
.message-summary { margin-bottom: 12px !important; }
.experience-section { background: var(--light-bg); }
.experience-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
    margin-top: 28px;
}
.experience-badges span {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 999px;
    background: #fff;
    border: 1px solid #dfe8f2;
    color: var(--primary-color);
    font-weight: 700;
    font-size: 13px;
}
.faq-home-section .faq-home-item {
    background: #fff;
    border: 1px solid #e2eaf3;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 8px 22px rgba(13,58,92,0.05);
}
.faq-home-trigger {
    width: 100%;
    background: #fff;
    border: none;
    padding: 20px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    text-align: left;
    color: var(--primary-color);
    font-weight: 700;
}
.faq-home-trigger span:last-child {
    color: var(--secondary-color);
    font-size: 18px;
    transition: transform 0.2s ease;
}
.faq-home-answer {
    padding: 0 22px 20px;
    color: #546170;
    line-height: 1.8;
    display: none;
}
.faq-home-item.active .faq-home-answer { display: block; }
.faq-home-item.active .faq-home-trigger span:last-child { transform: rotate(180deg); }

/* Portals Section */
.portals-section { background: var(--light-bg); }
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

/* Programs Section */
.programs-section { background: #fff; }
.program-card {
    background: #fff;
    border: 1px solid #e8edf4;
    border-radius: 10px;
    padding: 28px 22px 24px;
    height: 100%;
    transition: box-shadow 0.2s, transform 0.2s;
    position: relative;
    overflow: hidden;
}
.program-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,0.1); transform: translateY(-3px); }
.program-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 4px;
    background: var(--primary-color);
}
.program-card .online-badge {
    position: absolute;
    top: 14px; right: 14px;
    background: #28a745;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1px;
    padding: 3px 10px;
    border-radius: 20px;
    text-transform: uppercase;
}
.program-card .coming-badge {
    position: absolute;
    top: 14px; right: 14px;
    background: #6c757d;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1px;
    padding: 3px 10px;
    border-radius: 20px;
    text-transform: uppercase;
}
.program-card .prog-icon {
    width: 52px; height: 52px;
    background: var(--light-bg);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 16px;
}
.program-card .prog-icon i { color: var(--primary-color); font-size: 22px; }
.program-card h4 { color: var(--primary-color); font-weight: 700; font-size: 1.05rem; margin-bottom: 8px; }
.program-card p { color: var(--text-muted); font-size: 13.5px; margin-bottom: 14px; line-height: 1.6; }
.program-card .prog-count {
    font-size: 13px;
    color: var(--secondary-color);
    font-weight: 700;
}

/* Fees Section */
.fees-section { background: var(--light-bg); }
.fee-notice {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 8px;
    padding: 16px 20px;
    font-size: 14px;
    color: #856404;
    margin-top: 28px;
}
.fee-notice i { margin-right: 8px; }
.fee-table-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.07);
    overflow: hidden;
    margin-bottom: 24px;
}
.fee-table-card .fee-header {
    background: var(--primary-color);
    color: #fff;
    padding: 16px 22px;
    font-weight: 700;
    font-size: 1.05rem;
}
.fee-table-card table { margin: 0; }
.fee-table-card table td, .fee-table-card table th {
    padding: 12px 22px;
    font-size: 14px;
}

/* Admissions Section */
.admissions-section { background: #fff; }
.admission-step {
    display: flex;
    align-items: flex-start;
    margin-bottom: 24px;
}
.admission-step .step-num {
    min-width: 40px; height: 40px;
    background: var(--primary-color);
    color: #fff;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 16px;
    margin-right: 18px;
    flex-shrink: 0;
}
.admission-step .step-content h5 { color: var(--primary-color); font-weight: 700; margin-bottom: 4px; }
.admission-step .step-content p { color: #555; font-size: 14px; margin: 0; line-height: 1.6; }
.requirement-list li {
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
    color: #444;
}
.requirement-list li:last-child { border-bottom: none; }
.requirement-list li i { color: var(--secondary-color); margin-right: 10px; }
.warning-box {
    background: #fff3f3;
    border: 1px solid #f5c6cb;
    border-radius: 8px;
    padding: 16px 20px;
    font-size: 14px;
    color: #721c24;
    margin-top: 20px;
}
.warning-box i { margin-right: 8px; }

/* Team Section */
.team-section { background: var(--light-bg); }
.team-card {
    background: #fff;
    border-radius: 10px;
    padding: 32px 22px;
    text-align: center;
    box-shadow: 0 2px 16px rgba(0,0,0,0.07);
    transition: transform 0.2s;
}
.team-card:hover { transform: translateY(-4px); }
.team-card .team-avatar {
    width: 80px; height: 80px;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 16px;
}
.team-card .team-avatar i { color: #fff; font-size: 34px; }
.team-card h4 { color: var(--primary-color); font-weight: 700; font-size: 1.1rem; margin-bottom: 4px; }
.team-card .team-role { color: var(--secondary-color); font-size: 14px; font-weight: 600; }

/* News Section */
.news-section { background: #fff; }
.news-placeholder {
    background: var(--light-bg);
    border-radius: 10px;
    padding: 50px;
    text-align: center;
    color: var(--text-muted);
}

/* Affiliations Section */
.affiliations-section { background: var(--light-bg); }
.affiliation-placeholder {
    background: #fff;
    border-radius: 10px;
    padding: 50px;
    text-align: center;
    color: var(--text-muted);
}

/* Contact Section */
.contact-section { background: var(--primary-color); color: #fff; }
.contact-info-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 22px;
}
.contact-info-item .ci-icon {
    min-width: 44px; height: 44px;
    background: rgba(255,255,255,0.12);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    margin-right: 16px;
    flex-shrink: 0;
}
.contact-info-item .ci-icon i { color: var(--secondary-color); font-size: 18px; }
.contact-info-item .ci-text h5 { color: #fff; font-weight: 700; margin-bottom: 2px; font-size: 14px; }
.contact-info-item .ci-text p, .contact-info-item .ci-text a {
    color: rgba(255,255,255,0.8);
    font-size: 14px;
    margin: 0;
    text-decoration: none;
}
.contact-info-item .ci-text a:hover { color: var(--secondary-color); }
.contact-form-card {
    background: #fff;
    border-radius: 10px;
    padding: 36px 30px;
}
.contact-form-card h4 { color: var(--primary-color); font-weight: 700; margin-bottom: 22px; }
.contact-form-card .form-control {
    border: 1px solid #e0e6ef;
    border-radius: 6px;
    padding: 10px 14px;
    font-size: 14px;
}
.contact-form-card .btn-submit {
    background: var(--secondary-color);
    color: #fff;
    border: none;
    padding: 11px 30px;
    border-radius: 4px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s;
}
.contact-form-card .btn-submit:hover { background: #c5621a; }

/* Footer */
.footer-area { background: #0d213f; color: #c8d4e8; }
.footer-top { padding: 55px 0 30px; }
.footer-logo img {
    max-width: 140px;
    width: 100%;
    height: auto;
    margin-bottom: 16px;
}
.footer-brand {
    display: inline-flex;
    align-items: center;
    gap: 14px;
    text-decoration: none;
    margin-bottom: 16px;
}
.footer-brand .piie-brand-mark {
    width: 126px;
}
.footer-brand .piie-brand-copy strong {
    color: #fff;
    font-size: 15px;
}
.footer-brand .piie-brand-copy span {
    color: #c8d4e8;
    font-size: 10px;
}
.footer-items h4 { color: #fff; font-weight: 700; font-size: 1rem; margin-bottom: 16px; }
.footer-items p { font-size: 14px; line-height: 1.8; }
.footer-links { list-style: none; padding: 0; margin: 0; }
.footer-links li { margin-bottom: 8px; }
.footer-links a { color: #c8d4e8; font-size: 14px; text-decoration: none; transition: color 0.2s; }
.footer-links a:hover { color: var(--secondary-color); }
.footer-contact li { margin-bottom: 10px; font-size: 14px; }
.footer-contact li a { color: #c8d4e8; text-decoration: none; }
.footer-contact li a:hover { color: var(--secondary-color); }
.footer-contact li i { color: var(--secondary-color); margin-right: 8px; width: 16px; }
.footer-bottom {
    background: #081629;
    padding: 16px 0;
    text-align: center;
    color: rgba(255,255,255,0.5);
    font-size: 13px;
}
.footer-social { list-style: none; padding: 0; display: flex; gap: 10px; margin-top: 14px; }
.footer-social a {
    width: 36px; height: 36px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    text-decoration: none;
    transition: background 0.2s;
}
.footer-social a:hover { background: var(--secondary-color); }

/* Hamburger */
.hamburger-icon {
    display: none;
    cursor: pointer;
    width: 38px; height: 38px;
    background: var(--light-bg);
    border: none;
    border-radius: 8px;
    align-items: center; justify-content: center;
    color: var(--primary-color);
    font-size: 18px;
    flex-shrink: 0;
}
/* Mobile nav drawer */
.mobile-nav {
    display: none;
    background: #fff;
    border-top: 1px solid #eef0f5;
    padding: 10px 0 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}
.mobile-nav a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 20px;
    color: var(--primary-color);
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    border-bottom: 1px solid #f4f6fb;
    transition: background 0.15s;
}
.mobile-nav a:hover { background: var(--light-bg); }
.mobile-nav a i { color: var(--secondary-color); width: 16px; text-align: center; }
.mobile-nav .mobile-nav-btns {
    display: flex;
    gap: 10px;
    padding: 14px 20px 4px;
}

@media (max-width: 1199px) {
    .primary-menu a.nav-link { padding: 10px 9px !important; font-size: 13px; }
}
@media (max-width: 991px) {
    .hamburger-icon { display: flex; }
    .hero-section h1 { font-size: 1.7rem; }
    .hero-btns a { margin-bottom: 10px; display: block; text-align: center; }
    .header-actions .btn-apply { display: none; }
    .insight-card { flex-direction: column; }
}
</style>

<!--===== TOP BAR =====-->
<div class="top-bar">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-lg-8 col-md-7 d-none d-md-block">
                <a href="tel:{{ preg_replace('/\s+/', '', $setting('contact_phone_1', '+256 707390607')) }}" class="tb-item"><i class="fa-solid fa-phone"></i> {{ $setting('contact_phone_1', '+256 707390607') }}</a>
                <a href="tel:{{ preg_replace('/\s+/', '', $setting('contact_phone_2', '+256 788099193')) }}" class="tb-item"><i class="fa-solid fa-phone"></i> {{ $setting('contact_phone_2', '+256 788099193') }}</a>
                <a href="mailto:{{ $setting('contact_email', 'admissions@example.com') }}" class="tb-item"><i class="fa-solid fa-envelope"></i> {{ $setting('contact_email', 'admissions@example.com') }}</a>
            </div>
            <div class="col-lg-4 col-md-5 col-12 tb-right">
                <a href="{{ $setting('contact_website', '#') }}" class="tb-item"><i class="fa-solid fa-globe"></i> {{ str_replace(['http://', 'https://'], '', $setting('contact_website', 'Website update pending')) }}</a>
                <a href="{{ route('apply.form') }}" class="tb-item d-none d-lg-inline-flex"><i class="fa-solid fa-pen-to-square"></i> Apply Now</a>
            </div>
        </div>
    </div>
</div>

<!--===== HEADER =====-->
<header class="header-area" id="home">
    <div class="container-xl">
        <div class="header-inner d-flex align-items-center justify-content-between">
            <!-- Logo -->
            <a href="#home" class="piie-brand" aria-label="PIIE Home">
                <img src="{{ $logoAsset }}" alt="PIIE Logo" class="piie-brand-mark">
            </a>

            <!-- Desktop Nav -->
            <nav class="d-none d-xl-flex" style="flex:1; justify-content:flex-start; min-width:0;">
                <ul class="primary-menu">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#portals">Portals</a></li>
                    <li class="nav-item"><a class="nav-link" href="#programs">Programs</a></li>
                    <li class="nav-item"><a class="nav-link" href="#fees">Fees</a></li>
                    <li class="nav-item"><a class="nav-link" href="#news">News</a></li>
                    <li class="nav-item"><a class="nav-link" href="#team">Team</a></li>
                    <li class="nav-item"><a class="nav-link" href="#affiliations">Affiliations</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                </ul>
            </nav>
            <!-- Right Actions -->
            <div class="header-actions">
                @php
                if(isset(auth()->user()->id) && auth()->user()->id != "") {
                    if (auth()->user()->role_id =='1') {
                        $panel = 'Superadmin';
                        $dashboard = route('superadmin.dashboard');
                        $user_profile = route('superadmin.profile');
                    } elseif(auth()->user()->role_id =='2'){
                        $panel = 'Administrator';
                        $dashboard = route('admin.dashboard');
                        $user_profile = route('admin.profile');
                    } elseif(auth()->user()->role_id =='3'){
                        $panel = 'Teacher';
                        $dashboard = route('teacher.dashboard');
                        $user_profile = route('teacher.profile');
                    } elseif(auth()->user()->role_id =='4'){
                        $panel = 'Accountant';
                        $dashboard = route('accountant.dashboard');
                        $user_profile = route('accountant.profile');
                    } elseif(auth()->user()->role_id =='5'){
                        $panel = 'Librarian';
                        $dashboard = route('librarian.dashboard');
                        $user_profile = route('librarian.profile');
                    } elseif(auth()->user()->role_id =='6'){
                        $panel = 'Parent';
                        $dashboard = route('parent.dashboard');
                        $user_profile = route('parent.profile');
                    } elseif(auth()->user()->role_id =='7'){
                        $panel = 'Student';
                        $dashboard = route('student.dashboard');
                        $user_profile = route('student.profile');
                    } elseif(auth()->user()->role_id =='8'){
                        $panel = 'Warden';
                        $dashboard = route('warden.dashboard');
                        $user_profile = route('warden.profile');
                    } else {
                        $panel = 'Dashboard';
                        $dashboard = '/';
                        $user_profile = '/';
                    }
                }
                @endphp
                @if(isset(auth()->user()->id) && auth()->user()->id != "")
                    <a class="btn-login" href="{{ $dashboard }}">{{ $panel }}</a>
                    <div class="dropdown">
                        <button class="btn p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background:none;border:1px solid #e0e6ef;border-radius:50%;padding:2px !important;">
                            <img src="{{ get_user_image(auth()->user()->id) }}" alt="user" style="width:34px;height:34px;border-radius:50%;display:block;">
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" style="min-width:180px;">
                            <li><a class="dropdown-item" href="{{ $user_profile }}"><i class="fa-solid fa-user me-2"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Log out</a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a class="btn-login" href="{{ route('login') }}">Login</a>
                    <a class="btn-apply d-none d-lg-inline-block" href="{{ route('apply.form') }}">Apply Now</a>
                    <a href="{{ route('download.brochure') }}" 
                       class="d-none d-lg-inline-block"
                       style="padding: 10px 16px; background: linear-gradient(135deg, #e87722 0%, #d96912 100%); color: #fff; border-radius: 4px; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 6px; font-size: 13px;">
                        <i class="fa-solid fa-download"></i> Brochure
                    </a>
                @endif
                <button class="hamburger-icon d-xl-none" onclick="toggleMobileMenu()" aria-label="Menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </div>
    <!-- Mobile Nav Drawer -->
    <div class="mobile-nav" id="mobileMenu">
        <a href="#home" onclick="closeMobileMenu()"><i class="fa-solid fa-house"></i> Home</a>
        <a href="#about" onclick="closeMobileMenu()"><i class="fa-solid fa-circle-info"></i> About</a>
        <a href="#portals" onclick="closeMobileMenu()"><i class="fa-solid fa-table-columns"></i> Portals</a>
        <a href="#programs" onclick="closeMobileMenu()"><i class="fa-solid fa-graduation-cap"></i> Programs</a>
        <a href="#fees" onclick="closeMobileMenu()"><i class="fa-solid fa-file-invoice-dollar"></i> Fees</a>
        <a href="#news" onclick="closeMobileMenu()"><i class="fa-solid fa-newspaper"></i> News</a>
        <a href="#team" onclick="closeMobileMenu()"><i class="fa-solid fa-users"></i> Team</a>
        <a href="#affiliations" onclick="closeMobileMenu()"><i class="fa-solid fa-handshake"></i> Affiliations</a>
        <a href="#contact" onclick="closeMobileMenu()"><i class="fa-solid fa-envelope"></i> Contact</a>
        <div class="mobile-nav-btns">
            <a href="{{ route('login') }}" class="btn-login" style="flex:1;text-align:center;">Login</a>
            <a href="{{ route('apply.form') }}" onclick="closeMobileMenu()" class="btn-apply" style="flex:1;text-align:center;">Apply Now</a>
        </div>
    </div>
</header>

<!--===== HERO SECTION =====-->
<section class="hero-section" id="hero">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="hero-badge">{{ $setting('hero_badge', 'Fully Online / Virtual Institute') }}</span>
                <h1>{{ $sectionField('hero_slider', 'title', $setting('institution_name', 'Prime International Institute of Excellence (PIIE)')) }}</h1>
                <div class="tagline">{{ $setting('tagline', 'Internationally Benchmarked Education for Global Excellence') }}</div>
                <div class="motto"><i class="fa-solid fa-quote-left me-1" style="font-size:12px;opacity:0.7;"></i> {{ $setting('motto', 'Strive. Excel. Lead.') }} <i class="fa-solid fa-quote-right ms-1" style="font-size:12px;opacity:0.7;"></i></div>
                <p>{{ $sectionField('hero_slider', 'content', 'Prime International Institute of Excellence delivers rigorous, technology-enabled higher education for learners across Uganda, East Africa, and the wider world.') }}</p>
                <div class="hero-btns">
                    <a href="#programs" class="btn-primary-custom">Explore Programs</a>
                    <a href="{{ route('apply.form') }}" class="btn-outline-custom">Apply Now</a>
                </div>
            </div>
            <div class="col-lg-5 text-center mt-4 mt-lg-0">
                <div class="row hero-stats mt-lg-0 mt-4 justify-content-center">
                    <div class="col-4 stat-item">
                        <h3>7+</h3>
                        <span>Program Categories</span>
                    </div>
                    <div class="col-4 stat-item">
                        <h3>80+</h3>
                        <span>Total Programs</span>
                    </div>
                    <div class="col-4 stat-item">
                        <h3>100%</h3>
                        <span>Online Delivery</span>
                    </div>
                </div>
                <div style="margin-top:32px; background:rgba(255,255,255,0.1); border-radius:12px; padding:22px 28px; text-align:left;">
                    <h6 style="color:var(--secondary-color);font-weight:700;margin-bottom:12px; font-size:13px; text-transform:uppercase; letter-spacing:1px;">Quick Access</h6>
                    <a href="#portals" style="display:block;color:#fff;font-size:14px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.1);text-decoration:none;"><i class="fa-solid fa-arrow-right me-2" style="color:var(--secondary-color);"></i>Student Portal</a>
                    <a href="#portals" style="display:block;color:#fff;font-size:14px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.1);text-decoration:none;"><i class="fa-solid fa-arrow-right me-2" style="color:var(--secondary-color);"></i>School Management System</a>
                    <a href="#portals" style="display:block;color:#fff;font-size:14px;padding:8px 0;text-decoration:none;"><i class="fa-solid fa-arrow-right me-2" style="color:var(--secondary-color);"></i>Webmail</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!--===== ABOUT SECTION =====-->
<section class="about-section section-padding" id="about">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-lg-5 mb-4 mb-lg-0">
                <div class="section-title">
                    <span class="section-badge">About Us</span>
                    <h2>Who We Are</h2>
                    <div class="divider"></div>
                </div>
                <div class="about-highlight">
                    <h5><i class="fa-solid fa-graduation-cap me-2" style="color:var(--secondary-color);"></i>Our Mission</h5>
                    <p>To deliver rigorous, innovative, and internationally benchmarked academic programmes through flexible, technology-enabled learning systems.</p>
                </div>
                <div class="about-highlight">
                    <h5><i class="fa-solid fa-eye me-2" style="color:var(--secondary-color);"></i>Our Vision</h5>
                    <p>To be a premier internationally recognised institution of academic excellence shaping transformational leaders and globally competitive professionals.</p>
                </div>
                <div class="about-highlight">
                    <h5><i class="fa-solid fa-lightbulb me-2" style="color:var(--secondary-color);"></i>Our Motto</h5>
                    <p><strong>Strive. Excel. Lead.</strong></p>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="about-text">
                    <h3>{{ $setting('institution_name', 'Prime International Institute of Excellence (PIIE)') }}</h3>
                    <p>{{ $sectionField('about_institution', 'content', 'Prime International Institute of Excellence is a modern higher education institution established in Uganda to deliver internationally benchmarked academic programmes through flexible, technology-enabled learning.') }}</p>
                    <p>PIIE serves ambitious learners across Uganda, East Africa, and internationally through an Open, Distance and e-Learning model that makes access possible without compromising academic rigour.</p>
                    <p>Every programme at PIIE is built to develop intellectual capacity, professional competence, ethical character, and the confidence to lead in a rapidly evolving global environment.</p>
                    <div class="row mt-4">
                        <div class="col-sm-4 text-center mb-3">
                            <div style="background:var(--light-bg);border-radius:10px;padding:20px 10px;">
                                <h3 style="color:var(--primary-color);font-weight:800;margin:0;">80+</h3>
                                <p style="color:var(--text-muted);font-size:13px;margin:4px 0 0;">Programs Offered</p>
                            </div>
                        </div>
                        <div class="col-sm-4 text-center mb-3">
                            <div style="background:var(--light-bg);border-radius:10px;padding:20px 10px;">
                                <h3 style="color:var(--primary-color);font-weight:800;margin:0;">100%</h3>
                                <p style="color:var(--text-muted);font-size:13px;margin:4px 0 0;">Online Delivery</p>
                            </div>
                        </div>
                        <div class="col-sm-4 text-center mb-3">
                            <div style="background:var(--light-bg);border-radius:10px;padding:20px 10px;">
                                <h3 style="color:var(--secondary-color);font-weight:800;margin:0;">PIIE</h3>
                                <p style="color:var(--text-muted);font-size:13px;margin:4px 0 0;">Wakiso District, Uganda</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!--===== INSTITUTIONAL DIRECTION =====-->
<section class="story-section section-padding" id="direction">
    <div class="container-xl">
        <div class="section-title text-center">
            <span class="section-badge">Institutional Direction</span>
            <h2>Built With Clarity and Purpose</h2>
            <div class="divider mx-auto"></div>
            <p>{{ $sectionField('institutional_character', 'content', 'PIIE is defined by academic rigour, modern delivery, international orientation, and learner-centred support.') }}</p>
        </div>
        <div class="row g-4">
            @foreach($overviewSections as $overview)
                <div class="col-lg-6">
                    <article class="insight-card">
                        <div class="insight-icon"><i class="fa-solid {{ $overview['icon'] }}"></i></div>
                        <div class="insight-body">
                            <span class="insight-kicker">{{ $sectionField($overview['key'], 'subtitle', 'PIIE Overview') }}</span>
                            <h3>{{ $sectionField($overview['key'], 'title', 'Overview') }}</h3>
                            <p>{{ $sectionParagraphs($overview['key'])[0] ?? $sectionField($overview['key'], 'content') }}</p>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
        <div class="row g-4 mt-2">
            <div class="col-lg-6">
                <div class="narrative-panel">
                    <span class="insight-kicker">{{ $sectionField('educational_philosophy', 'subtitle', 'Institutional Philosophy') }}</span>
                    <h3>{{ $sectionField('educational_philosophy', 'title', 'Our Philosophy of Education') }}</h3>
                    @foreach(array_slice($sectionParagraphs('educational_philosophy'), 0, 2) as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-6">
                <div class="narrative-panel">
                    <span class="insight-kicker">{{ $sectionField('strategic_plan', 'subtitle', 'Strategic Direction') }}</span>
                    <h3>{{ $sectionField('strategic_plan', 'title', 'Strategic Plan') }}</h3>
                    @foreach(array_slice($sectionParagraphs('strategic_plan'), 0, 2) as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

<!--===== LEADERSHIP MESSAGES =====-->
<section class="messages-section section-padding" id="messages">
    <div class="container-xl">
        <div class="section-title text-center">
            <span class="section-badge">Welcome Messages</span>
            <h2>{{ $sectionField('welcome_messages', 'title', 'Leadership Perspectives') }}</h2>
            <div class="divider mx-auto"></div>
            <p>{{ $sectionField('welcome_messages', 'content', 'Institutional leadership welcomes students and stakeholders with a shared commitment to excellence.') }}</p>
        </div>
        <div class="row g-4">
            @foreach($welcomeItems as $message)
                <div class="col-lg-6">
                    <article class="message-card">
                        <h4>{{ $message->title }}</h4>
                        @if(!empty($message->subtitle))
                            <div class="message-role">{{ $message->subtitle }}</div>
                        @endif
                        @if(!empty($message->description))
                            <p class="message-summary">{{ $message->description }}</p>
                        @endif
                        @if(!empty($message->content))
                            <p>{{ $message->content }}</p>
                        @endif
                    </article>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!--===== PORTALS SECTION =====-->
<section class="portals-section section-padding" id="portals">
    <div class="container-xl">
        <div class="section-title text-center">
            <span class="section-badge">Quick Access</span>
            <h2>Student &amp; Staff Portals</h2>
            <div class="divider mx-auto"></div>
            <p>Access all {{ $setting('institution_name', 'PIIE') }} digital platforms from one place. Login with your institutional credentials.</p>
        </div>
        <div class="row justify-content-center g-4">
            <div class="col-lg-4 col-md-6">
                <div class="portal-card">
                    <div class="portal-icon"><i class="fa-solid fa-school"></i></div>
                    <h4>School Management System</h4>
                    <p>Access academic records, manage enrollments, class schedules, grades, and administrative functions.</p>
                    <a href="{{ route('login') }}" class="portal-link">Access Portal</a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="portal-card">
                    <div class="portal-icon"><i class="fa-solid fa-user-graduate"></i></div>
                    <h4>Student Portal</h4>
                    <p>View your results, fee statements, course materials, timetables, and registration status online.</p>
                    <a href="{{ route('login') }}" class="portal-link">Student Login</a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="portal-card">
                    <div class="portal-icon"><i class="fa-solid fa-envelope-open-text"></i></div>
                    <h4>Webmail</h4>
                    <p>Access your institutional email for official communications, notices, and correspondence.</p>
                    <a href="mailto:{{ $setting('contact_email', 'admissions@example.com') }}" class="portal-link">Open Webmail</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!--===== PROGRAMS SECTION =====-->
<section class="programs-section section-padding" id="programs">
    <div class="container-xl">
        <div class="section-title text-center">
            <span class="section-badge">Academic Programs</span>
            <h2>Our Programs</h2>
            <div class="divider mx-auto"></div>
            <p>{{ $sectionField('academic_programmes', 'content', 'All programs are delivered fully online through our virtual eLearning platform. Choose a category below to explore available programs.') }}</p>
        </div>
        <div class="row g-4">
            @foreach($sectionItems('programme_categories') as $program)
                <div class="col-lg-4 col-md-6">
                    <div class="program-card">
                        <span class="online-badge">{{ $setting('program_delivery_label', 'FULLY ONLINE / VIRTUAL') }}</span>
                        @if(stripos((string) $program->content, 'coming soon') !== false || stripos((string) $program->content, 'in development') !== false)
                            <span class="coming-badge">Coming Soon</span>
                        @endif
                        <div class="prog-icon"><i class="fa-solid fa-layer-group"></i></div>
                        <h4>{{ $program->title }}</h4>
                        <p>{{ $program->description }}</p>
                        <div class="prog-count"><i class="fa-solid fa-layer-group me-1"></i> {{ $program->content }}</div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="text-center mt-5">
            <p style="color:var(--text-muted);font-size:14px;">For the full list of available programmes, contact the Admissions Office at <a href="mailto:{{ $setting('contact_email', 'admissions@example.com') }}" style="color:var(--secondary-color);">{{ $setting('contact_email', 'admissions@example.com') }}</a>@if($setting('contact_phone_1')) or call <a href="tel:{{ preg_replace('/\s+/', '', $setting('contact_phone_1')) }}" style="color:var(--secondary-color);">{{ $setting('contact_phone_1') }}</a>@endif.</p>
        </div>
    </div>
</section>

<!--===== FEES SECTION =====-->
<section class="fees-section section-padding" id="fees">
    <div class="container-xl">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="section-title text-center">
                    <span class="section-badge">Fees &amp; Payments</span>
                    <h2>Fee Information</h2>
                    <div class="divider mx-auto"></div>
                    <p>Below is a summary of our fee structure. All fees are payable upon acceptance of admission. Contact the institute to confirm current fees before making payment.</p>
                </div>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="fee-table-card">
                    <div class="fee-header"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Graduate Courses &ndash; Fee Guide</div>
                    <table class="table table-striped mb-0">
                        <thead><tr><th>Program Level</th><th>Delivery</th><th>Note</th></tr></thead>
                        <tbody>
                            <tr><td>Postgraduate Diploma</td><td>Online</td><td>Contact institute</td></tr>
                            <tr><td>Master&apos;s Programs</td><td>Online</td><td>Contact institute</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="fee-table-card">
                    <div class="fee-header"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Undergraduate &amp; Below &ndash; Fee Guide</div>
                    <table class="table table-striped mb-0">
                        <thead><tr><th>Program Level</th><th>Delivery</th><th>Note</th></tr></thead>
                        <tbody>
                            <tr><td>Bachelor&apos;s Degree</td><td>Online</td><td>Contact institute</td></tr>
                            <tr><td>Diploma</td><td>Online</td><td>Contact institute</td></tr>
                            <tr><td>National Certificate</td><td>Online</td><td>Contact institute</td></tr>
                            <tr><td>Vocational Programs</td><td>Online</td><td>Contact institute</td></tr>
                            <tr><td>Short Courses</td><td>Online</td><td>Contact institute</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-lg-10 mx-auto">
                <div class="fee-table-card">
                    <div class="fee-header"><i class="fa-solid fa-info-circle me-2"></i>Important Notes on Fee Payment</div>
                    <div class="p-4">
                        <ul style="margin:0;padding-left:20px;font-size:14px;color:#444;line-height:2;">
                            <li>Fees are payable in full on or before the commencement of each semester or programme.</li>
                            <li>All fee down payments shall be made upon acceptance of admission through the student portal.</li>
                            <li>Subsequent fee payments can be made through the Fee Accounts menu in the student portal.</li>
                            <li>Fee payment receipts will reflect in the student portal under the Fee Account menu.</li>
                            <li>The fee structure may be reviewed at the discretion of the institute.</li>
                            <li>All fees paid are <strong>non-refundable</strong> once registration has been processed.</li>
                        </ul>
                    </div>
                </div>
                <div class="fee-notice">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <strong>Please Note:</strong> Fees may be reviewed at any time. Applicants are advised to confirm the current fee structure directly with the institute before making any payment. Contact us at <strong>{{ $setting('contact_email', 'admissions@example.com') }}</strong>@if($setting('contact_phone_1')) or call <strong>{{ $setting('contact_phone_1') }}@if($setting('contact_phone_2')) / {{ $setting('contact_phone_2') }}@endif</strong>@endif.
                </div>
            </div>
        </div>
    </div>
</section>

<!--===== ADMISSIONS SECTION =====-->
<section class="admissions-section section-padding" id="admissions">
    <div class="container-xl">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <div class="section-title">
                    <span class="section-badge">Admissions</span>
                    <h2>Admission Process</h2>
                    <div class="divider mx-auto"></div>
                    <p>PIIE offers fully online study through its ODeL delivery model. The admissions process is straightforward, merit-based, and designed to be accessible to qualified applicants.</p>
                </div>
            </div>
        </div>
        <div class="row g-5">
            <div class="col-lg-6">
                <h4 style="color:var(--primary-color);font-weight:700;margin-bottom:24px;">How to Apply</h4>
                <div class="admission-step">
                    <div class="step-num">1</div>
                    <div class="step-content">
                        <h5>Submit Your Application</h5>
                        <p>Apply through the PIIE admissions process, provide complete and accurate information, and upload the required academic and identity documents for review.</p>
                    </div>
                </div>
                <div class="admission-step">
                    <div class="step-num">2</div>
                    <div class="step-content">
                        <h5>Receive Your Provisional Admission Offer</h5>
                        <p>Upon successful review of your application, PIIE will issue a provisional admission offer letter subject to verification of submitted qualifications and supporting documents.</p>
                    </div>
                </div>
                <div class="admission-step">
                    <div class="step-num">3</div>
                    <div class="step-content">
                        <h5>Accept the Offer &amp; Register</h5>
                        <p>Accept your admission offer through the student portal and complete registration within <strong>four (4) weeks</strong> from the commencement of the semester. Late registration may result in cancellation of your place.</p>
                    </div>
                </div>
                <div class="admission-step">
                    <div class="step-num">4</div>
                    <div class="step-content">
                        <h5>Pay Your Fees</h5>
                        <p>Fees are payable in full on or before commencement of each semester. Down payments are made upon acceptance of admission through the portal. All fees are non-refundable once processed.</p>
                    </div>
                </div>
                <div class="admission-step">
                    <div class="step-num">5</div>
                    <div class="step-content">
                        <h5>Access the eLearning Portal</h5>
                        <p>Once registered and fees are confirmed, you will receive access credentials for the PIIE digital learning environment to begin your studies.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <h4 style="color:var(--primary-color);font-weight:700;margin-bottom:24px;">Required Documents</h4>
                <ul class="requirement-list list-unstyled">
                    <li><i class="fa-solid fa-check-circle"></i>Completed application form (online or physical)</li>
                    <li><i class="fa-solid fa-check-circle"></i>Original and certified copies of all academic certificates and transcripts</li>
                    <li><i class="fa-solid fa-check-circle"></i>Valid national identity card or passport</li>
                    <li><i class="fa-solid fa-check-circle"></i>Passport-size photographs (as specified on the application)</li>
                    <li><i class="fa-solid fa-check-circle"></i>NCHE (National Council for Higher Education) contribution where applicable</li>
                    <li><i class="fa-solid fa-check-circle"></i>Any other supporting documents as required by the programme</li>
                </ul>
                <h4 style="color:var(--primary-color);font-weight:700;margin-top:32px;margin-bottom:16px;">Conditions of Admission</h4>
                <ul class="requirement-list list-unstyled">
                    <li><i class="fa-solid fa-check-circle"></i>Undertaking to adhere to all rules and regulations governing studentship at PIIE</li>
                    <li><i class="fa-solid fa-check-circle"></i>Acceptance to pay fees in accordance with the institute&apos;s fee schedule</li>
                    <li><i class="fa-solid fa-check-circle"></i>Agreement to abide by the terms and conditions set out in the declaration for admission</li>
                    <li><i class="fa-solid fa-check-circle"></i>All fees paid are non-refundable once registration has been processed</li>
                </ul>
                <div class="warning-box mt-4">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <strong>Important:</strong> Any offer of admission will be automatically cancelled if it is established that an applicant provided falsified documents, engaged in impersonation, or submitted false or incomplete information during the application process.
                </div>
                <div style="background:var(--light-bg);border-radius:8px;padding:20px;margin-top:20px;">
                    <h6 style="color:var(--primary-color);font-weight:700;margin-bottom:12px;"><i class="fa-solid fa-envelope me-2" style="color:var(--secondary-color);"></i>Apply or Enquire</h6>
                    <p style="font-size:14px;color:#555;margin:0;">Email: <a href="mailto:{{ $setting('contact_email', 'admissions@example.com') }}" style="color:var(--secondary-color);">{{ $setting('contact_email', 'admissions@example.com') }}</a><br>
                    Phone: <a href="tel:{{ preg_replace('/\s+/', '', $setting('contact_phone_1', '+256')) }}" style="color:var(--secondary-color);">{{ $setting('contact_phone_1', 'Contact update pending') }}</a>@if($setting('contact_phone_2')) | <a href="tel:{{ preg_replace('/\s+/', '', $setting('contact_phone_2')) }}" style="color:var(--secondary-color);">{{ $setting('contact_phone_2') }}</a>@endif<br>
                    Website: <a href="{{ $setting('contact_website', '#') }}" style="color:var(--secondary-color);">{{ str_replace(['http://', 'https://'], '', $setting('contact_website', 'Website update pending')) }}</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!--===== LEARNING EXPERIENCE =====-->
<section class="experience-section section-padding" id="experience">
    <div class="container-xl">
        <div class="section-title text-center">
            <span class="section-badge">Learning Experience</span>
            <h2>Flexible, Supported, and International</h2>
            <div class="divider mx-auto"></div>
            <p>{{ $sectionField('online_learning_odel', 'content', 'PIIE learning is built around flexible access, supported delivery, and internationally oriented student experience.') }}</p>
        </div>
        <div class="row g-4">
            @foreach($experienceSections as $experience)
                <div class="col-lg-4 col-md-6">
                    <article class="experience-card">
                        <div class="experience-icon"><i class="fa-solid {{ $experience['icon'] }}"></i></div>
                        <h3>{{ $sectionField($experience['key'], 'title', 'PIIE Experience') }}</h3>
                        <p>{{ $sectionParagraphs($experience['key'])[0] ?? $sectionField($experience['key'], 'content') }}</p>
                    </article>
                </div>
            @endforeach
        </div>
        <div class="experience-badges">
            <span><i class="fa-solid fa-clock"></i>24/7 digital access</span>
            <span><i class="fa-solid fa-video"></i>Live and recorded facilitation</span>
            <span><i class="fa-solid fa-user-group"></i>Support from enrolment to graduation</span>
        </div>
    </div>
</section>

<!--===== NEWS SECTION =====-->
<section class="news-section section-padding" id="news">
    <div class="container-xl">
        <div class="section-title text-center">
            <span class="section-badge">News &amp; Updates</span>
            <h2>Latest News</h2>
            <div class="divider mx-auto"></div>
            <p>{{ $sectionField('news_events', 'content', 'Stay informed with the latest institutional announcements, academic updates, partnership news, and events from PIIE.') }}</p>
        </div>
        <div class="news-placeholder">
            <i class="fa-solid fa-newspaper" style="font-size:48px;color:#c8d4e8;margin-bottom:16px;display:block;"></i>
            <h5 style="color:var(--primary-color);">News Coming Soon</h5>
            <p style="margin:0;font-size:14px;">News and updates will be posted here. Check back regularly or contact us at <a href="mailto:{{ $setting('contact_email', 'admissions@example.com') }}" style="color:var(--secondary-color);">{{ $setting('contact_email', 'admissions@example.com') }}</a> for the latest information.</p>
        </div>
    </div>
</section>

<!--===== TEAM SECTION =====-->
<section class="team-section section-padding" id="team">
    <div class="container-xl">
        <div class="section-title text-center">
            <span class="section-badge">Leadership</span>
            <h2>Our Team</h2>
            <div class="divider mx-auto"></div>
            <p>{{ $sectionField('leadership_team', 'content', 'Meet the leadership guiding PIIE&apos;s academic mission and institutional growth.') }}</p>
        </div>
        <div class="row justify-content-center g-4">
            @foreach($leadershipItems as $leader)
                <div class="col-lg-3 col-md-6">
                    <div class="team-card">
                        <div class="team-avatar"><i class="fa-solid fa-user-tie"></i></div>
                        <h4>{{ $leader->title }}</h4>
                        <div class="team-role">{{ $leader->subtitle }}</div>
                        <p style="color:var(--text-muted);font-size:13.5px;margin-top:12px;">{{ $leader->description }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!--===== AFFILIATIONS SECTION =====-->
<section class="affiliations-section section-padding" id="affiliations">
    <div class="container-xl">
        <div class="section-title text-center">
            <span class="section-badge">Partnerships &amp; Affiliations</span>
            <h2>Our Affiliations</h2>
            <div class="divider mx-auto"></div>
            <p>{{ $sectionField('partnerships_affiliations', 'content', 'PIIE partners with recognised institutions and regulatory bodies to strengthen programme quality, qualification credibility, and student opportunity.') }}</p>
        </div>
        <div class="row g-4">
            @foreach($partnerItems as $partner)
                <div class="col-lg-4 col-md-6">
                    <article class="partner-tile">
                        <div class="partner-meta">Strategic Partner</div>
                        <h4>{{ $partner->title }}</h4>
                        <p>{{ $partner->description }}</p>
                    </article>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!--===== FAQ SECTION =====-->
<section class="faq-home-section section-padding" id="faqs">
    <div class="container-xl">
        <div class="section-title text-center">
            <span class="section-badge">Frequently Asked Questions</span>
            <h2>{{ $sectionField('faqs', 'title', 'Your Questions, Answered') }}</h2>
            <div class="divider mx-auto"></div>
            <p>{{ $sectionField('faqs', 'content', 'Answers to common questions about PIIE, admissions, study model, and student support.') }}</p>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="d-grid gap-3">
                    @foreach($sectionItems('faqs') as $faq)
                        <div class="faq-home-item">
                            <button type="button" class="faq-home-trigger" onclick="toggleHomeFaq(this.parentElement)">
                                <span>{{ $faq->title }}</span>
                                <span><i class="fa-solid fa-chevron-down"></i></span>
                            </button>
                            <div class="faq-home-answer">{{ $faq->description }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

<!--===== CONTACT SECTION =====-->
<section class="contact-section section-padding" id="contact">
    <div class="container-xl">
        <div class="row">
            <div class="col-lg-5 mb-5 mb-lg-0">
                <span class="section-badge" style="background:var(--secondary-color);">Get In Touch</span>
                <h2 style="color:#fff;font-weight:800;margin:16px 0 10px;">Contact Us</h2>
                <div class="divider" style="background:var(--secondary-color);"></div>
                <p style="color:rgba(255,255,255,0.8);margin-bottom:36px;font-size:15px;">We are here to help. Reach out to us for admissions inquiries, program information, or any other questions.</p>
                <div class="contact-info-item">
                    <div class="ci-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div class="ci-text">
                        <h5>Address</h5>
                        <p>{{ $setting('contact_address', 'Nansana Municipality, Wakiso District, Uganda') }}</p>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="ci-icon"><i class="fa-solid fa-phone"></i></div>
                    <div class="ci-text">
                        <h5>Phone</h5>
                        <a href="tel:{{ preg_replace('/\s+/', '', $setting('contact_phone_1', '+256')) }}">{{ $setting('contact_phone_1', 'Contact update pending') }}</a><br>
                        @if($setting('contact_phone_2'))<a href="tel:{{ preg_replace('/\s+/', '', $setting('contact_phone_2')) }}">{{ $setting('contact_phone_2') }}</a>@endif
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="ci-icon"><i class="fa-solid fa-envelope"></i></div>
                    <div class="ci-text">
                        <h5>Email</h5>
                        <a href="mailto:{{ $setting('contact_email', 'admissions@example.com') }}">{{ $setting('contact_email', 'admissions@example.com') }}</a>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="ci-icon"><i class="fa-solid fa-globe"></i></div>
                    <div class="ci-text">
                        <h5>Website</h5>
                        <a href="{{ $setting('contact_website', '#') }}">{{ str_replace(['http://', 'https://'], '', $setting('contact_website', 'Website update pending')) }}</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="contact-form-card">
                    <h4><i class="fa-solid fa-paper-plane me-2" style="color:var(--secondary-color);"></i>Send Us a Message</h4>
                    <form action="mailto:{{ $setting('contact_email', 'admissions@example.com') }}" method="get" enctype="text/plain">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label style="font-size:13px;font-weight:600;color:#444;margin-bottom:5px;">Full Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Your full name" required>
                            </div>
                            <div class="col-md-6">
                                <label style="font-size:13px;font-weight:600;color:#444;margin-bottom:5px;">Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="Your email address" required>
                            </div>
                            <div class="col-md-6">
                                <label style="font-size:13px;font-weight:600;color:#444;margin-bottom:5px;">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" placeholder="+256...">
                            </div>
                            <div class="col-md-6">
                                <label style="font-size:13px;font-weight:600;color:#444;margin-bottom:5px;">Subject</label>
                                <select name="subject" class="form-control" style="font-size:14px;">
                                    <option value="">Select a subject</option>
                                    <option>Admissions Inquiry</option>
                                    <option>Program Information</option>
                                    <option>Fee Inquiry</option>
                                    <option>Student Portal Support</option>
                                    <option>General Inquiry</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label style="font-size:13px;font-weight:600;color:#444;margin-bottom:5px;">Message</label>
                                <textarea name="body" class="form-control" rows="5" placeholder="Write your message here..." required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn-submit">
                                    <i class="fa-solid fa-paper-plane me-2"></i>Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@php
    $mappedSections = [
        'hero_slider',
        'about_institution',
        'origin_history',
        'institutional_character',
        'vision_mission_motto',
        'core_values',
        'educational_philosophy',
        'why_choose_us',
        'welcome_messages',
        'governance_structure',
        'strategic_plan',
        'academic_programmes',
        'programme_categories',
        'fees_structure',
        'admissions',
        'online_learning_odel',
        'student_support_services',
        'international_students',
        'leadership_team',
        'research_innovation',
        'news_events',
        'partnerships_affiliations',
        'faqs',
        'contact_page',
        'footer_settings',
        'quick_links',
        'portals_links',
        'social_media_links',
    ];
@endphp

@foreach($sections as $dynamicKey => $dynamicSection)
    @if(!in_array($dynamicKey, $mappedSections))
        <section class="section-padding" id="{{ $dynamicKey }}" style="background:#fff; border-top:1px solid #eef2f7;">
            <div class="container-xl">
                <div class="section-title text-center">
                    <span class="section-badge">Dynamic Content</span>
                    <h2>{{ $dynamicSection->title ?: ucwords(str_replace('_', ' ', $dynamicKey)) }}</h2>
                    <div class="divider mx-auto"></div>
                    @if(!empty($dynamicSection->subtitle))
                        <p>{{ $dynamicSection->subtitle }}</p>
                    @endif
                </div>
                @if(!empty($dynamicSection->content))
                    <div class="news-placeholder" style="margin-bottom:20px;">
                        <p style="margin:0; font-size:14px;">{{ $dynamicSection->content }}</p>
                    </div>
                @endif

                @if($sectionItems($dynamicKey)->count() > 0)
                    <div class="row g-4">
                        @foreach($sectionItems($dynamicKey) as $entry)
                            <div class="col-lg-4 col-md-6">
                                <div class="program-card">
                                    <h4>{{ $entry->title }}</h4>
                                    @if(!empty($entry->subtitle))
                                        <p><strong>{{ $entry->subtitle }}</strong></p>
                                    @endif
                                    @if(!empty($entry->description))
                                        <p>{{ $entry->description }}</p>
                                    @endif
                                    @if(!empty($entry->content))
                                        <div class="prog-count">{{ $entry->content }}</div>
                                    @endif
                                    @if(!empty($entry->link))
                                        <a href="{{ $entry->link }}" class="portal-link">Read More</a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif
@endforeach

<!--===== FOOTER =====-->
<footer class="footer-area">
    <div class="footer-top">
        <div class="container-xl">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="footer-items">
                        <div class="footer-logo">
                            <a href="#home" class="footer-brand">
                                <img src="{{ $footerLogoAsset }}" alt="PIIE Logo" class="piie-brand-mark">
                            </a>
                        </div>
                        <p>{{ $setting('institution_name', 'Prime International Institute of Excellence (PIIE)') }} &mdash; committed to rigorous, innovative, and internationally benchmarked higher education.</p>
                        <ul class="footer-social">
                            <li><a href="#" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a></li>
                            <li><a href="#" title="Twitter/X"><i class="fa-brands fa-x-twitter"></i></a></li>
                            <li><a href="#" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a></li>
                            <li><a href="#" title="Instagram"><i class="fa-brands fa-instagram"></i></a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <div class="footer-items">
                        <h4>Quick Links</h4>
                        <ul class="footer-links">
                            <li><a href="#home">Home</a></li>
                            <li><a href="#about">About</a></li>
                            <li><a href="#portals">Portals</a></li>
                            <li><a href="#programs">Programs</a></li>
                            <li><a href="#fees">Fees</a></li>
                            <li><a href="#news">News</a></li>
                            <li><a href="#team">Team</a></li>
                            <li><a href="#affiliations">Affiliations</a></li>
                            <li><a href="#contact">Contact</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-items">
                        <h4>Portals</h4>
                        <ul class="footer-links">
                            <li><a href="{{ route('login') }}">School Management System</a></li>
                            <li><a href="{{ route('login') }}">Student Portal</a></li>
                            <li><a href="mailto:{{ $setting('contact_email', 'admissions@example.com') }}">Webmail</a></li>
                        </ul>
                        <h4 style="margin-top:24px;">Programs</h4>
                        <ul class="footer-links">
                            <li><a href="#programs">Graduate Courses</a></li>
                            <li><a href="#programs">Bachelor Programs</a></li>
                            <li><a href="#programs">Diploma Programs</a></li>
                            <li><a href="#programs">Short Courses</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="footer-items">
                        <h4>Contact Details</h4>
                        <ul class="footer-contact list-unstyled">
                            <li><i class="fa-solid fa-location-dot"></i>{{ $setting('contact_address', 'Nansana Municipality, Wakiso District, Uganda') }}</li>
                            <li><i class="fa-solid fa-phone"></i><a href="tel:{{ preg_replace('/\s+/', '', $setting('contact_phone_1', '+256')) }}">{{ $setting('contact_phone_1', 'Contact update pending') }}</a></li>
                            @if($setting('contact_phone_2'))<li><i class="fa-solid fa-phone"></i><a href="tel:{{ preg_replace('/\s+/', '', $setting('contact_phone_2')) }}">{{ $setting('contact_phone_2') }}</a></li>@endif
                            <li><i class="fa-solid fa-envelope"></i><a href="mailto:{{ $setting('contact_email', 'admissions@example.com') }}">{{ $setting('contact_email', 'admissions@example.com') }}</a></li>
                            <li><i class="fa-solid fa-globe"></i><a href="{{ $setting('contact_website', '#') }}">{{ str_replace(['http://', 'https://'], '', $setting('contact_website', 'Website update pending')) }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p style="margin:0;">{{ $setting('footer_copyright', '© Prime International Institute of Excellence (PIIE). All Rights Reserved.') }}</p>
    </div>
</footer>

<script>
"use strict";
function toggleMobileMenu() {
    var menu = document.getElementById('mobileMenu');
    if (menu.style.display === 'block') {
        menu.style.display = 'none';
    } else {
        menu.style.display = 'block';
    }
}
function closeMobileMenu() {
    var menu = document.getElementById('mobileMenu');
    if (menu) menu.style.display = 'none';
}
function toggleHomeFaq(item) {
    document.querySelectorAll('.faq-home-item').forEach(function(entry) {
        if (entry !== item) {
            entry.classList.remove('active');
        }
    });
    item.classList.toggle('active');
}
// Active nav highlight on scroll
(function() {
    var sections = document.querySelectorAll('section[id], header[id]');
    var navLinks = document.querySelectorAll('.primary-menu a.nav-link');
    window.addEventListener('scroll', function() {
        var scrollY = window.scrollY + 100;
        sections.forEach(function(section) {
            if (scrollY >= section.offsetTop && scrollY < section.offsetTop + section.offsetHeight) {
                navLinks.forEach(function(link) {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === '#' + section.id) {
                        link.classList.add('active');
                    }
                });
            }
        });
    });
})();
document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
    anchor.addEventListener('click', function(e) {
        var target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>

@endsection
