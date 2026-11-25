<?php

namespace App\Services;

use App\Models\SuratTugas;
use DOMDocument;
use DOMXPath;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;
use ZipArchive;

/**
 * Menghasilkan dokumen Word (.docx) Surat Tugas + SPD + Visum SPD (3 halaman)
 * berbasis template resmi BPS (resources/templates/template-st-organik.docx).
 */
class SuratTugasWordExporter
{
    public function generate(SuratTugas $suratTugas, string $ukuran = 'f4'): string
    {
        $suratTugas->loadMissing(['pegawai', 'rutes']);

        $baseTemplate = resource_path('templates/template-st-organik.docx');
        if (! is_file($baseTemplate)) {
            throw new RuntimeException('Template Word Surat Tugas tidak ditemukan di '.$baseTemplate);
        }

        $dir = storage_path('app/temp');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $rutes = $suratTugas->rutes->values();
        $workingTemplate = $baseTemplate;
        $tempWorking = null;

        // Bila rute tujuan lebih dari 2 (maks 4), gandakan tabel blok Visum
        // secara dinamis pada salinan sementara sebelum diproses TemplateProcessor.
        if ($rutes->count() > 2) {
            $tempWorking = $dir.'/st-tpl-'.$suratTugas->id.'-'.uniqid().'.docx';
            copy($baseTemplate, $tempWorking);
            $this->expandVisumBlocks($tempWorking, min($rutes->count(), 4));
            $workingTemplate = $tempWorking;
        }

        $tp = new TemplateProcessor($workingTemplate);

        // --- Halaman 1: Surat Tugas ---
        $tp->setValue('nomor_st', $suratTugas->nomor_st);
        $tp->setValue('menimbang', $suratTugas->menimbang);

        // Gunakan penanda sementara yang akan diganti menjadi paragraf bernomor
        // dengan hanging-indent rapi (rata kanan-kiri) pada post-processing XML.
        $mengingatToken = '__MENGINGAT_LIST_BLOCK__';
        $tp->setValue('mengingat', $mengingatToken);

        $nama = $suratTugas->pegawai?->nama ?? '';
        $jabatan = $suratTugas->jabatan ?: ($suratTugas->pegawai?->jabatan ?? '');
        $pangkat = $suratTugas->pangkat_golongan ?: ($suratTugas->pegawai?->pangkat_golongan ?? '-');
        $unitKerja = $suratTugas->unit_kerja ?: ($suratTugas->pegawai?->unit_kerja ?? config('laporan.surat_tugas.instansi'));

        $tp->setValue('nama', $nama);
        $tp->setValue('jabatan', $jabatan);
        $tp->setValue('unit_kerja', $unitKerja);
        $tp->setValue('tujuan_kegiatan', $suratTugas->tujuan_kegiatan);
        $tp->setValue('waktu_pelaksanaan', $suratTugas->waktu_pelaksanaan);
        $tp->setValue('mata_anggaran', $suratTugas->mata_anggaran ?: '-');

        $tp->setValue('tempat_st', $suratTugas->tempat_st);
        $tp->setValue('tanggal_st', SuratTugas::formatTanggal($suratTugas->tanggal_st));
        $tp->setValue('kepala_jabatan', $suratTugas->kepala_jabatan);
        $tp->setValue('kepala_nama', $suratTugas->kepala_nama);
        $tp->setValue('kepala_nip', $suratTugas->kepala_nip);

        // TTD scan Kepala BPS: default kosong (siap TTD basah/TTE), hanya
        // disisipkan jika pengguna mengaktifkan opsi `sertakan_ttd_kepala`.
        $ttdPath = storage_path('app/'.config('laporan.surat_tugas.kepala_bps.ttd_scan_path', 'assets/ttd-kepala-bps.png'));
        if ($suratTugas->sertakan_ttd_kepala && is_file($ttdPath)) {
            $tp->setImageValue('ttd_kepala', [
                'path' => $ttdPath,
                'width' => 130,
                'height' => 62,
                'ratio' => true,
            ]);
        } else {
            $tp->setValue('ttd_kepala', '');
        }

        // --- Halaman 2: SPD ---
        $tp->setValue('nomor_spd', $suratTugas->nomor_spd ?: '-');
        $tp->setValue('instansi_anggaran', $suratTugas->instansi_anggaran ?: config('laporan.surat_tugas.instansi_panjang'));
        $tp->setValue('ppk_nama', $suratTugas->ppk_nama);
        $tp->setValue('pangkat_golongan', $pangkat);
        $tp->setValue('jabatan_instansi', trim($jabatan.'/'.$unitKerja, '/'));
        $tp->setValue('tingkat_biaya', $suratTugas->tingkat_biaya ?: 'C');
        $tp->setValue('alat_angkutan', $suratTugas->alat_angkutan ?: 'Lainnya');
        $tp->setValue('tempat_berangkat', $suratTugas->tempat_berangkat ?: config('laporan.surat_tugas.kabupaten'));
        $tp->setValue('tempat_tujuan', $suratTugas->tempat_tujuan ?: config('laporan.surat_tugas.kabupaten'));
        $tp->setValue('jumlah_hari', (string) ($suratTugas->jumlah_hari ?: 1));
        $tp->setValue('tanggal_berangkat', SuratTugas::formatTanggal($suratTugas->tanggal_berangkat));
        $tp->setValue('tanggal_kembali', SuratTugas::formatTanggal($suratTugas->tanggal_kembali));
        $tp->setValue('pengikut', $suratTugas->pengikut ?: '');
        $tp->setValue('program_kode', $suratTugas->program_kode ?: '');
        $tp->setValue('program_nama', $suratTugas->program_nama ?: '');
        $tp->setValue('kegiatan_kode', $suratTugas->kegiatan_kode ?: '');
        $tp->setValue('kegiatan_nama', $suratTugas->kegiatan_nama ?: '');
        $tp->setValue('komponen_kode', $suratTugas->komponen_kode ?: '');
        $tp->setValue('komponen_nama', $suratTugas->komponen_nama ?: '');
        $tp->setValue('keterangan_lain', $suratTugas->keterangan_lain ?: '');

        $tp->setValue('ppk_jabatan', $suratTugas->ppk_jabatan);
        $tp->setValue('ppk_sub_jabatan', $suratTugas->ppk_sub_jabatan ?: '');
        $tp->setValue('ppk_nip', $suratTugas->ppk_nip);

        $tembusan = $suratTugas->tembusanList();
        $tp->setValue('tembusan_1', '1. '.($tembusan[0] ?? ''));
        $tp->setValue('tembusan_2', '2. '.($tembusan[1] ?? ''));

        // --- Halaman 3: Visum SPD ---
        $asalLabel = SuratTugas::formatKecamatan($suratTugas->kecamatan_asal ?: config('laporan.surat_tugas.tempat_kedudukan'));
        $tp->setValue('kecamatan_asal_label', $asalLabel);

        // Pecah jabatan Kepala BPS menjadi 2 baris di kotak Visum I seperti template.
        [$kepalaBaris1, $kepalaBaris2] = $this->splitKepalaJabatan($suratTugas->kepala_jabatan);
        $tp->setValue('kepala_jabatan_1', $kepalaBaris1);
        $tp->setValue('kepala_jabatan_2', $kepalaBaris2);

        $firstRute = $rutes->get(0);
        $tp->setValue('visum_1_ke', $firstRute ? SuratTugas::formatKecamatan($firstRute->kecamatan_tujuan) : $suratTugas->tempat_tujuan);

        $totalSlots = max(2, min($rutes->count(), 4));
        for ($i = 0; $i < $totalSlots; $i++) {
            $slot = $i + 2; // visum_2 = tujuan 1, visum_3 = tujuan 2, dst.
            $rute = $rutes->get($i);
            $nextRute = $rutes->get($i + 1);

            if ($rute) {
                $tujuanLabel = SuratTugas::formatKecamatan($rute->kecamatan_tujuan);
                $keLabel = $nextRute
                    ? SuratTugas::formatKecamatan($nextRute->kecamatan_tujuan)
                    : $asalLabel;
                $tglTiba = SuratTugas::formatTanggal($rute->tanggal_tiba);
                $tglBerangkat = SuratTugas::formatTanggal($rute->tanggal_berangkat ?: $rute->tanggal_tiba);

                $tp->setValue("visum_{$slot}_tiba_di", $tujuanLabel);
                $tp->setValue("visum_{$slot}_tiba_tgl", $tglTiba);
                $tp->setValue("visum_{$slot}_dari", $tujuanLabel);
                $tp->setValue("visum_{$slot}_ke", $keLabel);
                $tp->setValue("visum_{$slot}_berangkat_tgl", $tglBerangkat);
            } else {
                $tp->setValue("visum_{$slot}_tiba_di", '');
                $tp->setValue("visum_{$slot}_tiba_tgl", '');
                $tp->setValue("visum_{$slot}_dari", '');
                $tp->setValue("visum_{$slot}_ke", '');
                $tp->setValue("visum_{$slot}_berangkat_tgl", '');
            }
        }

        $outPath = $dir.'/st-'.$suratTugas->id.'-'.uniqid().'.docx';
        $tp->saveAs($outPath);

        if ($tempWorking && is_file($tempWorking)) {
            @unlink($tempWorking);
        }

        // Sesuaikan ukuran kertas pada <w:pgSz> & format daftar Mengingat (hanging-indent).
        $this->postProcessDocx($outPath, strtolower($ukuran), $mengingatToken, $suratTugas->mengingatList());

        return $outPath;
    }

