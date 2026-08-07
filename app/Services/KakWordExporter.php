<?php

namespace App\Services;

use App\Models\Kak;
use Carbon\Carbon;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Shared\Html;

/**
 * Membangun dokumen Word (.docx) Kerangka Acuan Kerja (KAK) perjalanan
 * dinas BPS. Struktur mengikuti KAK resmi: kop instansi, judul kegiatan,
 * bagian A s.d. H, tabel rincian anggaran, dan blok penandatanganan dua
 * kolom (Ketua Tim Pelaksana & Pejabat Pembuat Komitmen).
 */
class KakWordExporter
{
    public function generate(Kak $kak): string
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        $section = $phpWord->addSection([
            'marginTop' => Converter::cmToTwip(2),
            'marginBottom' => Converter::cmToTwip(2),
            'marginLeft' => Converter::cmToTwip(3),
            'marginRight' => Converter::cmToTwip(2),
        ]);

        $this->kopInstansi($section, $kak);
        $this->judul($section, $kak);

        // Bagian-bagian isi KAK (A s.d. H).
        $bagian = [
            'A' => 'Dasar Hukum',
            'B' => 'Gambaran Umum',
            'C' => 'Maksud dan Tujuan',
            'D' => 'Keluaran/Output',
            'E' => 'Pelaksana Kegiatan',
            'F' => 'Jadwal dan Lokasi Kegiatan',
            'G' => 'Sumber Pembiayaan',
            'H' => 'Penutup',
        ];

        foreach ($bagian as $huruf => $label) {
            $kolom = $this->columnFor($huruf);
            $isi = trim((string) $kak->{$kolom});
            if ($isi === '') {
                continue;
            }

            $section->addText(
                $huruf.'. '.mb_strtoupper($label),
                ['bold' => true, 'size' => 12],
                ['spaceBefore' => 200, 'spaceAfter' => 120]
            );
            $this->addHtmlBlock($section, $isi);

            // Tabel rincian anggaran hanya di bagian G.
            if ($huruf === 'G' && $kak->anggarans->isNotEmpty()) {
                $this->tabelAnggaran($section, $kak);
            }
        }

        $this->tandaTangan($section, $kak);

