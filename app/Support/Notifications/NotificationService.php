<?php

namespace App\Support\Notifications;

use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;

/**
 * The one place that writes to user_notifications — every "something a
 * user should know about happened" event in the app should go through this,
 * not insert directly, so the bell/inbox behavior (dedup, batching) stays
 * consistent regardless of which feature triggered it.
 */
class NotificationService
{
    public static function notify(int $userId, int $schoolId, string $title, ?string $body = null, ?string $url = null, string $type = 'general'): UserNotification
    {
        return UserNotification::create([
            'user_id' => $userId,
            'school_id' => $schoolId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ]);
    }

    /**
     * @param iterable<int> $userIds
     */
    public static function notifyMany(iterable $userIds, int $schoolId, string $title, ?string $body = null, ?string $url = null, string $type = 'general'): int
    {
        $now = now();
        $rows = [];

        foreach ($userIds as $userId) {
            $rows[] = [
                'user_id' => (int) $userId,
                'school_id' => $schoolId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'url' => $url,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($rows)) {
            return 0;
        }

        // Bulk insert rather than $rows count of individual create() calls —
        // this is called with every eligible student/attendee for a single
        // reminder, which can be a large list.
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('user_notifications')->insert($chunk);
        }

        return count($rows);
    }
}
