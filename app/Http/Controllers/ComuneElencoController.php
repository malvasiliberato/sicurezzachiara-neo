<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComuneElencoController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->string('q')->value());

        if (mb_strlen($term) < 2) {
            return response()->json([
                'results' => [],
            ]);
        }

        $normalized = mb_strtolower($term);
        $numericTerm = preg_match('/^\d{3,5}$/', $term) ? (int) $term : null;

        $query = DB::table('comuni_elenco')
            ->select([
                'comune',
                'provincia',
                'provincia_esteso',
                DB::raw('MIN(istat) as istat'),
                DB::raw("ARRAY_AGG(DISTINCT cap ORDER BY cap) as caps"),
            ])
            ->where(function ($query) use ($term) {
                $query->where('comune', 'ilike', '%'.$term.'%')
                    ->orWhere('cap', 'like', '%'.$term.'%');
            })
            ->when($numericTerm !== null, function ($query) use ($numericTerm) {
                $query->orWhere(function ($rangeQuery) use ($numericTerm) {
                    $rangeQuery->where('cap', 'like', '%-%')
                        ->whereRaw('CAST(split_part(cap, \'-\', 1) AS INTEGER) <= ?', [$numericTerm])
                        ->whereRaw('CAST(split_part(cap, \'-\', 2) AS INTEGER) >= ?', [$numericTerm]);
                });
            })
            ->groupBy('comune', 'provincia', 'provincia_esteso');

        if ($numericTerm !== null) {
            $query->orderByRaw(
                "case
                    when exists (
                        select 1 from comuni_elenco ce3
                        where ce3.comune = comuni_elenco.comune
                          and ce3.provincia = comuni_elenco.provincia
                          and ce3.cap like '%-%'
                          and CAST(split_part(ce3.cap, '-', 1) AS INTEGER) <= ?
                          and CAST(split_part(ce3.cap, '-', 2) AS INTEGER) >= ?
                    ) then 0
                    when exists (
                        select 1 from comuni_elenco ce2
                        where ce2.comune = comuni_elenco.comune
                          and ce2.provincia = comuni_elenco.provincia
                          and ce2.cap like ?
                    ) then 1
                    else 2
                end",
                [
                    $numericTerm,
                    $numericTerm,
                    $term.'%',
                ]
            );
        }

        $results = $query
            ->orderByRaw(
                "case
                    when lower(comune) = ? then 0
                    when lower(comune) like ? then 1
                    else 2
                end",
                [
                    $normalized,
                    $normalized.'%',
                ]
            )
            ->orderBy('comune')
            ->limit(12)
            ->get()
            ->map(function ($row) {
                $caps = $this->normalizeCaps($row->caps ?? []);

                return [
                    'id' => (int) $row->istat,
                    'city' => $row->comune,
                    'province' => $row->provincia,
                    'provinceLabel' => $row->provincia_esteso,
                    'caps' => $caps,
                    'label' => $row->comune.' ('.$row->provincia.')',
                    'capLabel' => $this->formatCapLabel($caps),
                ];
            })
            ->values();

        return response()->json([
            'results' => $results,
            ]);
    }

    private function normalizeCaps(mixed $caps): array
    {
        if (is_array($caps)) {
            return collect($caps)
                ->flatMap(fn ($cap) => $this->expandCapValue($cap))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (is_string($caps)) {
            return collect(explode(',', trim($caps, '{}')))
                ->map(fn ($cap) => trim($cap, '" '))
                ->flatMap(fn ($cap) => $this->expandCapValue($cap))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return [];
    }

    private function expandCapValue(mixed $value): array
    {
        $cap = trim((string) $value);

        if ($cap === '') {
            return [];
        }

        if (! str_contains($cap, '-')) {
            return [$cap];
        }

        [$from, $to] = array_map('trim', explode('-', $cap, 2));

        if (! ctype_digit($from) || ! ctype_digit($to)) {
            return [$cap];
        }

        $start = (int) $from;
        $end = (int) $to;

        if ($start > $end || ($end - $start) > 200) {
            return [$cap];
        }

        return collect(range($start, $end))
            ->map(fn ($number) => str_pad((string) $number, max(strlen($from), strlen($to)), '0', STR_PAD_LEFT))
            ->all();
    }

    private function formatCapLabel(array $caps): string
    {
        if ($caps === []) {
            return '';
        }

        if (count($caps) === 1) {
            return $caps[0];
        }

        return $caps[0].' - '.$caps[array_key_last($caps)];
    }
}
