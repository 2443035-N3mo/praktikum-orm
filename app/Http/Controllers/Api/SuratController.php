<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusSurat;
use App\Http\Controllers\Controller;
use App\Models\Surat;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuratController extends Controller
{
    /** GET /api/v1/surat?status=diajukan */
    public function index(Request $request)
    {
        $request->validate(['status' => ['nullable', Rule::enum(StatusSurat::class)]]);

        return Surat::query()
            ->with(['mahasiswa:id,nim,nama', 'jenisSurat:id,nama'])
            ->when($request->status, fn ($q, $s) => $q->status(StatusSurat::from($s)))
            ->latest('id')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    /** POST /api/v1/surat */
    public function store(Request $request)
    {
        $data = $request->validate([
            'mahasiswa_id'   => ['required', 'integer', 'exists:mahasiswa,id'],
            'jenis_surat_id' => ['required', 'integer', 'exists:jenis_surat,id'],
            'keperluan'      => ['required', 'string', 'min:10'],
        ]);

        $surat = Surat::ajukan($data);

        return response()->json(['data' => $surat->load('mahasiswa', 'jenisSurat', 'riwayat')], 201);
    }

    /** GET /api/v1/surat/{surat} */
    public function show(Surat $surat)
    {
    return ['data' => $surat->load('mahasiswa', 'jenisSurat', 'riwayat', 'disetujuiOleh')];
    }

    /** PATCH /api/v1/surat/{surat}/status */
    public function ubahStatus(Request $request, Surat $surat)
    {
        $data = $request->validate([
            'status'  => ['required', Rule::enum(StatusSurat::class)],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $surat->ubahStatus(StatusSurat::from($data['status']), $data['catatan'] ?? null);
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return ['data' => $surat->load('riwayat')];
    }

        /** GET /api/v1/surat-terhapus */
    public function terhapus(Request $request)
    {
        return Surat::onlyTrashed()
            ->with(['mahasiswa:id,nim,nama', 'jenisSurat:id,nama'])
            ->latest('deleted_at')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

        /** POST /api/v1/surat/{id}/pulihkan */
    public function pulihkan(int $id)
    {
        // {surat} biasa tidak menemukan surat terhapus, jadi dicari manual
        $surat = Surat::onlyTrashed()->findOrFail($id);
        $surat->restore();

        return ['data' => $surat->load('mahasiswa', 'jenisSurat', 'riwayat')];
    }
    
    /** DELETE /api/v1/surat/{surat} */
    public function destroy(Surat $surat)
    {
        $surat->delete();

        return response()->noContent();
    }
}