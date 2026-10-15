<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\PlanPromotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index()
    {
        $tableMissing = false;
        try {
            $promotions = PlanPromotion::orderByDesc('starts_at')->get();
        } catch (\Throwable $e) {
            // plan_promotions table not migrated yet on the landlord DB
            $promotions   = collect();
            $tableMissing = true;
        }
        $current = PlanPromotion::current(); // already guarded internally
        return view('superadmin.promotions.index', compact('promotions', 'current', 'tableMissing'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        PlanPromotion::create($data);

        return redirect()->route('superadmin.promotions.index')
            ->with('message', 'Promotion created. Discounted prices are now live on the landing page while the promotion is active.');
    }

    public function update(Request $request, $id)
    {
        $promotion = PlanPromotion::findOrFail($id);
        $promotion->update($this->validated($request));

        return redirect()->route('superadmin.promotions.index')
            ->with('message', 'Promotion updated successfully.');
    }

    public function toggle($id)
    {
        $promotion = PlanPromotion::findOrFail($id);
        $promotion->update(['is_active' => ! $promotion->is_active]);

        return redirect()->route('superadmin.promotions.index')
            ->with('message', 'Promotion status updated.');
    }

    public function destroy($id)
    {
        PlanPromotion::findOrFail($id)->delete();

        return redirect()->route('superadmin.promotions.index')
            ->with('message', 'Promotion deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title'          => 'required|string|max:120',
            'badge_text'     => 'nullable|string|max:40',
            'description'    => 'nullable|string|max:2000',
            'discount_type'  => 'required|in:percentage,flat',
            'discount_value' => 'required|numeric|min:0',
            'starts_at'      => 'required|date',
            'ends_at'        => 'required|date|after:starts_at',
            'show_modal'     => 'boolean',
            'is_active'      => 'boolean',
        ]);

        if ($data['discount_type'] === 'percentage' && $data['discount_value'] > 100) {
            $data['discount_value'] = 100;
        }

        $data['show_modal'] = $request->boolean('show_modal');
        $data['is_active']  = $request->boolean('is_active');

        return $data;
    }
}
