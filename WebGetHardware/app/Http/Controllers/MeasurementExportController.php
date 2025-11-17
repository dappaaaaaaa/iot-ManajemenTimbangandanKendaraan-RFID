<?php

namespace App\Http\Controllers;

use App\Models\Measurement;
use Barryvdh\DomPDF\Facade\Pdf;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MeasurementExportController extends Controller
{
    public function exportAttachmentPdf(Measurement $measurement)
    {
        if (!$measurement->attachments) {
            abort(404, 'Tiket Tidak Ditemukan');
        }

        $imagePath = storage_path('app/public/' . $measurement->attachments);

        if (!file_exists($imagePath)) {
            abort(404, 'File tidak ditemukan.');
        }

        $imageManager = new ImageManager(new Driver());
        $image = $imageManager->read($imagePath);

        $image->scaleDown(2000, 2000);
        $compressedImage = $image->toJpeg(quality: 70);
        $base64Image = 'data:image/jpeg;base64,' . base64_encode((string) $compressedImage);

        // Ukuran gambar dalam pt (1 px ≈ 0.75 pt)
        $widthPt = $image->width() * 0.75;
        $heightPt = $image->height() * 0.75;

        // Buat PDF view
        $pdf = Pdf::loadView('pdf.attachment', [
            'imageBase64' => $base64Image,
        ]);

        // Ambil instance dompdf dan set ukuran
        $dompdf = $pdf->getDomPDF();
        $dompdf->setPaper([0, 0, $widthPt, $heightPt]);

        return $pdf->download('attachment-' . $measurement->vehicle_number . '.pdf');
    }
}
