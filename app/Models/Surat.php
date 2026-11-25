<?php

namespace App\Models;

use App\Enums\StatusSurat;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Surat extends Model
{
    protected $table = 'surat';

    protected $fillable = ['mahasiswa_id', 'jenis_surat_id', 'keperluan'];

    protected $attributes = ['status' => 'diajukan'];

    protected function casts(): array
    {
        return ['status' => StatusSurat::class];
    }

    // ---------------- RELASI ----------------

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function jenisSurat(): BelongsTo
    {
        return $this->belongsTo(JenisSurat::class);
    }

    public function show(Surat $surat)
    {
    return ['data' => $surat->load('mahasiswa', 'jenisSurat', 'riwayat', 'disetujuiOleh')];
    }

    public function disetujuiOleh(): BelongsTo
    {
    return $this->belongsTo(Dosen::class, 'disetujui_oleh');
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(RiwayatStatus::class)->orderBy('id');
    }

    // ---------------- QUERY SCOPE ----------------

    public function scopeStatus(Builder $query, StatusSurat $status): void
    {
        $query->where('status', $status->value);
    }

    // ---------------- ATURAN BISNIS ----------------

    public static function ajukan(array $data): self
    {
        return DB::transaction(function () use ($data) {
            $surat = self::create($data);

            $surat->nomor = sprintf('SRT/%04d/%s', $surat->id, now()->year);
            $surat->save();

            $surat->riwayat()->create([
                'status'  => StatusSurat::Diajukan,
                'catatan' => 'Pengajuan dibuat oleh mahasiswa.',
            ]);

            return $surat;
        });
    }

    public function ubahStatus(StatusSurat $baru, ?string $catatan = null): void
    {
        if (! $this->status->bisaBerubahKe($baru)) {
            throw new DomainException(
                "Status {$this->status->label()} tidak bisa diubah ke {$baru->label()}."
            );
        }

        DB::transaction(function () use ($baru, $catatan) {
            $this->status = $baru;
            $this->save();

            $this->riwayat()->create(['status' => $baru, 'catatan' => $catatan]);
        });
    }
}