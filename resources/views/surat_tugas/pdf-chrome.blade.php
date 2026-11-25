<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Tugas {{ $suratTugas->nomor_st }}</title>
    @include('surat_tugas._document_styles', ['forPdf' => true, 'paperCss' => $paperCss ?? '215mm 330mm'])
</head>
<body>
    @include('surat_tugas._document', ['suratTugas' => $suratTugas, 'forPdf' => true])
</body>
</html>
