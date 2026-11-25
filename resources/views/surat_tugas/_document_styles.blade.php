@php
    $forPdf = $forPdf ?? false;
    $fontUrl = function (string $file) use ($forPdf): string {
        $abs = public_path('fonts/bookman/'.$file);
        if ($forPdf) {
            return 'file://'.$abs;
        }

        return asset('fonts/bookman/'.$file);
    };
@endphp
<style>
    @font-face {
        font-family: "Bookman Old Style";
        src: url("{{ $fontUrl('BookmanOldStyle.ttf') }}") format("truetype");
        font-weight: 400;
        font-style: normal;
    }
    @font-face {
        font-family: "Bookman Old Style";
        src: url("{{ $fontUrl('BookmanOldStyle-Bold.ttf') }}") format("truetype");
        font-weight: 700;
        font-style: normal;
    }
    @font-face {
        font-family: "Bookman Old Style";
        src: url("{{ $fontUrl('BookmanOldStyle-Italic.ttf') }}") format("truetype");
        font-weight: 400;
        font-style: italic;
    }
    @font-face {
        font-family: "Bookman Old Style";
        src: url("{{ $fontUrl('BookmanOldStyle-BoldItalic.ttf') }}") format("truetype");
        font-weight: 700;
        font-style: italic;
    }

    @if ($forPdf)
        @page {
            size: {{ $paperCss ?? '215mm 330mm' }};
            margin: 15mm 8mm 14mm 8mm;
        }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        html, body { margin: 0; padding: 0; background: #fff; color: #000; }
    @endif

    .st-doc-root {
        font-family: "Bookman Old Style", "URW Bookman", Georgia, "Times New Roman", serif;
        color: #000;
        -webkit-font-smoothing: antialiased;
    }
    .st-doc-root * {
        box-sizing: border-box;
    }

    @if (! $forPdf)
        .st-preview-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 24px;
            overflow-x: auto;
            padding-bottom: 12px;
        }
        .st-sheet-block {
            width: 215.9mm;
            max-width: 100%;
        }
        .st-sheet-label {
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b7280;
            margin-bottom: 6px;
        }
        .dark .st-sheet-label {
            color: #9ca3af;
        }
        .st-sheet {
            width: 215.9mm;
            min-height: 315mm;
            background: #ffffff;
            color: #000000;
            padding: 15mm 8mm 14mm 8mm;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
            border: 1px solid #e5e7eb;
        }
    @else
        .st-sheet-page-break {
            break-before: page;
            page-break-before: always;
        }
        .st-sheet {
            width: 100%;
        }
    @endif

    /* ==================== HALAMAN 1: SURAT TUGAS ==================== */
    .st-kop {
        text-align: center;
        padding-top: 2mm;
        margin-bottom: 8mm;
    }
    .st-kop-logo {
        width: 25.3mm;
        height: 19.6mm;
        object-fit: contain;
        display: block;
        margin: 0 auto 3mm;
    }
    .st-kop-title {
        font-family: Arial, "Liberation Sans", Helvetica, sans-serif;
        font-size: 16pt;
        font-weight: 700;
        font-style: italic;
        line-height: 1.2;
        letter-spacing: 0.2pt;
    }

    .st-heading {
        text-align: center;
        font-size: 12pt;
        font-weight: 400;
        line-height: 1.25;
        margin-bottom: 8mm;
    }

    /* 5 kolom eksak Template ST Pegawai Organik.docx: 28.6 | 6.4 | 39.7 | 4.8 | 103.2 mm (total 182.7 mm) */
    table.st-main-tbl {
        width: 182.7mm;
        max-width: 100%;
        margin: 0 auto;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 11pt;
        line-height: 1.16;
    }
    table.st-main-tbl > tbody > tr > td {
        vertical-align: top;
        padding: 1.2mm 0;
        border: none;
    }
    table.st-main-tbl > tbody > tr.st-gap-top > td {
        padding-top: 3.5mm;
    }
    .st-justify {
        text-align: justify;
    }
    .st-sep {
        text-align: left;
    }

    table.st-mengingat-tbl {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 11pt;
        line-height: 1.16;
    }
    table.st-mengingat-tbl td {
        padding: 0 0 1.4mm 0 !important;
        vertical-align: top;
        border: none;
    }
    table.st-mengingat-tbl td.m-no {
        width: 6.5mm;
        text-align: left;
    }
    table.st-mengingat-tbl td.m-txt {
        text-align: justify;
    }

    .st-ttd-wrap {
        width: 182.7mm;
        max-width: 100%;
        margin: 14mm auto 0;
        display: flex;
        justify-content: flex-end;
    }
    .st-ttd-box {
        width: 90mm;
        text-align: center;
        font-size: 11pt;
        line-height: 1.2;
        font-weight: 400;
    }
    .st-ttd-space {
        height: 22mm;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .st-ttd-space img {
        max-width: 34mm;
        max-height: 21mm;
        object-fit: contain;
    }

    /* ==================== HALAMAN 2: SPD ==================== */
    table.spd-header-tbl {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 10.5pt;
        line-height: 1.2;
        margin-bottom: 2.5mm;
    }
    table.spd-header-tbl td {
        padding: 0.4mm 0.5mm;
        vertical-align: top;
        border: none;
    }

    /* 8 kolom eksak TBL[33]: 9.9 | 7.5 | 47.5 | 14.8 | 24.1 | 18.8 | 10.8 | 71.5 mm */
    table.spd-main-tbl {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 10.2pt;
        line-height: 1.18;
    }
    table.spd-main-tbl td {
        border: 0.5pt solid #000;
        padding: 1.2mm 1.6mm;
        vertical-align: top;
        word-wrap: break-word;
    }
    table.spd-main-tbl td.c-no {
        text-align: left;
    }
    /* Garis pemisah utama sesuai XML TableGrid1 + tcBorders */
    table.spd-main-tbl td.bt-thick { border-top: 2pt solid #000; }
    table.spd-main-tbl td.bb-thick { border-bottom: 2pt solid #000; }
    table.spd-main-tbl td.bt-med   { border-top: 1pt solid #000; }
    table.spd-main-tbl td.bb-med   { border-bottom: 1pt solid #000; }
    table.spd-main-tbl td.br-thick { border-right: 2pt solid #000; }
    table.spd-main-tbl td.bl-thick { border-left: 2pt solid #000; }
    table.spd-main-tbl td.bt-none  { border-top: none; }
    table.spd-main-tbl td.bb-none  { border-bottom: none; }
    table.spd-main-tbl td.bl-none  { border-left: none; }
    table.spd-main-tbl td.br-none  { border-right: none; }

    .spd-sign-section {
        width: 185.7mm;
        max-width: 100%;
        margin: 8mm auto 0;
        font-size: 10.5pt;
        line-height: 1.22;
    }
    table.spd-keluar-tbl {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    table.spd-keluar-tbl td {
        padding: 0.5mm 0;
        border: none;
        vertical-align: top;
    }
    .spd-ppk-wrap {
        width: 94mm;
        margin-left: auto;
        margin-right: 4mm;
        margin-top: 6mm;
        text-align: center;
    }
    .spd-ppk-space {
        height: 19mm;
    }
    .spd-ppk-name {
        font-weight: 700;
    }
    .spd-tembusan {
        margin-top: 7mm;
        padding-left: 4mm;
        font-size: 10.5pt;
        line-height: 1.3;
    }

    /* ==================== HALAMAN 3: VISUM SPD ==================== */
    /* 6 kolom eksak TBL[55..58]: 33.3 | 7.9 | 55.6 | 31.9 | 4.2 | 55.6 mm (total 188.5 mm) */
    table.visum-tbl {
        width: 188.5mm;
        max-width: 100%;
        margin: 0 auto;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 10.2pt;
        line-height: 1.18;
    }
    table.visum-tbl td {
        padding: 0.9mm 1.4mm;
        vertical-align: top;
        border: none;
        word-wrap: break-word;
    }
    table.visum-tbl td.v-bt { border-top: 0.75pt solid #000; }
    table.visum-tbl td.v-bb { border-bottom: 0.75pt solid #000; }
    table.visum-tbl td.v-bl { border-left: 0.75pt solid #000; }
    table.visum-tbl td.v-br { border-right: 0.75pt solid #000; }

    .visum-sig-cell {
        text-align: center;
        padding-top: 2mm !important;
        padding-bottom: 2.5mm !important;
    }
    .visum-sig-gap {
        height: 16mm;
    }
    .visum-blank-row td {
        height: 21mm;
    }
    .visum-blank-row-compact td {
        height: 13mm;
    }
    .visum-bold {
        font-weight: 700;
    }
    .visum-note-cell {
        font-size: 9pt;
        padding-top: 1.5mm !important;
        padding-bottom: 3mm !important;
    }
    .visum-perhatian-cell {
        font-size: 9pt;
        line-height: 1.2;
        padding-bottom: 2mm !important;
    }
</style>
