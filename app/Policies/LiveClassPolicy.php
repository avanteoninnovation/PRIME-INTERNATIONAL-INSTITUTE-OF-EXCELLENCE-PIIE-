<?php

namespace App\Policies;

use App\Models\LiveClass;
use App\Models\User;

class LiveClassPolicy
{
    public function viewAny(User $user): bool
    {
        if ((int) $user->role_id === 7) {
            return true;
        }

        return $this->isStaff($user) && $this->hasMenuPermission($user, 'admin.live_classes');
    }

    public function view(User $user, LiveClass $liveClass): bool
    {
        if ((int) $user->school_id !== (int) $liveClass->school_id) {
            return false;
        }

        if ((int) $user->role_id === 7) {
            return (bool) $liveClass->is_published;
        }

        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        if ((int) $user->role_id === 7) {
            return false;
        }

        if (!$this->isStaff($user) || !$this->hasMenuPermission($user, 'admin.live_classes')) {
            return false;
        }

        return in_array((int) $user->role_id, [1, 2, 3, 10, 12, 14], true);
    }

    public function update(User $user, LiveClass $liveClass): bool
    {
        if ((int) $user->school_id !== (int) $liveClass->school_id) {
            return false;
        }

        if (!$this->isStaff($user) || !$this->hasMenuPermission($user, 'admin.live_classes')) {
            return false;
        }

        if ($this->canManageAll($user)) {
            return true;
        }

        return (int) $user->id === (int) ($liveClass->teacher_id ?: $liveClass->created_by);
    }

    public function delete(User $user, LiveClass $liveClass): bool
    {
        return $this->update($user, $liveClass);
    }

    public function cancel(User $user, LiveClass $liveClass): bool
    {
        return $this->update($user, $liveClass);
    }

    public function publish(User $user, LiveClass $liveClass): bool
    {
        if ((int) $user->school_id !== (int) $liveClass->school_id) {
            return false;
        }

        if (!$this->isStaff($user) || !$this->hasMenuPermission($user, 'admin.live_classes')) {
            return false;
        }

        return $this->canManageAll($user);
    }

    public function join(User $user, LiveClass $liveClass): bool
    {
        if ((int) $user->school_id !== (int) $liveClass->school_id) {
            return false;
        }

        if ((int) $user->role_id === 7) {
            return (bool) $liveClass->is_published;
        }

        return $this->viewAny($user);
    }

    public function managePlatforms(User $user): bool
    {
        if (!$this->isStaff($user) || !$this->hasMenuPermission($user, 'admin.setting')) {
            return false;
        }

        return in_array((int) $user->role_id, [1, 2, 14], true);
    }

    private function isStaff(User $user): bool
    {
        return in_array((int) $user->role_id, [1, 2, 3, 4, 5, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19], true)
            && $user->account_status !== 'disable';
    }

    private function canManageAll(User $user): bool
    {
        return in_array((int) $user->role_id, [1, 2, 10, 12, 14], true);
    }

    private function hasMenuPermission(User $user, string $routeKey): bool
    {
        if (empty($user->menu_permission) || $user->menu_permission === 'null') {
            return true;
        }

        $permissions = json_decode($user->menu_permission, true);
        if (!is_array($permissions)) {
            return false;
        }

        return in_array($routeKey, $permissions, true);
    }
}
