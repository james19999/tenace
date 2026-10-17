<?php

namespace App\Http\Controllers;

use App\Models\CostumerContactHistory;
use App\Models\CostumerContactSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicReviewController extends Controller
{
    public function index(): View
    {
        $settings = CostumerContactSetting::query()->first();
        $phoneMode = in_array($settings?->public_review_phone_mode, ['hidden', 'mask_last_two', 'full'], true)
            ? $settings->public_review_phone_mode
            : 'hidden';

        $reviews = CostumerContactHistory::query()
            ->with(['costumer:id,name,phone'])
            ->whereNotNull('responded_at')
            ->where('sentiment', 'positive')
            ->whereNotNull('response')
            ->whereRaw("TRIM(response) <> ''")
            ->orderByDesc('responded_at')
            ->paginate(18);

        $reviews->getCollection()->transform(function (CostumerContactHistory $review) use ($phoneMode) {
            $customerName = trim((string) ($review->costumer?->name ?? ''));
            $review->public_customer_name = $customerName !== ''
                ? (preg_split('/\s+/', $customerName)[0] ?? 'Cliente')
                : 'Cliente';

            $phone = trim((string) ($review->costumer?->phone ?? ''));
            $review->public_phone = match ($phoneMode) {
                'full' => $phone !== '' ? $phone : null,
                'mask_last_two' => $phone !== '' ? preg_replace_callback('/\d+/', function (array $match): string {
                    $digits = $match[0];
                    return strlen($digits) > 2
                        ? substr($digits, 0, -2).'••'
                        : str_repeat('•', strlen($digits));
                }, $phone) : null,
                default => null,
            };

            return $review;
        });

        return view('public.reviews.index', [
            'reviews' => $reviews,
            'phoneMode' => $phoneMode,
            'canConfigure' => auth()->user()?->isAdmin() ?? false,
        ]);
    }

    public function updatePhoneDisplay(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'public_review_phone_mode' => ['required', 'in:hidden,mask_last_two,full'],
        ]);

        $settings = CostumerContactSetting::query()->firstOrNew();
        $settings->fill($data)->save();

        return redirect()->route('public-reviews.index')->with('review_settings_saved', 'La visibilité des numéros a été mise à jour.');
    }
}
