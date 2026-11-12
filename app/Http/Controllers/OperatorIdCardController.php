<?php

namespace App\Http\Controllers;

use App\Models\WorkPermit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OperatorIdCardController extends Controller
{
    /** Show approved permits that have not been printed yet. */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $permits = $this->filteredPermits($search)
            ->whereNull('printed_at')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('operator.id-cards.index', compact('permits', 'search'));
    }

    /** Show approved permits that have already been printed. */
    public function printed(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $permits = $this->filteredPermits($search)
            ->whereNotNull('printed_at')
            ->orderByDesc('printed_at')
            ->paginate(10)
            ->withQueryString();

        return view('operator.id-cards.printed', compact('permits', 'search'));
    }

    /** Show a printable card only while the permit is approved. */
    public function show(WorkPermit $workPermit)
    {
        abort_unless($workPermit->status === 'diizinkan', 404);

        return view('operator.id-cards.show', compact('workPermit'));
    }

    /** Persist the operator's selected card zone, or follow the detected location zone. */
    public function updateZone(Request $request, WorkPermit $workPermit)
    {
        abort_unless($workPermit->status === 'diizinkan', 404);

        $zones = config('mitra.zones', []);
        $validated = $request->validate([
            'header_zone' => ['required', 'string', Rule::in(array_keys($zones))],
        ]);

        $selectedZone = $validated['header_zone'];
        $headerColor = $zones[$selectedZone]['color'] ?? null;

        $workPermit->forceFill([
            'id_card_header_color' => $headerColor,
        ])->save();

        return redirect()
            ->route('operator.id-cards.show', $workPermit)
            ->with(
                'success',
                'Zona kartu berhasil diubah ke ' . $zones[$selectedZone]['label'] . '.'
            );
    }

    /** Record the first time an approved worker card is marked as printed. */
    public function markPrinted(WorkPermit $workPermit)
    {
        abort_unless($workPermit->status === 'diizinkan', 404);

        $printedAt = now();
        $updated = WorkPermit::query()
            ->whereKey($workPermit->getKey())
            ->whereNull('printed_at')
            ->update([
                'printed_at' => $printedAt,
                'updated_at' => $printedAt,
            ]);

        $workPermit->refresh();

        return redirect()
            ->route('operator.id-cards.show', $workPermit)
            ->with(
                'success',
                $updated > 0
                    ? 'Kartu berhasil ditandai sudah dicetak.'
                    : 'Kartu ini sudah pernah ditandai dicetak pada ' . $workPermit->printed_at?->format('d/m/Y H:i') . '.'
            );
    }

    private function filteredPermits(string $search): Builder
    {
        return WorkPermit::query()
            ->where('status', 'diizinkan')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('job_title', 'like', "%{$search}%")
                        ->orWhere('work_location', 'like', "%{$search}%");
                });
            });
    }
}
