<?php

namespace App\Http\Controllers;

use App\Models\CustomerServiceCaseAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CustomerServiceCaseAttachmentController extends Controller
{
    public function download(CustomerServiceCaseAttachment $attachment)
    {
        $user = Auth::user();
        $case = $attachment->customerServiceCase;
        $canManage = $user->hasRole(['ADMINUSER', 'MNG', 'SCR']);
        if ($case->archived_at && ! $user->hasRole(['ADMINUSER'])) {
            $canManage = $case->archiveAccessRequests()
                ->where('requester_id', $user->id)
                ->where('archive_snapshot_at', $case->archived_at)
                ->where('status', 'approved')
                ->exists();
            abort_unless($canManage, 403, 'Un administrateur doit d’abord autoriser l’accès à ce dossier archivé.');
        }
        abort_unless($canManage || $case->assigned_to === $user->id, 403);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }
}
