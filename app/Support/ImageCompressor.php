<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ImageCompressor
{
    /**
     * Buat thumbnail terkompresi dari file gambar sumber.
     *
     * @param string $sourcePath Path relatif di disk public
     * @param string $targetPath Path relatif tujuan di disk public
     * @param int $maxWidth Lebar maksimum (default: 600px)
     * @param int $maxHeight Tinggi maksimum (default: 600px)
     * @param int $quality Kualitas kompresi JPEG 1-100 (default: 75)
     * @return bool
     */
    public static function createThumbnail(
        string $sourcePath,
        string $targetPath,
        int $maxWidth = 600,
        int $maxHeight = 600,
        int $quality = 75
    ): bool {
        if (! extension_loaded('gd')) {
            return false;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($sourcePath)) {
            return false;
        }

        $absSource = $disk->path($sourcePath);
        $absTarget = $disk->path($targetPath);

        // Pastikan direktori tujuan ada
        $targetDir = dirname($absTarget);
        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $info = @getimagesize($absSource);
        if (! $info) {
            return false;
        }

        [$origWidth, $origHeight, $imageType] = $info;

        if ($origWidth <= 0 || $origHeight <= 0) {
            return false;
        }

        // Buka gambar sumber berdasarkan tipe
        $sourceImage = match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($absSource),
            IMAGETYPE_PNG => @imagecreatefrompng($absSource),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absSource) : null,
            IMAGETYPE_GIF => @imagecreatefromgif($absSource),
            default => null,
        };

        if (! $sourceImage) {
            return false;
        }

        // Koreksi orientasi EXIF (khusus JPEG dari kamera smartphone)
        if ($imageType === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            try {
                $exif = @exif_read_data($absSource);
                if (! empty($exif['Orientation'])) {
                    $orientation = (int) $exif['Orientation'];
                    if ($orientation === 3) {
                        $rotated = imagerotate($sourceImage, 180, 0);
                        if ($rotated) {
                            imagedestroy($sourceImage);
                            $sourceImage = $rotated;
                        }
                    } elseif ($orientation === 6) {
                        $rotated = imagerotate($sourceImage, -90, 0);
                        if ($rotated) {
                            imagedestroy($sourceImage);
                            $sourceImage = $rotated;
                            $tmp = $origWidth;
                            $origWidth = $origHeight;
                            $origHeight = $tmp;
                        }
                    } elseif ($orientation === 8) {
                        $rotated = imagerotate($sourceImage, 90, 0);
                        if ($rotated) {
                            imagedestroy($sourceImage);
                            $sourceImage = $rotated;
                            $tmp = $origWidth;
                            $origWidth = $origHeight;
                            $origHeight = $tmp;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Abaikan kesalahan baca EXIF
            }
        }

        // Hitung skala dengan mempertahankan rasio aspek
        $scale = min($maxWidth / $origWidth, $maxHeight / $origHeight, 1.0);
        $newWidth = max(1, (int) round($origWidth * $scale));
        $newHeight = max(1, (int) round($origHeight * $scale));

        $thumb = imagecreatetruecolor($newWidth, $newHeight);

        // Beri latar belakang putih bila gambar transparan (disimpan sebagai JPEG)
        $white = imagecolorallocate($thumb, 255, 255, 255);
        imagefilledrectangle($thumb, 0, 0, $newWidth, $newHeight, $white);

        imagecopyresampled(
            $thumb,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth,
            $newHeight,
            $origWidth,
            $origHeight
        );

        $saved = imagejpeg($thumb, $absTarget, $quality);

        imagedestroy($thumb);
        imagedestroy($sourceImage);

        return (bool) $saved;
    }
}
