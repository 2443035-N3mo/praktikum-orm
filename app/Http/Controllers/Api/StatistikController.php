<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JenisSurat;
use App\Models\Surat;

class StatistikController extends Controller
{
    /** GET /api/v1/statistik */
    public function __invoke()
    {
        // 1 query: SELECT status, count(*) AS jumlah FROM surat GROUP BY status
        $perStatus = Surat::selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        // 1 query: jumlah surat per jenis (withCount), semua jenis tampil termasuk yang 0
        $perJenis = JenisSurat::withCount('surat')
            ->orderBy('kode')
            ->get()
            ->map(fn ($j) => [
                'kode'   => $j->kode,
                'nama'   => $j->nama,
                'jumlah' => $j->surat_count,
            ]);

        return ['data' => [
            'total_surat' => Surat::count(),
            'per_status'  => $perStatus,
            'per_jenis'   => $perJenis,
        ]];
    }
}