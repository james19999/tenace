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
        abort_unless($canManage || $case->assigned_to === $user->id, 403);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }
}
