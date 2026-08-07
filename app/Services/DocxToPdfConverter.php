<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;

/**
 * Mengonversi berkas DOCX hasil editor menjadi HTML yang rapi untuk Dompdf.
 *
 * Alih-alih memakai penulis HTML bawaan PhpWord (yang kehilangan penomoran
 * list, ukuran gambar, dan struktur), berkas DOCX diurai langsung dari XML
 * (document.xml, numbering.xml, rels, media) sehingga penomoran "1. 2. 3.",
 * tabel, gambar, dan perataan paragraf dipertahankan. Output diberi kelas
 * CSS yang menyamai template PDF KAK (kop, judul, bagian, tabel, ttd).
 */
class DocxToPdfConverter
{
    private string $tmpDir;

    private \DOMDocument $document;

    private \DOMXPath $xpath;

    private string $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /** @var array<int, array> Peta numId => [format, lvlText] */
    private array $numbering = [];

    /** @var array<int, string> rId => path relatif media */
    private array $mediaRels = [];

    /** @var array<string, string> daftar gambar yang sudah dibaca */
    private array $images = [];

    /**
     * Konversi DOCX (path absolut) menjadi HTML siap-render untuk Dompdf.
     */
    public function toHtml(string $docxPath): string
    {
        $this->unzip($docxPath);
        $this->loadDocument();
        $this->loadNumbering();
        $this->loadRelationships();

        $body = $this->renderBlocks($this->getBodyChildren());

        $this->cleanup();

        return $body;
    }

    /**
     * Ukuran kertas (A4/F4) dari dokumen, dikembalikan sebagai string 'a4'
     * atau array [0,0,w,h] dalam poin untuk Dompdf.
     *
     * @return string|array<int, float>
     */
    public function paperSize(): string|array
    {
        $w = $this->nodeVal('/w:document/w:body/w:sectPr/w:pgSz/@w:w');
        $h = $this->nodeVal('/w:document/w:body/w:sectPr/w:pgSz/@w:h');

        // 1 twip = 1/20 poin.
        $wPt = $w !== null ? ((float) $w) / 20.0 : 595.28;
        $hPt = $h !== null ? ((float) $h) / 20.0 : 841.89;

        // Deteksi F4/Folio (lebar ~609pt, tinggi ~936pt).
        if ($wPt > 600 && $wPt < 640 && $hPt > 900) {
            return [0, 0, $wPt, $hPt];
        }

        return 'a4';
    }

    /**
     * Margins dokumen (twip) menjadi CSS, dengan konversi twip → pt.
     */
    public function marginsCss(): string
    {
        $get = fn (string $name): float => ($this->nodeVal("/w:document/w:body/w:sectPr/w:pgMar/@w:{$name}") ?? 1134) / 20.0;

        return sprintf(
            'margin: %.2fpt %.2fpt %.2fpt %.2fpt;',
            $get('top'),
            $get('right'),
            $get('bottom'),
            $get('left')
        );
    }

    /**
     * Ekstrak DOCX ke direktori sementara.
     */
    private function unzip(string $docxPath): void
    {
        $this->tmpDir = sys_get_temp_dir().'/docx2pdf-'.uniqid();

        $zip = new \ZipArchive;
        if ($zip->open($docxPath) !== true) {
            throw new \RuntimeException('Gagal membaca berkas DOCX.');
        }
        $zip->extractTo($this->tmpDir);
        $zip->close();
    }

    private function loadDocument(): void
    {
        $this->document = new \DOMDocument;
        libxml_use_internal_errors(true);
        $this->document->load($this->tmpDir.'/word/document.xml');
        libxml_clear_errors();
        $this->xpath = new \DOMXPath($this->document);
        $this->xpath->registerNamespace('w', $this->ns);
    }

    /**
     * Baca numbering.xml untuk memetakan numId → format & teks level.
     */
    private function loadNumbering(): void
    {
        $file = $this->tmpDir.'/word/numbering.xml';
        if (! is_file($file)) {
            return;
        }

        $xml = new \DOMDocument;
        libxml_use_internal_errors(true);
        $xml->load($file);
        libxml_clear_errors();
        $xp = new \DOMXPath($xml);
        $xp->registerNamespace('w', $this->ns);

        // Peta abstractNumId => level format/teks.
        $abstract = [];
        foreach ($xp->query('//w:abstractNum') as $an) {
            $aid = (int) $an->getAttribute('w:abstractNumId');
            foreach ($an->getElementsByTagNameNS($this->ns, 'lvl') as $lvl) {
                $ilvl = (int) $lvl->getAttribute('w:ilvl');
                $fmt = $xp->evaluate('string(w:numFmt/@w:val)', $lvl);
                $text = $xp->evaluate('string(w:lvlText/@w:val)', $lvl);
                $start = (int) ($xp->evaluate('string(w:start/@w:val)', $lvl) ?: 1);
                $abstract[$aid][$ilvl] = ['fmt' => $fmt, 'text' => $text, 'start' => $start];
            }
        }

        // numId → abstractNumId.
        foreach ($xp->query('//w:num') as $num) {
            $numId = (int) $num->getAttribute('w:numId');
            $abstractId = (int) $xp->evaluate('string(w:abstractNumId/@w:val)', $num);
            if (isset($abstract[$abstractId])) {
                $this->numbering[$numId] = $abstract[$abstractId];
            }
        }
    }

