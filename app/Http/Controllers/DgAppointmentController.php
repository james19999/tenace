<?php

namespace App\Http\Controllers;

use App\Models\DgAppointment;
use App\Models\DgAppointmentAvailability;
use App\Models\DgAppointmentCalendar;
use App\Models\User;
use Carbon\Carbon;
use App\Notifications\DgAppointmentNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class DgAppointmentController extends Controller
{
    private const WEEKDAYS = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

    public function updateSettings(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => ['nullable', 'array'],
            'days.*.ranges' => ['nullable', 'array'],
            'days.*.ranges.*.starts_at' => ['nullable', 'date_format:H:i'],
            'days.*.ranges.*.ends_at' => ['nullable', 'date_format:H:i'],
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('settings_tab', 'appointments');
        }
        $validated = $validator->validated();

        foreach (self::WEEKDAYS as $weekday => $label) {
            $day = $validated['days'][$weekday] ?? [];
            $validRanges = [];
            foreach (($day['ranges'] ?? []) as $range) {
                if (empty($range['starts_at']) && empty($range['ends_at'])) {
                    continue;
                }
                if (empty($range['starts_at']) || empty($range['ends_at']) || $range['starts_at'] >= $range['ends_at']) {
                    return back()->withInput()->withErrors([
                        "days.$weekday.ranges" => "Une plage du $label est incomplète ou invalide : indique un début et une fin, avec la fin après le début.",
                    ])->with('settings_tab', 'appointments');
                }
                $validRanges[] = $range;
            }
            usort($validRanges, fn ($a, $b) => strcmp($a['starts_at'], $b['starts_at']));
            for ($index = 1; $index < count($validRanges); $index++) {
                if ($validRanges[$index]['starts_at'] < $validRanges[$index - 1]['ends_at']) {
                    return back()->withInput()->withErrors([
                        "days.$weekday.ranges" => "Les plages du $label se chevauchent. Ajuste leurs horaires.",
                    ])->with('settings_tab', 'appointments');
                }
            }
        }

        DB::transaction(function () use ($validated): void {
            $calendar = DgAppointmentCalendar::query()->lockForUpdate()->first();
            if (!$calendar) {
                $calendar = DgAppointmentCalendar::create(['dg_user_id' => auth()->id()]);
            } elseif ((string) $calendar->dg_user_id !== (string) auth()->id()) {
                $calendar->dg_user_id = auth()->id();
                $calendar->save();
            }

            foreach (self::WEEKDAYS as $weekday => $label) {
                $day = $validated['days'][$weekday] ?? [];
                DgAppointmentAvailability::where('dg_user_id', $calendar->dg_user_id)->where('weekday', $weekday)->delete();
                foreach (($day['ranges'] ?? []) as $range) {
                    if (!empty($range['starts_at']) && !empty($range['ends_at'])) {
                        DgAppointmentAvailability::create([
                            'dg_user_id' => $calendar->dg_user_id,
                            'weekday' => $weekday,
                            'starts_at' => $range['starts_at'],
                            'ends_at' => $range['ends_at'],
                        ]);
                    }
                }
            }
        });

        return back()->with('appointment_settings_success', 'Les disponibilités hebdomadaires du DG ont été enregistrées.')
            ->with('settings_tab', 'appointments');
    }

    public function index(): View
    {
        $calendar = DgAppointmentCalendar::with('dg')->first();
        $appointments = $calendar ? $this->appointmentsForCalendar($calendar->dg_user_id, false) : collect();
        $pastAppointments = $calendar ? $this->appointmentsForCalendar($calendar->dg_user_id, true) : collect();

        return view('appointments.index', compact('calendar', 'appointments', 'pastAppointments'));
    }

    public function upcomingCount(): JsonResponse
    {
        $calendar = DgAppointmentCalendar::first();
        $count = $calendar
            ? $this->appointmentQuery($calendar->dg_user_id, false)->count()
            : 0;

        return response()->json(['count' => $count]);
    }

    private function appointmentsForCalendar(int $dgUserId, bool $past)
    {
        return $this->appointmentQuery($dgUserId, $past)->with('requester')
            ->orderBy('appointment_date')->orderBy('starts_at')->get();
    }

    private function appointmentQuery(int $dgUserId, bool $past)
    {
        $today = now()->toDateString();
        $time = now()->format('H:i:s');

        return DgAppointment::where('dg_user_id', $dgUserId)->where(function ($query) use ($past, $today, $time) {
            if ($past) {
                $query->whereDate('appointment_date', '<', $today)
                    ->orWhere(function ($sameDay) use ($today, $time) {
                        $sameDay->whereDate('appointment_date', $today)->where('ends_at', '<=', $time);
                    });
                return;
            }

            $query->whereDate('appointment_date', '>', $today)
                ->orWhere(function ($sameDay) use ($today, $time) {
                    $sameDay->whereDate('appointment_date', $today)->where('ends_at', '>', $time);
                });
        });
    }

    public function availableSlots(Request $request): JsonResponse
    {
        $validated = $request->validate(['date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today']]);
        $calendar = DgAppointmentCalendar::first();
        if (!$calendar) {
            return response()->json(['ranges' => [], 'message' => 'Le DG n’a pas encore configuré ses disponibilités.']);
        }

        $date = Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay();
        $availabilityRanges = DgAppointmentAvailability::where('dg_user_id', $calendar->dg_user_id)
            ->where('weekday', $date->dayOfWeek)->orderBy('starts_at')->get();
        if ($availabilityRanges->isEmpty()) {
            return response()->json(['ranges' => [], 'message' => 'Le DG n’est pas disponible ce jour-là.']);
        }

        $existing = DgAppointment::where('dg_user_id', $calendar->dg_user_id)
            ->whereDate('appointment_date', $date->toDateString())->get(['starts_at', 'ends_at']);
        $freeRanges = [];
        foreach ($availabilityRanges as $availability) {
            $cursor = Carbon::parse($date->toDateString().' '.$availability->starts_at);
            $rangeEnd = Carbon::parse($date->toDateString().' '.$availability->ends_at);
            foreach ($existing->sortBy('starts_at') as $appointment) {
                $busyStart = Carbon::parse($date->toDateString().' '.$appointment->starts_at);
                $busyEnd = Carbon::parse($date->toDateString().' '.$appointment->ends_at);
                if ($busyEnd->lte($cursor) || $busyStart->gte($rangeEnd)) {
                    continue;
                }
                if ($busyStart->gt($cursor)) {
                    $freeEnd = $busyStart->lt($rangeEnd) ? $busyStart : $rangeEnd;
                    $freeRanges[] = [$cursor->copy(), $freeEnd->copy()];
                }
                if ($busyEnd->gt($cursor)) {
                    $cursor = $busyEnd->copy();
                }
                if ($cursor->gte($rangeEnd)) {
                    break;
                }
            }
            if ($cursor->lt($rangeEnd)) {
                $freeRanges[] = [$cursor, $rangeEnd];
            }
        }

        $ranges = [];
        foreach ($freeRanges as [$rangeStart, $rangeEnd]) {
            if ($date->isToday() && $rangeStart->lt(now())) {
                $rangeStart = now()->copy();
            }
            if ($rangeStart->lt($rangeEnd)) {
                $ranges[] = ['starts_at' => $rangeStart->format('H:i'), 'ends_at' => $rangeEnd->format('H:i')];
            }
        }

        return response()->json([
            'ranges' => $ranges,
            'message' => $ranges ? null : 'Aucune plage n’est libre à cette date. Choisis un autre jour.',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'visitor_name' => ['required', 'string', 'max:150'],
            'visitor_phone' => ['required', 'string', 'max:40'],
            'subject' => ['required', 'string', 'max:500'],
            'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
        ]);

        $calendar = DgAppointmentCalendar::first();
        if (!$calendar) {
            return back()->withInput()->withErrors(['starts_at' => 'Le DG n’a pas encore configuré ses disponibilités.']);
        }

        $start = Carbon::createFromFormat('Y-m-d H:i', $validated['appointment_date'].' '.$validated['starts_at']);
        $end = Carbon::createFromFormat('Y-m-d H:i', $validated['appointment_date'].' '.$validated['ends_at']);
        $booked = DB::transaction(function () use ($calendar, $validated, $start, $end) {
            // Lock the single calendar row so simultaneous requests cannot book the same slot.
            $lockedCalendar = DgAppointmentCalendar::whereKey($calendar->id)->lockForUpdate()->first();
            $weekday = $start->dayOfWeek;
            $availabilityRanges = DgAppointmentAvailability::where('dg_user_id', $lockedCalendar->dg_user_id)
                ->where('weekday', $weekday)->get();
            if ($availabilityRanges->isEmpty()) {
                return 'unavailable';
            }

            $insideAvailability = $availabilityRanges->contains(function ($availability) use ($start, $end) {
                $dayStart = Carbon::parse($start->toDateString().' '.$availability->starts_at);
                $dayEnd = Carbon::parse($start->toDateString().' '.$availability->ends_at);
                return $start->gte($dayStart) && $end->lte($dayEnd);
            });
            if (!$insideAvailability) {
                return 'unavailable';
            }
            if ($start->isPast()) {
                return 'past';
            }

            $conflict = DgAppointment::where('dg_user_id', $lockedCalendar->dg_user_id)
                ->whereDate('appointment_date', $validated['appointment_date'])
                ->where('starts_at', '<', $end->format('H:i:s'))
                ->where('ends_at', '>', $start->format('H:i:s'))
                ->exists();
            if ($conflict) {
                return 'conflict';
            }

            $appointment = DgAppointment::create([
                'dg_user_id' => $lockedCalendar->dg_user_id,
                'created_by' => auth()->id(),
                'visitor_name' => $validated['visitor_name'],
                'visitor_phone' => $validated['visitor_phone'],
                'subject' => $validated['subject'],
                'appointment_date' => $validated['appointment_date'],
                'starts_at' => $start->format('H:i:s'),
                'ends_at' => $end->format('H:i:s'),
            ]);
            $dg = User::find($lockedCalendar->dg_user_id);
            if ($dg) {
                $dg->notify(new DgAppointmentNotification($appointment, auth()->user()->name));
            }
            return 'created';
        });

        if ($booked === 'conflict') {
            return back()->withInput()->withErrors(['starts_at' => 'Ce créneau vient d’être réservé. Choisis une autre heure disponible.']);
        }
        if ($booked === 'past') {
            return back()->withInput()->withErrors(['starts_at' => 'Cette heure est déjà passée. Choisis un créneau futur.']);
        }
        if ($booked !== 'created') {
            return back()->withInput()->withErrors(['starts_at' => 'Le DG n’est pas disponible sur ce créneau. Choisis une heure affichée comme libre.']);
        }

        return redirect()->route('dg-appointments.index')->with('appointment_success', 'Le rendez-vous avec le DG a été planifié.');
    }
}
