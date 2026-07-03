<div class="eSection-wrap mb-3">
    <div class="d-flex flex-wrap" style="font-size:12px">
        <a href="{{ route('admin.settings.school') }}"
            class="px-3 py-2 border-end {{ $active=='general'?'fw-bold text-primary':'' }}"
            style="text-decoration:none;color:inherit">
            <i class="bi bi-gear"></i> {{ get_phrase('General') }}
        </a>
        <a href="{{ route('admin.settings.academic') }}"
            class="px-3 py-2 border-end {{ $active=='academic'?'fw-bold text-primary':'' }}"
            style="text-decoration:none;color:inherit">
            <i class="bi bi-mortarboard"></i> {{ get_phrase('Grading Scale') }}
        </a>
        <a href="{{ route('admin.settings.notifications') }}"
            class="px-3 py-2 border-end {{ $active=='notifications'?'fw-bold text-primary':'' }}"
            style="text-decoration:none;color:inherit">
            <i class="bi bi-bell"></i> {{ get_phrase('Notifications') }}
        </a>
        <a href="{{ route('admin.settings.permissions') }}"
            class="px-3 py-2 border-end {{ $active=='permissions'?'fw-bold text-primary':'' }}"
            style="text-decoration:none;color:inherit">
            <i class="bi bi-shield-check"></i> {{ get_phrase('Permissions') }}
        </a>
        <a href="{{ route('admin.settings.backup') }}"
            class="px-3 py-2 border-end {{ $active=='backup'?'fw-bold text-primary':'' }}"
            style="text-decoration:none;color:inherit">
            <i class="bi bi-cloud-arrow-down"></i> {{ get_phrase('Backup') }}
        </a>
        <a href="{{ route('admin.settings.api') }}"
            class="px-3 py-2 {{ $active=='api'?'fw-bold text-primary':'' }}"
            style="text-decoration:none;color:inherit">
            <i class="bi bi-code-square"></i> {{ get_phrase('API') }}
        </a>
    </div>
</div>