    /**
     * Baca relasi document.xml untuk memetakan rId → file media.
     */
    private function loadRelationships(): void
    {
        $file = $this->tmpDir.'/word/_rels/document.xml.rels';
        if (! is_file($file)) {
            return;
        }

        $xml = new \DOMDocument;
        libxml_use_internal_errors(true);
        $xml->load($file);
        libxml_clear_errors();
        $xp = new \DOMXPath($xml);
        $xp->registerNamespace('pr', 'http://schemas.openxmlformats.org/package/2006/relationships');

        foreach ($xp->query('//pr:Relationship') as $rel) {
            $type = $rel->getAttribute('Type');
            if (str_contains($type, '/image')) {
                $this->mediaRels[$rel->getAttribute('Id')] = $rel->getAttribute('Target');
            }
        }
    }

    /**
     * Ambil children <body> (w:p, w:tbl, w:sectPr).
     *
     * @return array<int, \DOMNode>
     */
    private function getBodyChildren(): array
    {
        $children = [];
        $body = $this->xpath->query('/w:document/w:body')->item(0);
        if (! $body) {
            return $children;
        }

        foreach ($body->childNodes as $node) {
            if ($node instanceof \DOMElement) {
                $children[] = $node;
            }
        }

        return $children;
    }

    /**
     * Render children body menjadi HTML dengan penanganan list berurutan.
     *
     * @param array<int, \DOMNode> $nodes
     */
    private function renderBlocks(array $nodes): string
    {
        $html = '';
        $count = count($nodes);

        for ($i = 0; $i < $count; $i++) {
            $node = $nodes[$i];

            // Gabungkan rangkaian paragraf bernomor menjadi satu <ol>.
            if ($this->isListItem($node)) {
                $listHtml = '';
                $numId = $this->listNumId($node);
                while ($i < $count && $this->isListItem($nodes[$i]) && $this->listNumId($nodes[$i]) === $numId) {
                    $listHtml .= $this->renderListItem($nodes[$i], $numId);
                    $i++;
                }
                $i--;
                $html .= '<ol>'.$listHtml.'</ol>';
                continue;
            }

            if ($node->localName === 'p') {
                $html .= $this->renderParagraph($node);
            } elseif ($node->localName === 'tbl') {
                $html .= $this->renderTable($node);
            }
        }

        return $html;
    }

    /**
     * Render satu paragraf biasa.
     */
    private function renderParagraph(\DOMElement $p): string
    {
        $jc = $this->xpath->evaluate('string(w:pPr/w:jc/@w:val)', $p);
        $align = $this->mapAlignment($jc);

        $before = (int) ($this->xpath->evaluate('string(w:pPr/w:spacing/@w:before)', $p) ?: 0);
        $after = (int) ($this->xpath->evaluate('string(w:pPr/w:spacing/@w:after)', $p) ?: 0);
        // Konversi twip → em (1 twip = 0.05pt ≈ 0.00417em pada 12pt).
        $marginTop = $before > 0 ? sprintf('margin-top:%.2fpt;', $before / 20) : '';
        $marginBottom = $after > 0 ? sprintf('margin-bottom:%.2fpt;', $after / 20) : '';

        $inner = $this->renderInlines($p);

        if (trim($inner) === '') {
            // Paragraf kosong dipertahankan sebagai spasi vertikal (mis. blok ttd).
            return '<p class="spacer">&nbsp;</p>';
        }

        $style = trim($align.$marginTop.$marginBottom);
        $styleAttr = $style !== '' ? ' style="'.$style.'"' : '';

        // Paragraf berisi satu gambar → blok logo.
        if (preg_match('/^\s*<img[^>]+>\s*$/s', $inner) === 1) {
            return '<p class="logo"'.$styleAttr.'>'.$inner.'</p>';
        }

        // Deteksi header bagian seperti "A. DASAR HUKUM" (teks pendek, bold, seluruh paragraf).
        if ($this->isSectionHeading($p, $inner)) {
            return '<p class="bagian-judul"'.$styleAttr.'>'.$inner.'</p>';
        }

        return '<p'.$styleAttr.'>'.$inner.'</p>';
    }