    /**
     * Pecah jabatan Kepala BPS menjadi 2 baris untuk kolom sempit pada blok Visum I.
     *
     * @return array{0: string, 1: string}
     */
    protected function splitKepalaJabatan(string $jabatan): array
    {
        if (preg_match('/^(.*?BPS)\s+(.+)$/i', trim($jabatan), $m)) {
            return [$m[1], $m[2]];
        }

        return [$jabatan, ''];
    }

    /**
     * Gandakan tabel blok Visum III (berisi placeholder visum_3_*) menjadi
     * blok IV (visum_4_*) dan V (visum_5_*) saat jumlah tujuan > 2.
     */
    protected function expandVisumBlocks(string $docxPath, int $totalRutes): void
    {
        $zip = new ZipArchive;
        if ($zip->open($docxPath) !== true) {
            return;
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close();

            return;
        }

        $dom = new DOMDocument;
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        // Temukan tabel yang memuat placeholder ${visum_3_tiba_di}.
        $tables = $xpath->query('//w:body/w:tbl[contains(., "visum_3_tiba_di")]');
        if ($tables && $tables->length > 0) {
            $baseTbl = $tables->item(0);
            $anchor = $baseTbl;

            for ($slot = 4; $slot <= $totalRutes + 1; $slot++) {
                $cloned = $baseTbl->cloneNode(true);
                foreach ($xpath->query('.//w:t', $cloned) as $textNode) {
                    if (str_contains($textNode->nodeValue, 'visum_3_')) {
                        $textNode->nodeValue = str_replace('visum_3_', "visum_{$slot}_", $textNode->nodeValue);
                    }
                }
                $baseTbl->parentNode->insertBefore($cloned, $anchor->nextSibling);
                $anchor = $cloned;
            }

            $zip->addFromString('word/document.xml', $dom->saveXML());
        }

        $zip->close();
    }

