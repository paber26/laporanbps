<?php

namespace App\Support;

class PdfImage
{
    /**
     * Ubah berkas gambar menjadi data URI (base64) yang aman disematkan di PDF
     * (dompdf) maupun HTML preview.
     *
     * Foto diproses ulang dengan GD sebelum disematkan:
     *  - orientasi EXIF diperbaiki (foto ponsel sering ter-rotate),
     *  - diperkecil hingga sisi terpanjang <= $maxDim agar hemat memori — dompdf
     *    bisa gagal diam-diam (gambar tak muncul) saat menyematkan foto 3–5 MB
     *    di server ber-memory kecil,
     *  - dikompres ulang (JPEG) sehingga base64 jauh lebih ringan.
     *
     * PNG (mis. tanda tangan transparan) tetap dipertahankan sebagai PNG.
     *
     * @return string|null data URI, atau null bila berkas tak ada/tak terbaca.
     */
    public static function dataUri(?string $absolutePath, int $maxDim = 1200, int $quality = 75): ?string
    {
        if (! $absolutePath || ! is_file($absolutePath)) {
            return null;
        }

        // Tanpa GD: sematkan apa adanya dengan mime yang dinormalkan.
        if (! function_exists('imagecreatefromstring')) {
            return self::rawDataUri($absolutePath);
        }

        $info = @getimagesize($absolutePath);
        $type = $info[2] ?? null; // konstanta IMAGETYPE_*

        $raw = @file_get_contents($absolutePath);
        if ($raw === false) {
            return null;
        }

        $img = @imagecreatefromstring($raw);
        if ($img === false) {
            // Jika GD gagal (mungkin format WEBP atau HEIC yang tidak didukung GD di server ini),
            // coba gunakan Imagick untuk mengonversi ke JPEG sebelum disematkan.
            if (class_exists('Imagick')) {
                try {
                    $imagick = new \Imagick();
                    $imagick->readImageBlob($raw);
                    $imagick->setImageFormat('jpeg');
                    $imagick->setImageCompressionQuality($quality);
                    // Resize jika perlu
                    $width = $imagick->getImageWidth();
                    $height = $imagick->getImageHeight();
                    $scale = min(1, $maxDim / max($width, $height));
                    if ($scale < 1) {
                        $imagick->resizeImage(
                            max(1, (int) round($width * $scale)),
                            max(1, (int) round($height * $scale)),
                            \Imagick::FILTER_LANCZOS,
                            1
                        );
                    }
                    $jpegBlob = $imagick->getImageBlob();
                    $imagick->clear();
                    $imagick->destroy();
                    
                    if ($jpegBlob) {
                        return 'data:image/jpeg;base64,'.base64_encode($jpegBlob);
                    }
                } catch (\Throwable $e) {
                    // Imagick gagal, fallback ke mentah
                }
            }
            
            // Fallback terakhir: sematkan apa adanya (jika dompdf tidak dukung, gambar akan blank/hilang)
            return self::rawDataUri($absolutePath);
        }
        unset($raw);

        // Perbaiki orientasi berdasarkan EXIF (khusus JPEG).
        if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data') && function_exists('imagerotate')) {
            $img = self::applyExifOrientation($img, $absolutePath);
        }

        $isPng = ($type === IMAGETYPE_PNG);

        // Perkecil bila melebihi batas sisi terpanjang.
        $width = imagesx($img);
        $height = imagesy($img);
        $scale = min(1, $maxDim / max($width, $height));
        if ($scale < 1) {
            $newW = max(1, (int) round($width * $scale));
            $newH = max(1, (int) round($height * $scale));
            $resized = imagecreatetruecolor($newW, $newH);
            if ($isPng) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $width, $height);
            imagedestroy($img);
            $img = $resized;
        }

        ob_start();
        if ($isPng) {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            imagepng($img, null, 6);
            $mime = 'image/png';
        } else {
            imagejpeg($img, null, $quality);
            $mime = 'image/jpeg';
        }
        $data = ob_get_clean();
        imagedestroy($img);

        if (! $data) {
            return self::rawDataUri($absolutePath);
        }

        return 'data:'.$mime.';base64,'.base64_encode($data);
    }

    /**
     * Putar gambar sesuai tag Orientation EXIF agar tampil tegak.
     *
     * @param  \GdImage  $img
     * @return \GdImage
     */
    protected static function applyExifOrientation($img, string $path)
    {
        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 0);

        // imagerotate berputar berlawanan arah jarum jam untuk sudut positif.
        $rotated = match ($orientation) {
            3 => imagerotate($img, 180, 0),
            6 => imagerotate($img, -90, 0),
            8 => imagerotate($img, 90, 0),
            default => null,
        };

        if ($rotated) {
            imagedestroy($img);

            return $rotated;
        }

        return $img;
    }

    /**
     * Sematkan berkas apa adanya (tanpa proses ulang), dengan mime dari ekstensi.
     */
    protected static function rawDataUri(string $absolutePath): ?string
    {
        $raw = @file_get_contents($absolutePath);
        if ($raw === false) {
            return null;
        }

        $mime = match (strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return 'data:'.$mime.';base64,'.base64_encode($raw);
    }
}