    /**
     * Cek apakah paragraf adalah header bagian (judul A..H yang dicetak tebal).
     */
    private function isSectionHeading(\DOMElement $p, string $inner): bool
    {
        // Harus seluruh teks di dalam satu run dengan bold.
        $runs = $p->getElementsByTagNameNS($this->ns, 'r');
        if ($runs->length === 0) {
            return false;
        }

        $allBold = true;
        $text = '';
        foreach ($runs as $run) {
            $b = $this->xpath->evaluate('string(w:rPr/w:b/@w:val)', $run);
            // <w:b/> tanpa val berarti bold.
            $isBold = $b !== '0' && $this->xpath->evaluate('count(w:rPr/w:b)', $run) > 0;
            if (! $isBold) {
                $allBold = false;
            }
            $text .= $this->xpath->evaluate('string(./w:t)', $run);
        }

        $text = trim($text);

        return $allBold && strlen($text) > 0 && strlen($text) <= 120
            && preg_match('/^[A-Z0-9]{1,2}\.\s/', $text) === 1;
    }

    /**
     * Render paragraf list menjadi <li>.
     */
    private function renderListItem(\DOMElement $p, int $numId): string
    {
        $inner = $this->renderInlines($p);

        if (trim($inner) === '') {
            return '';
        }

        $jc = $this->xpath->evaluate('string(w:pPr/w:jc/@w:val)', $p);
        $align = $this->mapAlignment($jc);

        return '<li style="'.$align.'">'.$inner.'</li>';
    }

    /**
     * Render run/teks/gambar dalam sebuah paragraf.
     */
    private function renderInlines(\DOMElement $container): string
    {
        $html = '';
        $runs = $container->getElementsByTagNameNS($this->ns, 'r');

        foreach ($runs as $run) {
            $html .= $this->renderRun($run);
        }

        return $html;
    }

    /**
     * Render satu run: teks + format + gambar.
     */
    private function renderRun(\DOMElement $run): string
    {
        $open = '';
        $close = '';

        $bold = $this->xpath->evaluate('count(w:rPr/w:b)', $run) > 0
            && $this->xpath->evaluate('string(w:rPr/w:b/@w:val)', $run) !== '0';
        $italic = $this->xpath->evaluate('count(w:rPr/w:i)', $run) > 0
            && $this->xpath->evaluate('string(w:rPr/w:i/@w:val)', $run) !== '0';
        $underline = $this->xpath->evaluate('count(w:rPr/w:u)', $run) > 0
            && $this->xpath->evaluate('string(w:rPr/w:u/@w:val)', $run) !== '0';

        $sz = $this->xpath->evaluate('string(w:rPr/w:sz/@w:val)', $run);
        $fontSize = $sz !== '' ? sprintf('font-size:%.1fpt;', (int) $sz / 2) : '';

        if ($bold) {
            $open .= '<b>';
            $close = '</b>'.$close;
        }
        if ($italic) {
            $open .= '<i>';
            $close = '</i>'.$close;
        }
        if ($underline) {
            $open .= '<u>';
            $close = '</u>'.$close;
        }
        if ($fontSize !== '') {
            $open .= '<span style="'.$fontSize.'">';
            $close = '</span>'.$close;
        }

        $out = '';

        // Gambar (v:shape/imagedata atau w:drawing).
        $pict = $run->getElementsByTagNameNS('urn:schemas-microsoft-com:vml', 'shape');
        if ($pict->length > 0) {
            $out .= $this->renderImageFromVml($pict->item(0));
        }

        $blip = $run->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'blip');
        if ($blip->length > 0) {
            $out .= $this->renderImageFromDrawing($blip->item(0));
        }

        // Teks biasa.
        foreach ($run->getElementsByTagNameNS($this->ns, 't') as $t) {
            $out .= htmlspecialchars($t->textContent, ENT_QUOTES, 'UTF-8');
        }

        if ($this->xpath->evaluate('count(w:br)', $run) > 0) {
            $out .= '<br>';
        }

        if ($out === '') {
            return '';
        }

