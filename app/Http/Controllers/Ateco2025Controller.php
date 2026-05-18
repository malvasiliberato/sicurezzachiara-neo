<?php

namespace App\Http\Controllers;

use App\Models\Ateco2025;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Ateco2025Controller extends Controller
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

        $results = Ateco2025::query()
            ->where('livello', 6)
            ->where(function ($query) use ($term) {
                $query->where('codice', 'like', '%'.$term.'%')
                    ->orWhere('titolo_it', 'ilike', '%'.$term.'%');
            })
            ->orderByRaw(
                "case
                    when lower(codice) = ? then 0
                    when lower(codice) like ? then 1
                    when lower(titolo_it) like ? then 2
                    else 3
                end",
                [
                    $normalized,
                    $normalized.'%',
                    $normalized.'%',
                ]
            )
            ->orderBy('codice')
            ->limit(12)
            ->get(['id', 'codice', 'titolo_it'])
            ->map(fn (Ateco2025 $entry) => [
                'id' => $entry->id,
                'code' => $entry->codice,
                'title' => $entry->titolo_it,
                'label' => $entry->codice.' - '.$entry->titolo_it,
            ])
            ->values();

        return response()->json([
            'results' => $results,
        ]);
    }
};
