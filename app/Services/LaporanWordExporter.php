<?php

namespace App\Services;

use App\Models\Laporan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Shared\Html;

/**
 * Membangun dokumen Word (.docx) laporan perjalanan dinas secara
 * programatik sehingga hasilnya bisa diunduh lalu diedit di MS Word.
 */
class LaporanWordExporter
{
    public function generate(Laporan $laporan): string
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        $this->buildSuratPengantar($phpWord, $laporan);
        $this->buildLampiran1($phpWord, $laporan);
        $this->buildLampiran2($phpWord, $laporan);

        $dir = storage_path('app/temp');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $path = $dir.'/laporan-'.$laporan->id.'-'.uniqid().'.docx';

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);

        return $path;
    }

    protected function sectionStyle(): array
    {
        return [
            'marginTop' => Converter::cmToTwip(2.5),
            'marginBottom' => Converter::cmToTwip(2.5),
            'marginLeft' => Converter::cmToTwip(3),
            'marginRight' => Converter::cmToTwip(2.5),
        ];
    }

    protected function buildSuratPengantar(PhpWord $phpWord, Laporan $laporan): void
    {
        $section = $phpWord->addSection($this->sectionStyle());

        $section->addText(
            mb_strtoupper($laporan->judul_laporan),
            ['bold' => true, 'size' => 12],
            ['alignment' => 'center', 'spaceAfter' => 240]
        );

        // Perihal + tempat/tanggal.
        $tanggal = Carbon::parse($laporan->tanggal_laporan)->locale('id')->translatedFormat('j F Y');
        $table = $section->addTable(['cellMargin' => 0, 'width' => 100 * 50, 'unit' => 'pct']);
        $table->addRow();
        $left = $table->addCell(6000);
        $left->addText('Perihal : '.$laporan->perihal_laporan, [], ['spaceAfter' => 0]);
        $right = $table->addCell(3500);
        $right->addText('('.$laporan->tempat_laporan.', '.$tanggal.')', [], ['alignment' => 'right', 'spaceAfter' => 0]);

        $section->addTextBreak(1);
        $section->addText('Kepada Yang Terhormat:', [], ['spaceAfter' => 0]);
        $section->addText($laporan->tujuan_surat, ['bold' => true], ['spaceAfter' => 0]);
        $section->addText('di', [], ['spaceAfter' => 0]);
        $section->addText('Tempat', [], ['spaceAfter' => 120]);

        $section->addTextBreak(1);
        $section->addText(
            'Bersama ini disampaikan laporan perjalanan dinas dalam rangka '
            .$laporan->perihal_laporan.' sebagai berikut.',
            [],
            ['alignment' => 'both', 'spaceAfter' => 120]
        );

        // Daftar bernomor.
        $this->addNomor($section, 1, 'Nama', $laporan->pegawai->nama);
        $this->addNomor($section, 2, 'NIP', $laporan->pegawai->nip);
        $this->addNomor($section, 3, 'Unit Kerja', $laporan->pegawai->unit_kerja);
        $this->addNomor($section, 4, 'Lokasi Tujuan Kegiatan', $laporan->lokasi_tujuan);

        $section->addText('5.  Pembiayaan Kegiatan', [], ['spaceAfter' => 0]);
        if ($laporan->pembiayaan) {
            $p = $laporan->pembiayaan;
            foreach ([
                'Program' => $p->program,
                'Kegiatan' => $p->kegiatan,
                'RO' => $p->ro,
                'Komponen' => $p->komponen,
                'Akun' => $p->akun,
            ] as $label => $value) {
                $section->addText(str_repeat(' ', 8).$label.' : '.$value, [], ['spaceAfter' => 0, 'indentation' => ['left' => Converter::cmToTwip(1)]]);
            }
        }

        $section->addText('6.  Uraian kegiatan perjalanan dinas dapat dilihat pada Lampiran 1.', [], ['spaceAfter' => 0]);
        $section->addText('7.  Dokumentasi perjalanan dinas dapat dilihat pada Lampiran 2.', [], ['spaceAfter' => 120]);

        $section->addTextBreak(1);
        $section->addText('Demikian laporan yang dapat disampaikan untuk dijadikan sebagai bahan evaluasi.', [], ['alignment' => 'both']);

        // Blok tanda tangan (rata kanan).
        $section->addTextBreak(1);
        $ttd = $section->addTable(['width' => 100 * 50, 'unit' => 'pct']);
        $ttd->addRow();
        $ttd->addCell(5500);
        $cell = $ttd->addCell(4000);
        $cell->addText('Petugas', [], ['alignment' => 'center', 'spaceAfter' => 0]);

        if ($laporan->pegawai->tanda_tangan_path && \Storage::disk('public')->exists($laporan->pegawai->tanda_tangan_path)) {
            $cell->addImage(\Storage::disk('public')->path($laporan->pegawai->tanda_tangan_path), [
                'width' => 120, 'alignment' => 'center',
            ]);
        } else {
            $cell->addTextBreak(3);
        }
        $cell->addText('('.$laporan->pegawai->nama.')', [], ['alignment' => 'center', 'spaceAfter' => 0]);
    }

    protected function addNomor(Section $section, int $no, string $label, string $value): void
    {
        $section->addText($no.'.  '.$label.' : '.$value, [], ['spaceAfter' => 0]);
    }

    protected function buildLampiran1(PhpWord $phpWord, Laporan $laporan): void
    {
        $section = $phpWord->addSection($this->sectionStyle());

        $section->addText('Lampiran 1', ['bold' => true], ['spaceAfter' => 120]);
        $section->addText('URAIAN KEGIATAN', ['bold' => true], ['alignment' => 'center', 'spaceAfter' => 0]);
        $section->addText(mb_strtoupper($laporan->perihal_laporan), ['bold' => true], ['alignment' => 'center', 'spaceAfter' => 0]);
        $section->addText(mb_strtoupper($laporan->pegawai->unit_kerja), ['bold' => true], ['alignment' => 'center', 'spaceAfter' => 240]);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 60,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);

        $headStyle = ['bold' => true];
        $headParagraph = ['alignment' => 'center', 'spaceAfter' => 0];
        $table->addRow();
        $table->addCell(2500, ['valign' => 'center'])->addText('Hari/Tanggal', $headStyle, $headParagraph);
        $table->addCell(1800, ['valign' => 'center'])->addText('Jam', $headStyle, $headParagraph);
        $table->addCell(5700, ['valign' => 'center'])->addText('Uraian Kegiatan', $headStyle, $headParagraph);

        foreach ($laporan->uraians as $uraian) {
            $table->addRow();
            $hari = Carbon::parse($uraian->tanggal_kegiatan)->locale('id')->translatedFormat('l');
            $tgl = Carbon::parse($uraian->tanggal_kegiatan)->locale('id')->translatedFormat('j F Y');
            $table->addCell(2500, ['valign' => 'top'])->addText($hari.'/'.$tgl, [], ['spaceAfter' => 0]);

            $jam = trim(($uraian->jam_mulai ?? '').(($uraian->jam_mulai && $uraian->jam_selesai) ? '-' : '').($uraian->jam_selesai ?? ''));
            $table->addCell(1800, ['valign' => 'top'])->addText($jam, [], ['spaceAfter' => 0]);

            $cell = $table->addCell(5700, ['valign' => 'top']);
            $html = $uraian->uraian_html ?: '&nbsp;';
            try {
                Html::addHtml($cell, $html, false, false);
            } catch (\Throwable $e) {
                $cell->addText(strip_tags($uraian->uraian_text), [], ['alignment' => 'both', 'spaceAfter' => 0]);
            }
        }
    }

    protected function buildLampiran2(PhpWord $phpWord, Laporan $laporan): void
    {
        $section = $phpWord->addSection($this->sectionStyle());

        $section->addText('Lampiran 2', ['bold' => true], ['spaceAfter' => 120]);
        $section->addText('DOKUMENTASI KEGIATAN', ['bold' => true], ['alignment' => 'center', 'spaceAfter' => 0]);
        $section->addText(mb_strtoupper($laporan->perihal_laporan), ['bold' => true], ['alignment' => 'center', 'spaceAfter' => 240]);

        $docs = $laporan->dokumentasis;
        if ($docs->isEmpty()) {
            $section->addText('Tidak ada dokumentasi.', ['italic' => true]);

            return;
        }

        $table = $section->addTable(['cellMargin' => 80, 'width' => 100 * 50, 'unit' => 'pct']);
        $perRow = 2;
        foreach ($docs->chunk($perRow) as $chunk) {
            $table->addRow();
            foreach ($chunk as $dok) {
                $cell = $table->addCell(4750, ['valign' => 'center']);
                $abs = \Storage::disk('public')->path($dok->image_path);
                if (is_file($abs)) {
                    $cell->addImage($abs, [
                        'width' => 220,
                        'height' => 165,
                        'alignment' => 'center',
                    ]);
                }
                if ($dok->keterangan) {
                    $cell->addText($dok->keterangan, ['size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
                }
            }
            // Sel kosong pelengkap bila jumlah ganjil.
            for ($i = $chunk->count(); $i < $perRow; $i++) {
                $table->addCell(4750);
            }
        }
    }
}
