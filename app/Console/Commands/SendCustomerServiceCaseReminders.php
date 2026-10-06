<?php

namespace App\Console\Commands;

use App\Models\CustomerServiceCase;
use App\Models\CustomerServiceSupportMilestone;
use App\Models\User;
use App\Notifications\CustomerServiceCaseNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendCustomerServiceCaseReminders extends Command
{
    protected $signature = 'service-cases:send-reminders';
    protected $description = 'Notifie les responsables des suivis client arrivés à échéance';

    public function handle(): int
    {
        if (! DB::getSchemaBuilder()->hasTable('notifications')) {
            $this->warn('La table notifications est absente. Exécutez les migrations avant les rappels.');
            return self::SUCCESS;
        }

        CustomerServiceCase::with(['customer', 'assignee'])
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<=', now())
            ->whereNotIn('status', ['resolved', 'closed'])
            ->orderBy('next_follow_up_at')
            ->chunkById(100, function ($cases) {
                foreach ($cases as $case) {
                    $reminderType = $case->next_follow_up_at->isToday() ? 'due' : 'overdue';
                    $reminderFor = $reminderType === 'due'
                        ? $case->next_follow_up_at->toDateTimeString()
                        : now()->toDateString();
                    $recipients = collect();
                    if ($case->assignee && $case->assignee->active) {
                        $recipients->push($case->assignee);
                    } else {
                        $recipients = User::whereIn('user_type', ['ADMINUSER', 'MNG', 'SCR'])->where('active', 1)->get();
                    }
                    if ($reminderType === 'overdue') {
                        $recipients = $recipients->merge(User::whereIn('user_type', ['ADMINUSER', 'MNG', 'SCR'])->where('active', 1)->get());
                    }

                    foreach ($recipients as $recipient) {
                        $alreadyNotified = $recipient->notifications()
                            ->where('type', CustomerServiceCaseNotification::class)
                            ->where('data->case_id', $case->id)
                            ->where('data->reminder_type', $reminderType)
                            ->where('data->reminder_for', $reminderFor)
                            ->exists();
                        if ($alreadyNotified) continue;

                        $label = $reminderType === 'due' ? 'arrivé à échéance' : 'en retard';
                        $recipient->notify(new CustomerServiceCaseNotification(
                            $case,
                            'follow_up_'.$reminderType,
                            'Le suivi du dossier '.$case->case_number.' ('.$case->customer->name.') est '.$label.'.',
                            $reminderType,
                            $reminderFor,
                        ));
                    }
                }
            });

        CustomerServiceSupportMilestone::with(['plan.customerServiceCase.customer', 'plan.customerServiceCase.assignee'])
            ->whereNull('completed_at')
            ->where('due_at', '<=', now())
            ->whereHas('plan.customerServiceCase', fn ($query) => $query->whereNotIn('status', ['resolved', 'closed']))
            ->orderBy('due_at')
            ->chunkById(100, function ($milestones) {
                foreach ($milestones as $milestone) {
                    $case = $milestone->plan->customerServiceCase;
                    $reminderType = $milestone->due_at->isToday() ? 'milestone_due' : 'milestone_overdue';
                    $reminderFor = $reminderType === 'milestone_due' ? $milestone->due_at->toDateTimeString() : now()->toDateString();
                    $recipients = collect();
                    if ($case->assignee && $case->assignee->active) {
                        $recipients->push($case->assignee);
                    } else {
                        $recipients = User::whereIn('user_type', ['ADMINUSER', 'MNG', 'SCR'])->where('active', 1)->get();
                    }
                    if ($reminderType === 'milestone_overdue') {
                        $recipients = $recipients->merge(User::whereIn('user_type', ['ADMINUSER', 'MNG', 'SCR'])->where('active', 1)->get());
                    }

                    foreach ($recipients->unique('id') as $recipient) {
                        $alreadyNotified = $recipient->notifications()
                            ->where('type', CustomerServiceCaseNotification::class)
                            ->where('data->case_id', $case->id)
                            ->where('data->milestone_id', $milestone->id)
                            ->where('data->reminder_type', $reminderType)
                            ->where('data->reminder_for', $reminderFor)
                            ->exists();
                        if ($alreadyNotified) continue;

                        $label = $reminderType === 'milestone_due' ? 'arrivée à échéance' : 'en retard';
                        $recipient->notify(new CustomerServiceCaseNotification(
                            $case,
                            $reminderType,
                            'Une étape d’accompagnement du dossier '.$case->case_number.' ('.$case->customer->name.') est '.$label.'.',
                            $reminderType,
                            $reminderFor,
                            $milestone->id,
                        ));
                    }
                }
            });

        return self::SUCCESS;
    }
}
