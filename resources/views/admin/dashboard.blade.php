@extends('layouts.app')

@section('content')
<style>
    /* ============================================ */
    /* FIXED DASHBOARD STYLES - Text Not Cut Off    */
    /* ============================================ */

    /* Dashboard Stats Cards */
    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 18px 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        border-left: 4px solid #1a3a6b;
        transition: all 0.3s ease;
        position: relative;
        overflow: visible;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .stat-card .stat-number {
        font-size: 24px;
        font-weight: 700;
        color: #1a2332;
        margin-bottom: 2px;
        display: block;
    }

    .stat-card .stat-label {
        font-size: 12px;
        color: #6c757d;
        font-weight: 500;
        display: block;
        white-space: normal;
        word-wrap: break-word;
    }

    .stat-card .stat-icon {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 28px;
        opacity: 0.08;
        color: #1a3a6b;
    }

    .stat-card.blue { border-left-color: #1a3a6b; }
    .stat-card.gold { border-left-color: #c8860a; }
    .stat-card.green { border-left-color: #198754; }
    .stat-card.red { border-left-color: #dc3545; }
    .stat-card.purple { border-left-color: #6f42c1; }
    .stat-card.teal { border-left-color: #20c997; }

    .stat-card.blue .stat-icon { color: #1a3a6b; }
    .stat-card.gold .stat-icon { color: #c8860a; }
    .stat-card.green .stat-icon { color: #198754; }
    .stat-card.red .stat-icon { color: #dc3545; }
    .stat-card.purple .stat-icon { color: #6f42c1; }
    .stat-card.teal .stat-icon { color: #20c997; }

    /* Card Styles */
    .card-dashboard {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e9ecef;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        margin-bottom: 20px;
        overflow: visible;
    }

    .card-dashboard .card-header {
        padding: 14px 18px;
        border-bottom: 1px solid #e9ecef;
        background: #fafbfc;
        font-weight: 600;
        font-size: 14px;
        color: #1a2332;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .card-dashboard .card-body {
        padding: 16px 18px;
        overflow: visible;
    }

    /* Badge Status */
    .badge-status {
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
        display: inline-block;
        white-space: nowrap;
    }

    .badge-status.active { background: #d1f5e0; color: #198754; }
    .badge-status.inactive { background: #fce4e4; color: #842029; }

    /* School Item */
    .school-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #f5f5f5;
        overflow: visible;
    }

    .school-item:last-child {
        border-bottom: none;
    }

    .school-item .school-avatar {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: #1a3a6b;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
        flex-shrink: 0;
    }

    .school-item .school-info {
        flex: 1;
        min-width: 0;
    }

    .school-item .school-info .school-name {
        font-weight: 600;
        color: #1a2332;
        font-size: 13px;
        white-space: normal;
        word-wrap: break-word;
    }

    .school-item .school-info .school-details {
        font-size: 11px;
        color: #6c757d;
        white-space: normal;
        word-wrap: break-word;
    }

    /* Event Item */
    .event-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #f5f5f5;
    }

    .event-item:last-child {
        border-bottom: none;
    }

    .event-item .event-date {
        min-width: 40px;
        font-size: 11px;
        color: #6c757d;
        text-align: center;
        line-height: 1.3;
    }

    .event-item .event-date .day {
        font-size: 18px;
        font-weight: 700;
        color: #1a2332;
        display: block;
    }

    .event-item .event-info {
        flex: 1;
        min-width: 0;
    }

    .event-item .event-info .title {
        font-weight: 500;
        color: #1a2332;
        font-size: 13px;
        white-space: normal;
        word-wrap: break-word;
    }

    .event-item .event-info .meta {
        font-size: 11px;
        color: #6c757d;
    }

    /* Table */
    .table-superadmin {
        width: 100%;
        font-size: 13px;
        border-collapse: collapse;
    }

    .table-superadmin thead th {
        background: #f8f9fa;
        padding: 10px 14px;
        text-align: left;
        font-weight: 600;
        color: #1a2332;
        border-bottom: 2px solid #e9ecef;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .table-superadmin tbody td {
        padding: 10px 14px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
        color: #495057;
        white-space: normal;
        word-wrap: break-word;
    }

    .table-superadmin tbody tr:hover {
        background: #f8f9fa;
    }

    /* Two Column Layout */
    .row-two {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }

    @media (max-width: 992px) {
        .row-two {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .dashboard-stats {
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .stat-card {
            padding: 14px 16px;
        }
        .stat-card .stat-number {
            font-size: 20px;
        }
    }

    @media (max-width: 480px) {
        .dashboard-stats {
            grid-template-columns: 1fr;
        }
    }

    /* Section Header */
    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .section-header .section-title {
        font-size: 20px;
        font-weight: 700;
        color: #1a2332;
        margin: 0;
    }

    .section-header .section-title small {
        font-size: 14px;
        font-weight: 400;
        color: #6c757d;
        display: block;
        margin-top: 2px;
    }

    .btn-primary-custom {
        padding: 8px 18px;
        border-radius: 8px;
        border: none;
        background: #1a3a6b;
        color: #ffffff;
        font-weight: 500;
        font-size: 13px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-primary-custom:hover {
        background: #0d1f3c;
        color: #ffffff;
        text-decoration: none;
    }

    .btn-outline-primary-custom {
        padding: 8px 18px;
        border-radius: 8px;
        border: 1.5px solid #1a3a6b;
        background: transparent;
        color: #1a3a6b;
        font-weight: 500;
        font-size: 13px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-outline-primary-custom:hover {
        background: #1a3a6b;
        color: #ffffff;
        text-decoration: none;
    }
</style>

<div class="container-fluid">

    <!-- ============================================ -->
    <!-- WELCOME SECTION                             -->
    <!-- ============================================ -->
    <div class="section-header">
        <div>
            <h1 class="section-title">
                Dashboard
                <small>Home - Dashboard</small>
            </h1>
        </div>
        <div class="section-actions">
            <a href="{{ route('superadmin.school.add') }}" class="btn-primary-custom">
                <i class="fas fa-plus"></i> Add School
            </a>
            <a href="{{ route('superadmin.package') }}" class="btn-outline-primary-custom">
                <i class="fas fa-cube"></i> Packages
            </a>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- STATS CARDS                                 -->
    <!-- ============================================ -->
    <div class="dashboard-stats">
        <div class="stat-card blue">
            <span class="stat-number">{{ $totalSchools ?? 0 }}</span>
            <span class="stat-label">Total Schools</span>
            <i class="fas fa-school stat-icon"></i>
        </div>

        <div class="stat-card gold">
            <span class="stat-number">{{ $activeSchools ?? 0 }}</span>
            <span class="stat-label">Active Schools</span>
            <i class="fas fa-check-circle stat-icon"></i>
        </div>

        <div class="stat-card green">
            <span class="stat-number">{{ $totalAdmins ?? 0 }}</span>
            <span class="stat-label">Total Admins</span>
            <i class="fas fa-user-shield stat-icon"></i>
        </div>

        <div class="stat-card purple">
            <span class="stat-number">{{ $totalSubscriptions ?? 0 }}</span>
            <span class="stat-label">Active Subscriptions</span>
            <i class="fas fa-crown stat-icon"></i>
        </div>

        <div class="stat-card red">
            <span class="stat-number">{{ $pendingRequests ?? 0 }}</span>
            <span class="stat-label">Pending Requests</span>
            <i class="fas fa-clock stat-icon"></i>
        </div>

        <div class="stat-card teal">
            <span class="stat-number">UGX {{ number_format($totalRevenue ?? 0) }}</span>
            <span class="stat-label">Total Revenue</span>
            <i class="fas fa-coins stat-icon"></i>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- TWO COLUMN LAYOUT                           -->
    <!-- ============================================ -->
    <div class="row-two">

        <!-- ============================================ -->
        <!-- LEFT COLUMN - Recent Schools                 -->
        <!-- ============================================ -->
        <div class="card-dashboard">
            <div class="card-header">
                <span><i class="fas fa-school" style="color: #1a3a6b; margin-right: 8px;"></i>Recent Schools</span>
                <a href="{{ route('superadmin.school.list') }}" style="font-size: 12px; color: #1a3a6b; text-decoration: none; font-weight: 500;">View All →</a>
            </div>
            <div class="card-body">
                @if(isset($recentSchools) && count($recentSchools) > 0)
                    @foreach($recentSchools as $school)
                    <div class="school-item">
                        <div class="school-avatar">{{ substr($school->title ?? 'S', 0, 2) }}</div>
                        <div class="school-info">
                            <div class="school-name">{{ $school->title ?? 'School Name' }}</div>
                            <div class="school-details">
                                {{ $school->email ?? '' }} 
                                <span class="badge-status {{ $school->status == 1 ? 'active' : 'inactive' }}">
                                    {{ $school->status == 1 ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                        <a href="{{ route('superadmin.edit.school', $school->id) }}" style="color: #adb5bd; font-size: 12px; flex-shrink: 0;">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    @endforeach
                @else
                    <div style="text-align: center; padding: 30px 20px; color: #6c757d;">
                        <i class="fas fa-school" style="font-size: 28px; opacity: 0.2; display: block; margin-bottom: 8px;"></i>
                        <p style="font-size: 13px; margin: 0;">No schools found.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- ============================================ -->
        <!-- RIGHT COLUMN - Upcoming Events               -->
        <!-- ============================================ -->
        <div class="card-dashboard">
            <div class="card-header">
                <span><i class="fas fa-calendar-alt" style="color: #c8860a; margin-right: 8px;"></i>Upcoming Events</span>
                <a href="{{ route('admin.academic_calendar.index') }}" style="font-size: 12px; color: #1a3a6b; text-decoration: none; font-weight: 500;">See all →</a>
            </div>
            <div class="card-body">
                @if(isset($upcomingEvents) && count($upcomingEvents) > 0)
                    @foreach($upcomingEvents as $event)
                    <div class="event-item">
                        <div class="event-date">
                            <span class="day">{{ \Carbon\Carbon::parse($event->event_date)->format('d') }}</span>
                            {{ \Carbon\Carbon::parse($event->event_date)->format('M') }}
                        </div>
                        <div class="event-info">
                            <div class="title">{{ $event->title }}</div>
                            <div class="meta">{{ \Carbon\Carbon::parse($event->event_date)->format('l, M d Y') }}</div>
                        </div>
                        <span style="padding: 2px 10px; border-radius: 12px; font-size: 9px; font-weight: 600; background: #dbeafe; color: #1a3a6b; flex-shrink: 0;">
                            {{ $event->event_type ?? 'Event' }}
                        </span>
                    </div>
                    @endforeach
                @else
                    <div style="text-align: center; padding: 20px; color: #6c757d; font-size: 13px;">
                        No upcoming events
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- ACTIVE SUBSCRIPTIONS TABLE                  -->
    <!-- ============================================ -->
    <div class="card-dashboard">
        <div class="card-header">
            <span><i class="fas fa-crown" style="color: #c8860a; margin-right: 8px;"></i>Active Subscriptions</span>
            <a href="{{ route('superadmin.subscription.report') }}" style="font-size: 12px; color: #1a3a6b; text-decoration: none; font-weight: 500;">View All →</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <div style="overflow-x: auto;">
                <table class="table-superadmin">
                    <thead>
                        <tr>
                            <th>School</th>
                            <th>Package</th>
                            <th>Amount</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($activeSubscriptions) && count($activeSubscriptions) > 0)
                            @foreach($activeSubscriptions as $sub)
                            <tr>
                                <td><strong>{{ $sub->school->title ?? 'N/A' }}</strong></td>
                                <td>{{ $sub->package->name ?? 'N/A' }}</td>
                                <td>UGX {{ number_format($sub->paid_amount ?? 0) }}</td>
                                <td>{{ \Carbon\Carbon::parse($sub->expire_date)->format('M d, Y') }}</td>
                                <td>
                                    <span class="badge-status active">
                                        {{ $sub->active ? 'Active' : 'Expired' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px 20px; color: #6c757d;">
                                    <i class="fas fa-crown" style="font-size: 24px; opacity: 0.2; display: block; margin-bottom: 8px;"></i>
                                    No active subscriptions found.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@endsection