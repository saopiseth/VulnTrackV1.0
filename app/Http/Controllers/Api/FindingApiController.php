<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VulnFinding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindingApiController extends Controller
{
    private const SEVERITY_ORDER = ['Critical' => 0, 'High' => 1, 'Medium' => 2, 'Low' => 3, 'Info' => 4];

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'severity'      => ['nullable', 'in:Critical,High,Medium,Low,Info'],
            'search'        => ['nullable', 'string', 'max:100'],
            'assessment_id' => ['nullable', 'integer', 'exists:vuln_assessments,id'],
            'cursor'        => ['nullable', 'string'],
        ]);

        $query = VulnFinding::select([
            'id', 'vuln_name', 'severity', 'ip_address', 'hostname',
            'cve', 'cvss_score', 'plugin_id', 'assessment_id',
            'vuln_category', 'port', 'protocol', 'created_at',
        ])->orderByDesc('id');

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('assessment_id')) {
            $query->where('assessment_id', $request->assessment_id);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('vuln_name',   'like', "%{$term}%")
                  ->orWhere('ip_address', 'like', "%{$term}%")
                  ->orWhere('cve',        'like', "%{$term}%")
                  ->orWhere('hostname',   'like', "%{$term}%");
            });
        }

        $paginated = $query->cursorPaginate(20);

        return response()->json([
            'data'        => $paginated->items(),
            'next_cursor' => $paginated->nextCursor()?->encode(),
            'has_more'    => $paginated->hasMorePages(),
            'total_shown' => count($paginated->items()),
        ]);
    }

    public function show(VulnFinding $finding): JsonResponse
    {
        $finding->load([
            'scan:id,filename,is_baseline,created_at',
            'assessment:id,uuid,name',
        ]);

        return response()->json($finding);
    }
}
