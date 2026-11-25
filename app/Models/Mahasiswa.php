<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mahasiswa extends Model
{
    use HasFactory;
    protected $table = 'mahasiswa';

    protected $fillable = ['nim', 'nama', 'prodi', 'angkatan'];

    protected function casts(): array
    {
        return ['angkatan' => 'integer'];
    }

    public function surat(): HasMany
    {
        return $this->hasMany(Surat::class);
    }
}