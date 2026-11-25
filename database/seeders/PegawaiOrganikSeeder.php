<?php

namespace Database\Seeders;

use App\Models\Pegawai;
use Illuminate\Database\Seeder;

/**
 * Sinkronisasi data 29 Pegawai Organik BPS Kabupaten Minahasa Selatan
 * (bersumber dari sheet "Organik" pada Database Translok 2026.xlsx).
 *
 * Idempoten: mencocokkan NIP baik berspasi maupun tanpa spasi agar tidak
 * membuat duplikat pada data pegawai yang sudah tersimpan sebelumnya.
 */
class PegawaiOrganikSeeder extends Seeder
{
    public function run(): void
    {
        $unitKerja = config('laporan.surat_tugas.instansi', 'BPS Kabupaten Minahasa Selatan');

        $organiks = [
            ['nama' => 'Irena Listianawati, SST, SE, M.Si', 'nip' => '19810706 200312 2 005', 'jabatan' => 'Kepala', 'pangkat_golongan' => 'Pembina Tk.I (IV/b)'],
            ['nama' => 'Sri Endang T. Karim, SE', 'nip' => '19700914 199203 2 001', 'jabatan' => 'Kepala Subbagian Umum', 'pangkat_golongan' => 'Penata Tk.I (III/d)'],
            ['nama' => 'Johanes, S.ST', 'nip' => '19800511 200212 1 003', 'jabatan' => 'Statistisi Ahli Madya', 'pangkat_golongan' => 'Penata Tk.I (III/d)'],
            ['nama' => 'Jimmy Ferdinan Mamahani, SE', 'nip' => '19840609 201003 1 001', 'jabatan' => 'Statistisi Ahli Muda', 'pangkat_golongan' => 'Penata Tk.I (III/d)'],
            ['nama' => 'Frenly Wongkar, S.Si', 'nip' => '19860207 201003 1 004', 'jabatan' => 'Statistisi Ahli Muda', 'pangkat_golongan' => 'Penata Tk.I (III/d)'],
            ['nama' => 'Ireyne Tamburian, S.Sos', 'nip' => '19830716 201003 2 002', 'jabatan' => 'Statistisi Ahli Muda', 'pangkat_golongan' => 'Penata Tk.I (III/d)'],
            ['nama' => 'Widhita Krisnahapsari, S.ST, M.SE', 'nip' => '19870430 200912 2 007', 'jabatan' => 'Statistisi Ahli Muda', 'pangkat_golongan' => 'Penata Tk.I (III/d)'],
            ['nama' => 'Meity Chintya Sinadia, SE', 'nip' => '19860526 201003 2 001', 'jabatan' => 'Statistisi Ahli Muda', 'pangkat_golongan' => 'Penata Tk.I (III/d)'],
            ['nama' => 'Diane Roosjefien Rondonuwu, S.P.', 'nip' => '19701211 201410 2 002', 'jabatan' => 'Statistisi Ahli Muda', 'pangkat_golongan' => 'Penata (III/c)'],
            ['nama' => 'Rafif Rikasatya, S.ST', 'nip' => '19950524 201802 1 001', 'jabatan' => 'Statistisi Ahli Muda', 'pangkat_golongan' => 'Penata (III/c)'],
            ['nama' => 'Desnacita Harly Putri, S.ST', 'nip' => '19951221 201802 2 002', 'jabatan' => 'Statistisi Ahli Pertama', 'pangkat_golongan' => 'Penata Muda Tk.I (III/b)'],
            ['nama' => 'Afwin Fauzy Akhsan S.Tr.Stat.', 'nip' => '19960922 201901 1 002', 'jabatan' => 'Statistisi Ahli Pertama', 'pangkat_golongan' => 'Penata Muda Tk.I (III/b)'],
            ['nama' => 'Yunanda Angelia Sinurat S.Tr.Stat.', 'nip' => '19960607 201901 2 002', 'jabatan' => 'Statistisi Ahli Pertama', 'pangkat_golongan' => 'Penata Muda Tk.I (III/b)'],
            ['nama' => 'Artha Gumelar Suharsa, S.Tr.Stat', 'nip' => '19951221 201912 1 001', 'jabatan' => 'Statistisi Ahli Pertama', 'pangkat_golongan' => 'Penata Muda Tk.I (III/b)'],
            ['nama' => 'Syahfianti Inung Pratiwi, S.Tr.Stat.', 'nip' => '19980218 202104 2 001', 'jabatan' => 'Statistisi Ahli Pertama', 'pangkat_golongan' => 'Penata Muda (III/a)'],
            ['nama' => 'Melati Sukmaningtyas, S.Tr.Stat', 'nip' => '19980620 202104 2 003', 'jabatan' => 'Statistisi Ahli Pertama', 'pangkat_golongan' => 'Penata Muda Tk.I (III/b)'],
            ['nama' => 'Pidyatama Putri Situmorang S.Tr.Stat.', 'nip' => '20000920 202201 2 006', 'jabatan' => 'Statistisi Ahli Pertama', 'pangkat_golongan' => 'Penata Muda (III/a)'],
            ['nama' => 'Vincentius Agra Ananta Gunito, S.Tr.Stat', 'nip' => '20000410 202302 1 002', 'jabatan' => 'Statistisi Ahli Pertama', 'pangkat_golongan' => 'Penata Muda (III/a)'],
            ['nama' => 'Steven Bonny Reppi, SE', 'nip' => '19720926 199603 1 002', 'jabatan' => 'Statistisi Ahli Pertama', 'pangkat_golongan' => 'Penata (III/c)'],
            ['nama' => 'Kurniawan Walangitan, SE', 'nip' => '19830131 200604 1 005', 'jabatan' => 'Statistisi Ahli Pertama', 'pangkat_golongan' => 'Penata Tk.I (III/d)'],
            ['nama' => 'Bernaldo Napitupulu, S.Tr.Stat', 'nip' => '20001111 202302 1 002', 'jabatan' => 'Pranata Komputer Pertama', 'pangkat_golongan' => 'Penata Muda (III/a)'],
            ['nama' => 'Dodik Setyawan, S.Tr.Stat', 'nip' => '20001129 202310 1 004', 'jabatan' => 'Pranata Komputer Pertama', 'pangkat_golongan' => 'Penata Muda (III/a)'],
            ['nama' => 'Alfie Gustaf Paul Sunkudon, S.Pd', 'nip' => '19710811 201410 1 001', 'jabatan' => 'Statistisi Penyelia', 'pangkat_golongan' => 'Penata (III/c)'],
            ['nama' => 'Joice Rorimpandey A.Md', 'nip' => '19850129 201003 2 002', 'jabatan' => 'Statistisi Mahir', 'pangkat_golongan' => 'Penata Muda Tk.I (III/b)'],
            ['nama' => 'Jelly Jevry Tapada, A.Md', 'nip' => '19760715 202521 1 008', 'jabatan' => 'Pengelola Layanan Operasional', 'pangkat_golongan' => 'VII'],
            ['nama' => 'Hendra Peddro Riekho Sunkudon', 'nip' => '19780319 202521 1 002', 'jabatan' => 'Pengelola Layanan Operasional', 'pangkat_golongan' => 'V'],
            ['nama' => 'Frensix Katihokang', 'nip' => '19951105 202521 1 038', 'jabatan' => 'Pengelola Layanan Operasional', 'pangkat_golongan' => null],
            ['nama' => 'Daniel Albert Manaf', 'nip' => '19991107 202521 1 013', 'jabatan' => 'Pengelola Layanan Operasional', 'pangkat_golongan' => null],
            ['nama' => 'Sem Ham Lumempow', 'nip' => '19730910 202521 1 025', 'jabatan' => 'Pengelola Layanan Operasional', 'pangkat_golongan' => null],
        ];

        foreach ($organiks as $row) {
            $nipNoSpace = preg_replace('/\s+/', '', $row['nip']);

            $existing = Pegawai::query()
                ->where('nip', $row['nip'])
                ->orWhere('nip', $nipNoSpace)
                ->first();

            if ($existing) {
                $existing->update([
                    'nama' => $row['nama'],
                    'jabatan' => $row['jabatan'],
                    'pangkat_golongan' => $row['pangkat_golongan'],
                    'unit_kerja' => $existing->unit_kerja ?: $unitKerja,
                ]);
            } else {
                Pegawai::create([
                    'nip' => $row['nip'],
                    'nama' => $row['nama'],
                    'jabatan' => $row['jabatan'],
                    'pangkat_golongan' => $row['pangkat_golongan'],
                    'unit_kerja' => $unitKerja,
                ]);
            }
        }
    }
}
