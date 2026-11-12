<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAuditEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinanceAuditController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isAccountant(), 403);
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:64'],
            'subject_type' => ['nullable', Rule::in(['FinanceCashRemittance', 'FinanceExpenseRequest', 'FinanceSupplierInvoice', 'FinanceCashClosure', 'FinanceExternalLoan'])],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $query = FinanceAuditEvent::query()->with('user')
            ->when(! empty($filters['action']), fn ($query) => $query->where('action', $filters['action']))
            ->when(! empty($filters['subject_type']), fn ($query) => $query->where('subject_type', $filters['subject_type']))
            ->when(! empty($filters['user_id']), fn ($query) => $query->where('user_id', $filters['user_id']))
            ->when(! empty($filters['from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->latest('created_at');

        return view('finance.audit.index', [
            'events' => $query->paginate(7)->withQueryString(),
            'filters' => $filters,
            'users' => User::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
            'actions' => FinanceAuditEvent::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