        return $open.$out.$close;
    }

    /**
     * Gambar dari VML (v:shape → w:imagedata r:id).
     */
    private function renderImageFromVml(\DOMElement $shape): string
    {
        $imagedata = $shape->getElementsByTagNameNS($this->ns, 'imagedata');
        if ($imagedata->length === 0) {
            return '';
        }

        $rId = $imagedata->item(0)->getAttribute('r:id');
        $style = $shape->getAttribute('style');

        return $this->imageTag($rId, $style);
    }

    /**
     * Gambar dari DrawingML (a:blip → r:embed).
     */
    private function renderImageFromDrawing(\DOMElement $blip): string
    {
        $rId = $blip->getAttribute('r:embed');

        // Ukuran dari ext (a:ext cx/cy dalam EMU).
        $ext = $blip->ownerDocument !== null
            ? $blip->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'ext')
            : null;
        $style = '';
        if ($ext && $ext->length > 0) {
            $cx = (float) $ext->item(0)->getAttribute('cx');
            $cy = (float) $ext->item(0)->getAttribute('cy');
            if ($cx > 0 && $cy > 0) {
                // 1 EMU = 1/914400 inci.
                $w = $cx / 914400 * 72;
                $h = $cy / 914400 * 72;
                $style = sprintf('width:%.2fpt; height:%.2fpt;', $w, $h);
            }
        }

        return $this->imageTag($rId, $style);
    }

    /**
     * Buat tag <img> base64 dari rId, dengan ukuran dari style VML/Drawing.
     */
    private function imageTag(string $rId, string $style): string
    {
        if (! isset($this->mediaRels[$rId])) {
            return '';
        }

        $target = ltrim($this->mediaRels[$rId], '/');
        $path = $this->tmpDir.'/word/'.$target;

        if (! is_file($path)) {
            return '';
        }

        $mime = function_exists('mime_content_type') ? (mime_content_type($path) ?: 'image/png') : 'image/png';
        $b64 = base64_encode(file_get_contents($path));

        // Ukuran: ambil dari style VML (width/height dalam pt), fallback ke
        // lebar gambar asli dengan batas maksimum.
        if (preg_match('/width:([\d.]+)pt;\s*height:([\d.]+)pt/', $style, $m) && $m[1] > 0 && $m[2] > 0) {
            $w = (float) $m[1];
            $h = (float) $m[2];
            // Logo BPS: pertahankan rasio tapi batasi lebar ~90pt.
            if ($w > 120) {
                $h = $h * (120 / $w);
                $w = 120;
            }
            $dim = sprintf('width:%.2fpt; height:%.2fpt;', $w, $h);
        } else {
            $dim = 'max-width:100%; height:auto;';
        }

        return '<img src="data:'.$mime.';base64,'.$b64.'" style="'.$dim.'"/>';
    }

    /**
     * Render tabel (w:tbl) menjadi <table>.
     */
    private function renderTable(\DOMElement $tbl): string
    {
        // Deteksi tabel ttd (tanpa border, dua kolom) vs tabel data (berborder).
        $hasBorders = $this->xpath->evaluate('count(w:tblPr/w:tblBorders/w:top[@w:val!="none"])', $tbl) > 0;
        $hasBorders = $hasBorders || $this->xpath->evaluate('count(w:tblPr/w:tblBorders/w:left[@w:val!="none"])', $tbl) > 0;

        $class = $hasBorders ? 'data-table' : 'ttd-table';
        $html = '<table class="'.$class.'">';

        foreach ($tbl->getElementsByTagNameNS($this->ns, 'tr') as $tr) {
            $html .= '<tr>';
            foreach ($tr->getElementsByTagNameNS($this->ns, 'tc') as $tc) {
                $html .= '<td>'.$this->renderTableCell($tc).'</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }

    /**
     * Render isi sel tabel.
     */
    private function renderTableCell(\DOMElement $tc): string
    {
        $html = '';
        foreach ($tc->getElementsByTagNameNS($this->ns, 'p') as $p) {
            if ($this->isListItem($p)) {
                $html .= '<p>'.$this->renderInlines($p).'</p>';
                continue;
            }
            $html .= $this->renderParagraph($p);
        }

        return $html;
    }

    private function isListItem(\DOMElement $p): bool
    {
        return $this->xpath->evaluate('count(w:pPr/w:numPr)', $p) > 0;
    }

    private function listNumId(\DOMElement $p): int
    {
        return (int) $this->xpath->evaluate('string(w:pPr/w:numPr/w:numId/@w:val)', $p);
    }

    private function mapAlignment(string $jc): string
    {
        return match ($jc) {
            'center' => 'text-align:center;',
            'right' => 'text-align:right;',
            'both', 'distribute' => 'text-align:justify;',
            default => '',
        };
    }

    private function nodeVal(string $query): ?string
    {
        $val = $this->xpath->evaluate('string('.$query.')');

        return $val === '' ? null : $val;
    }

    private function cleanup(): void
    {
        if (isset($this->tmpDir) && is_dir($this->tmpDir)) {
            // Hapus rekursif.
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->tmpDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $f) {
                $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
            }
            rmdir($this->tmpDir);
        }
    }
}
