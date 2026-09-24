<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Staff\StaffRecordService;

/**
 * Serves PRIVATE staff documents, resolved only by database id.
 *
 * The route is mapped to staff.documents.view in the permission registry
 * ('rbac' middleware → 403 without it); StaffRecordService re-checks the
 * permission and resolves the id within the signed-in user's school, so another
 * school's document is a plain 404 that reveals nothing (no name, no metadata).
 * The file is sent as an attachment with no-sniff / no-store headers; its
 * storage path never leaves the server.
 */
class StaffDocumentController extends Controller
{
    public function download(StaffRecordService $records, $id)
    {
        abort_unless(is_numeric($id), 404);
        [$document, $path] = $records->documentForDownload(auth()->user(), (int) $id);

        return response()->download($path, $document->original_name, [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
