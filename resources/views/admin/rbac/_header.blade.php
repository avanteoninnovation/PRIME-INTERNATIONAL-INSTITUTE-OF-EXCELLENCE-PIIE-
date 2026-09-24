{{-- RBAC Phase 3B — shared header, tabs and scoped styles for the Roles & Permissions screens. --}}
<style>
    .rbac { color: #1f2937; }
    .rbac .rbac-tabs { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
    .rbac .rbac-tab { padding: .5rem 1rem; border-radius: 8px; border: 1px solid #d0d5dd; background: #fff; color: #344054; text-decoration: none; font-weight: 500; }
    .rbac .rbac-tab:hover { background: #f2f4f7; color: #101828; }
    .rbac .rbac-tab.active { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
    .rbac .rbac-card { background: #fff; border: 1px solid #e4e7ec; border-radius: 10px; padding: 1.25rem; margin-bottom: 1rem; }
    .rbac .rbac-card h5 { font-size: 1rem; font-weight: 600; color: #101828; margin-bottom: .75rem; }
    .rbac .rbac-muted { color: #475467; font-size: .875rem; }
    .rbac .rbac-key { font-family: SFMono-Regular, Consolas, monospace; font-size: .75rem; color: #475467; }
    .rbac .rbac-badge { display: inline-block; padding: .15rem .5rem; border-radius: 999px; font-size: .75rem; font-weight: 600; border: 1px solid transparent; white-space: nowrap; }
    .rbac .rbac-badge-sensitive { background: #fef3f2; color: #b42318; border-color: #fecdca; }
    .rbac .rbac-badge-locked { background: #f2f4f7; color: #344054; border-color: #d0d5dd; }
    .rbac .rbac-badge-base { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
    .rbac .rbac-badge-role { background: #f5f3ff; color: #6d28d9; border-color: #ddd6fe; }
    .rbac .rbac-badge-direct { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .rbac .rbac-badge-admin { background: #101828; color: #fff; }
    .rbac .rbac-badge-legacy { background: #fffbeb; color: #92400e; border-color: #fde68a; }
    .rbac .rbac-badge-active { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .rbac .rbac-badge-inactive { background: #f2f4f7; color: #475467; border-color: #d0d5dd; }
    .rbac .rbac-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
    .rbac .rbac-module { border: 1px solid #e4e7ec; border-radius: 10px; padding: 1rem; background: #fff; }
    .rbac .rbac-module-head { display: flex; justify-content: space-between; align-items: center; gap: .5rem; margin-bottom: .5rem; padding-bottom: .5rem; border-bottom: 1px solid #f2f4f7; }
    .rbac .rbac-module-head strong { color: #101828; }
    .rbac .rbac-perm { display: flex; gap: .6rem; align-items: flex-start; padding: .45rem .35rem; border-radius: 6px; }
    .rbac .rbac-perm:hover { background: #f9fafb; }
    .rbac .rbac-perm input[type=checkbox] { width: 1.1rem; height: 1.1rem; margin-top: .15rem; accent-color: #1d4ed8; flex-shrink: 0; cursor: pointer; }
    .rbac .rbac-perm.is-sensitive input[type=checkbox] { accent-color: #b42318; }
    .rbac .rbac-perm label { cursor: pointer; margin: 0; }
    .rbac .rbac-perm.is-disabled { opacity: .65; }
    .rbac .rbac-perm.is-disabled label, .rbac .rbac-perm.is-disabled input { cursor: not-allowed; }
    .rbac .rbac-perm .rbac-label { font-weight: 500; color: #101828; }
    .rbac .rbac-perm .rbac-desc { display: block; color: #475467; font-size: .8rem; }
    .rbac .rbac-warning { background: #fffaeb; border: 1px solid #fedf89; color: #93370d; border-radius: 8px; padding: .75rem 1rem; }
    .rbac .rbac-danger-zone { border-color: #fecdca; }
    .rbac .rbac-empty { text-align: center; padding: 2rem 1rem; color: #475467; }
    .rbac .rbac-empty i { font-size: 2rem; color: #98a2b3; display: block; margin-bottom: .5rem; }
    .rbac .rbac-actions { display: flex; flex-wrap: wrap; gap: .4rem; }
    .rbac .rbac-actions form { display: inline; margin: 0; }
    .rbac .rbac-table { width: 100%; }
    .rbac .rbac-table th { color: #344054; font-weight: 600; font-size: .8rem; text-transform: uppercase; letter-spacing: .02em; }
    .rbac .rbac-table td, .rbac .rbac-table th { padding: .7rem .6rem; vertical-align: middle; border-bottom: 1px solid #eaecf0; }
    .rbac .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .rbac .rbac-filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: .75rem; align-items: end; }
    .rbac .rbac-source-list { display: flex; flex-wrap: wrap; gap: .3rem; justify-content: flex-end; }
    .rbac .rbac-effective-row { display: flex; justify-content: space-between; gap: .75rem; padding: .45rem 0; border-bottom: 1px dashed #eaecf0; }
    .rbac .rbac-effective-row:last-child { border-bottom: 0; }
    @media (max-width: 575.98px) {
        .rbac .rbac-card { padding: 1rem; }
        .rbac .rbac-effective-row { flex-direction: column; }
        .rbac .rbac-source-list { justify-content: flex-start; }
        .rbac .rbac-actions .btn { flex: 1 1 auto; }
    }
</style>

<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                <div class="d-flex flex-column">
                    <h4>{{ $title }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="{{ route('admin.dashboard') }}">{{ get_phrase('Home') }}</a></li>
                        <li><a href="{{ route('admin.rbac.roles.index') }}">{{ get_phrase('Roles & Permissions') }}</a></li>
                        @isset($crumb)<li><a href="#">{{ $crumb }}</a></li>@endisset
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="rbac-tabs" role="tablist">
    <a class="rbac-tab {{ ($tab ?? '') === 'roles' ? 'active' : '' }}" href="{{ route('admin.rbac.roles.index') }}"><i class="bi bi-diagram-3"></i> {{ get_phrase('Custom Roles') }}</a>
    <a class="rbac-tab {{ ($tab ?? '') === 'staff' ? 'active' : '' }}" href="{{ route('admin.rbac.staff.index') }}"><i class="bi bi-people"></i> {{ get_phrase('Staff Access') }}</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
