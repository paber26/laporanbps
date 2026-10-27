<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Profil pegawai yang terhubung dengan akun ini.
     */
    public function pegawai(): HasOne
    {
        return $this->hasOne(Pegawai::class);
    }

    /**
     * Laporan yang dibuat/dimiliki akun ini.
     */
    public function laporans(): HasMany
    {
        return $this->hasMany(Laporan::class);
    }

    /**
     * KAK yang dibuat/dimiliki akun ini.
     */
    public function kaks(): HasMany
    {
        return $this->hasMany(Kak::class);
    }

    /**
     * Apakah pengguna ini administrator (oversight penuh).
     *
     * Catatan: kolom `role` sengaja tidak masuk $fillable agar tidak bisa
     * diubah lewat mass-assignment (mis. form registrasi).
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
