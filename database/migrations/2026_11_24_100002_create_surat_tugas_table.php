<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Surat Tugas (ST) Pegawai Organik + SPD + Visum SPD (3 halaman lengkap)
     * mengikuti format Template ST Pegawai Organik.docx BPS Minahasa Selatan.
     */
    public function up(): void
    {
        Schema::create('surat_tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pegawai_id')->constrained('pegawais');
            $table->foreignId('pembiayaan_id')->nullable()->constrained('master_pembiayaans')->nullOnDelete();

            // Identitas & Penomoran ST / SPD
            $table->string('nomor_st_urut', 50);
            $table->string('kode_klasifikasi', 50)->default('KP.650');
            $table->unsignedSmallInteger('tahun');
            $table->string('nomor_st');
            $table->string('nomor_spd')->nullable();
            $table->string('tempat_st')->default('Minahasa Selatan');
            $table->date('tanggal_st');

            // Isi Surat Tugas (Halaman 1)
            $table->text('menimbang');
            $table->text('mengingat');
            $table->text('tujuan_kegiatan');
            $table->string('waktu_pelaksanaan');

            // Snapshot Pegawai & Detail SPD (Halaman 2)
            $table->string('jabatan')->nullable();
            $table->string('pangkat_golongan')->nullable();
            $table->string('unit_kerja')->default('BPS Kabupaten Minahasa Selatan');
            $table->string('tingkat_biaya', 20)->default('C');
            $table->string('alat_angkutan')->default('Lainnya');
            $table->string('tempat_berangkat')->default('Kabupaten Minahasa Selatan');
            $table->string('tempat_tujuan')->default('Kabupaten Minahasa Selatan');
            $table->string('kecamatan_asal')->default('Amurang Barat');
            $table->unsignedSmallInteger('jumlah_hari')->default(1);
            $table->date('tanggal_berangkat');
            $table->date('tanggal_kembali');
            $table->text('pengikut')->nullable();
            $table->text('keterangan_lain')->nullable();
            $table->text('tembusan')->nullable();

            // Pembebanan Anggaran (Poin 9 SPD & Mata Anggaran ST)
            $table->string('mata_anggaran')->nullable();
            $table->string('program_kode', 50)->nullable();
            $table->string('program_nama')->nullable();
            $table->string('kegiatan_kode', 50)->nullable();
            $table->string('kegiatan_nama')->nullable();
            $table->string('komponen_kode', 50)->nullable();
            $table->string('komponen_nama')->nullable();
            $table->string('instansi_anggaran')->default('Badan Pusat Statistik Minahasa Selatan');

            // Pejabat Penandatangan (default dari config, dapat di-override per ST)
            $table->string('kepala_jabatan');
            $table->string('kepala_nama');
            $table->string('kepala_nip');
            $table->string('ppk_jabatan');
            $table->string('ppk_sub_jabatan')->nullable();
            $table->string('ppk_nama');
            $table->string('ppk_nip');
            $table->boolean('sertakan_ttd_kepala')->default(false);

            $table->timestamps();

            $table->index('tanggal_st');
            $table->index('tanggal_berangkat');
            $table->index('pegawai_id');
        });

        Schema::create('surat_tugas_rutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_tugas_id')->constrained('surat_tugas')->cascadeOnDelete();
            $table->unsignedTinyInteger('urutan')->default(1);
            $table->string('kecamatan_tujuan');
            $table->date('tanggal_tiba');
            $table->date('tanggal_berangkat')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_tugas_rutes');
        Schema::dropIfExists('surat_tugas');
    }
};