        $dir = storage_path('app/temp');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $path = $dir.'/kak-'.$kak->id.'-'.uniqid().'.docx';

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);

        return $path;
    }

    protected function columnFor(string $huruf): string
    {
        return match ($huruf) {
            'A' => 'dasar_hukum',
            'B' => 'gambaran_umum',
            'C' => 'maksud_tujuan',
            'D' => 'keluaran',
            'E' => 'pelaksana_kegiatan',
            'F' => 'jadwal_lokasi',
            'G' => 'sumber_pembiayaan',
            'H' => 'penutup',
            default => 'dasar_hukum',
        };
    }

    /**
     * Kop instansi (logo + nama lembaga) di tengah halaman.
     */
    protected function kopInstansi(\PhpOffice\PhpWord\Element\Section $section, Kak $kak): void
    {
        $nama = trim($kak->unit_kerja ?? '');
        if ($nama === '') {
            $nama = 'BADAN PUSAT STATISTIK';
        }

        $logo = public_path('logo-small.png');
        if (is_file($logo)) {
            $section->addImage($logo, [
                'width' => 90,
                'height' => 70,
                'alignment' => 'center',
                'spaceAfter' => 60,
            ]);
        }

        $section->addText(
            mb_strtoupper($nama),
            ['bold' => true, 'size' => 14],
            ['alignment' => 'center', 'spaceAfter' => 200]
        );
    }

    /**
     * Judul KAK di tengah halaman.
     */
    protected function judul(\PhpOffice\PhpWord\Element\Section $section, Kak $kak): void
    {
        $section->addText(
            'KERANGKA ACUAN KERJA (KAK)',
            ['bold' => true, 'size' => 14],
            ['alignment' => 'center', 'spaceAfter' => 120]
        );
        $section->addText(
            mb_strtoupper($kak->judul),
            ['bold' => true, 'size' => 13],
            ['alignment' => 'center', 'spaceAfter' => 0]
        );

        $sub = trim($kak->unit_kerja ?? '');
        if ($kak->tahun) {
            $sub = trim($sub.' T.A. '.$kak->tahun);
        }
        if ($sub !== '') {
            $section->addText(
                mb_strtoupper($sub),
                ['bold' => true, 'size' => 12],
                ['alignment' => 'center', 'spaceAfter' => 240]
            );
        }
    }

    /**
     * Sisipkan konten HTML (dari CKEditor) sebagai paragraf Word.
     */
    protected function addHtmlBlock(\PhpOffice\PhpWord\Element\Section $section, string $html): void
    {
        try {
            Html::addHtml($section, $html, false, false);
        } catch (\Throwable $e) {
            $section->addText(strip_tags($html), [], ['alignment' => 'both']);
        }
    }

    /**
     * Tabel rincian anggaran biaya (bagian G).
     */
    protected function tabelAnggaran(\PhpOffice\PhpWord\Element\Section $section, Kak $kak): void
    {
        $section->addTextBreak(1);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 60,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);

        $head = ['bold' => true];
        $center = ['alignment' => 'center', 'spaceAfter' => 0];

        $table->addRow();
        $table->addCell(1300, ['valign' => 'center'])->addText('Kode MAK', $head, $center);
        $table->addCell(3200, ['valign' => 'center'])->addText('Deskripsi', $head, $center);
        $table->addCell(700, ['valign' => 'center'])->addText('Volume', $head, $center);
        $table->addCell(800, ['valign' => 'center'])->addText('Satuan', $head, $center);
        $table->addCell(1600, ['valign' => 'center'])->addText('Harga Satuan (Rp)', $head, $center);
        $table->addCell(1600, ['valign' => 'center'])->addText('Jumlah Biaya (Rp)', $head, $center);

        foreach ($kak->anggarans as $anggaran) {
            $table->addRow();
            $table->addCell(1300, ['valign' => 'top'])->addText($anggaran->kode_mak ?? '', [], ['spaceAfter' => 0]);
            $table->addCell(3200, ['valign' => 'top'])->addText($anggaran->deskripsi ?? '', [], ['spaceAfter' => 0]);
            $table->addCell(700, ['valign' => 'top'])->addText($this->formatNum($anggaran->volume), [], $center);
            $table->addCell(800, ['valign' => 'top'])->addText($anggaran->satuan ?? '', [], $center);
            $table->addCell(1600, ['valign' => 'top'])->addText($this->formatNum($anggaran->harga_satuan), [], $center);
            $table->addCell(1600, ['valign' => 'top'])->addText($this->formatNum($anggaran->jumlah_biaya), [], $center);
        }

        $total = $kak->anggarans->sum('jumlah_biaya');
        $table->addRow();
        $table->addCell(1300)->addText('', [], ['spaceAfter' => 0]);
        $table->addCell(3200)->addText('Jumlah', $head, ['spaceAfter' => 0]);
        $table->addCell(700)->addText('', [], ['spaceAfter' => 0]);
        $table->addCell(800)->addText('', [], ['spaceAfter' => 0]);
        $table->addCell(1600)->addText('', [], ['spaceAfter' => 0]);
        $table->addCell(1600)->addText($this->formatNum($total), $head, $center);
    }

    protected function formatNum(float $nilai): string
    {
        return number_format($nilai, 0, ',', '.');
    }

    /**
     * Blok penandatanganan dua kolom: Ketua Tim Pelaksana & PPK.
     */
    protected function tandaTangan(\PhpOffice\PhpWord\Element\Section $section, Kak $kak): void
    {
        $tempat = $kak->tempat_penandatanganan ?? '';
        $tanggal = $kak->tanggal_penandatanganan
            ? Carbon::parse($kak->tanggal_penandatanganan)->locale('id')->translatedFormat('j F Y')
            : '';

        // Tanpa data penandatangan sama sekali, lewati blok ini.
        if (! $kak->ttd_kiri_nama && ! $kak->ttd_kiri_jabatan && ! $kak->ttd_kanan_nama && ! $kak->ttd_kanan_jabatan) {
            return;
        }

        $section->addTextBreak(3);

        $kiri = [
            'jabatan' => $kak->ttd_kiri_jabatan ?? '',
            'nama' => $kak->ttd_kiri_nama ?? '',
            'nip' => $kak->ttd_kiri_nip ?? '',
        ];
        $kanan = [
            'jabatan' => $kak->ttd_kanan_jabatan ?? '',
            'nama' => $kak->ttd_kanan_nama ?? '',
            'nip' => $kak->ttd_kanan_nip ?? '',
        ];

        // Kop tanggal & instansi penandatangan.
        $section->addText(
            trim(($tempat ? $tempat.($tanggal ? ', ' : '') : '').$tanggal),
            [],
            ['alignment' => 'center', 'spaceAfter' => 0]
        );
        $section->addText(
            mb_strtoupper(trim($kak->unit_kerja ?? '')),
            ['bold' => true],
            ['alignment' => 'center', 'spaceAfter' => 160]
        );

        $ttd = $section->addTable(['width' => 100 * 50, 'unit' => 'pct']);
        $ttd->addRow();

        $this->addTtdCell($ttd->addCell(4750, ['valign' => 'top']), $kiri);
        $this->addTtdCell($ttd->addCell(4750, ['valign' => 'top']), $kanan);
    }

    protected function addTtdCell(\PhpOffice\PhpWord\Element\Cell $cell, array $kolom): void
    {
        $center = ['alignment' => 'center', 'spaceAfter' => 0];
        $cell->addText($kolom['jabatan'], [], $center);
        $cell->addTextBreak(3);
        $cell->addText('('.$kolom['nama'].')', ['bold' => true, 'underline' => 'single'], $center);
        if ($kolom['nip']) {
            $cell->addText('NIP. '.$kolom['nip'], [], $center);
        }
    }
}
