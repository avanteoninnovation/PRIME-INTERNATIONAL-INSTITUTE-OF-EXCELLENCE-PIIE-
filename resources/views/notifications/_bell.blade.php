{{--
    Real notification bell — every role's navigation layout @includes this
    once. Before this, the "notification_icon" bell already present in
    every layout's header was purely decorative (it just sat next to the
    school's name); this is the first thing in the app that actually reads
    from a per-user inbox (see NotificationController, UserNotification).
--}}
<div class="adminTable-action" style="margin-right: 14px; margin-top: 8px;">
    <button
        type="button"
        class="eBtn eBtn-black dropdown-toggle table-action-btn-2 notification-bell-toggle"
        data-bs-toggle="dropdown"
        aria-expanded="false"
        style="position:relative; width: 42px; height: 38px; padding: 0;"
        title="{{ get_phrase('Notifications') }}"
    >
        <i class="bi bi-bell" style="font-size:16px;"></i>
        <span class="notification-unread-badge" style="display:none;position:absolute;top:2px;right:2px;background:#e5484d;color:#fff;border-radius:50%;font-size:10px;line-height:1;padding:3px 5px;"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end eDropdown-menu-2 eDropdown-table-action notification-dropdown-list" style="min-width:320px;max-height:400px;overflow-y:auto;">
        <li class="dropdown-item text-muted text-center">{{ get_phrase('Loading') }}...</li>
    </ul>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.querySelector('.notification-bell-toggle');
    var list = document.querySelector('.notification-dropdown-list');
    var badge = document.querySelector('.notification-unread-badge');
    if (!toggle || !list || !badge) return;

    function refreshBadge() {
        fetch('{{ route('notifications.unread_count') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.count > 0) {
                    badge.style.display = 'inline-block';
                    badge.textContent = data.count > 99 ? '99+' : data.count;
                } else {
                    badge.style.display = 'none';
                }
            })
            .catch(function () {});
    }

    var loaded = false;
    toggle.addEventListener('show.bs.dropdown', function () {
        if (loaded) return;
        loaded = true;
        fetch('{{ route('notifications.dropdown') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) { list.innerHTML = html; refreshBadge(); })
            .catch(function () { list.innerHTML = '<li class="dropdown-item text-danger">{{ get_phrase('Failed to load') }}</li>'; });
    });

    refreshBadge();
    setInterval(refreshBadge, 60000);
});
</script>