    /**
     * Pasca-proses document.xml:
     * 1) Ubah dimensi kertas `<w:pgSz>` (A4 / F4 / Legal).
     * 2) Ganti penanda Mengingat menjadi paragraf terpisah ber-hanging-indent
     *    (`left=360 dxa, hanging=360 dxa`, justified, Bookman Old Style 11pt).
     *
     * @param  list<string>  $mengingatItems
     */
    protected function postProcessDocx(string $docxPath, string $ukuran, string $mengingatToken, array $mengingatItems): void
    {
        [$w, $h] = match ($ukuran) {
            'a4' => [11906, 16838],          // 210mm x 297mm
            'f4', 'folio' => [12189, 18709], // 215mm x 330mm
            default => [12240, 20160],       // Legal: 8.5in x 14in
        };

        $zip = new ZipArchive;
        if ($zip->open($docxPath) !== true) {
            return;
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml !== false) {
            $xml = preg_replace(
                '/<w:pgSz\b[^>]*\/>/',
                '<w:pgSz w:w="'.$w.'" w:h="'.$h.'"/>',
                $xml,
                1
            ) ?? $xml;

            // Pastikan namespace VML tersedia bila PhpWord menyisipkan <v:shape> untuk TTD scan.
            if (str_contains($xml, '<v:') && ! str_contains($xml, 'xmlns:v=')) {
                $xml = preg_replace(
                    '/<w:document\b/',
                    '<w:document xmlns:v="urn:schemas-microsoft-com:vml"',
                    $xml,
                    1
                ) ?? $xml;
            }

            if (str_contains($xml, $mengingatToken)) {
                $paragraphs = [];
                foreach ($mengingatItems as $idx => $rawItem) {
                    $cleanItem = htmlspecialchars(
                        preg_replace('/^\d+\.\s*/', '', $rawItem),
                        ENT_XML1 | ENT_QUOTES,
                        'UTF-8'
                    );
                    $num = ($idx + 1).'.';
                    $rPr = '<w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style" w:cs="Bookman Old Style"/><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr>';
                    $paragraphs[] = '<w:p>'
                        .'<w:pPr><w:spacing w:after="60" w:line="259" w:lineRule="auto"/><w:ind w:left="360" w:hanging="360"/><w:jc w:val="both"/></w:pPr>'
                        .'<w:r>'.$rPr.'<w:t>'.$num.'</w:t><w:tab/></w:r>'
                        .'<w:r>'.$rPr.'<w:t xml:space="preserve">'.$cleanItem.'</w:t></w:r>'
                        .'</w:p>';
                }

                $replacementXml = implode('', $paragraphs);
                $xml = preg_replace(
                    '/<w:p\b[^>]*>(?:(?!<\/w:p>).)*'.preg_quote($mengingatToken, '/').'(?:(?!<\/w:p>).)*<\/w:p>/s',
                    $replacementXml,
                    $xml,
                    1
                ) ?? $xml;
            }

            $zip->addFromString('word/document.xml', $xml);
        }

        $zip->close();
    }
}
