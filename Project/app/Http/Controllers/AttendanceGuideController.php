<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\Process\Process;

class AttendanceGuideController extends Controller
{
    private const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

    public function pdf(): Response
    {
        $htmlPath = tempnam(sys_get_temp_dir(), 'attendance-guide-').'.html';
        $pdfPath = tempnam(sys_get_temp_dir(), 'attendance-guide-').'.pdf';
        file_put_contents($htmlPath, view('participant.panduan-absensi-pdf')->render());
        @unlink($pdfPath);

        $process = new Process([self::CHROME, '--headless', '--disable-gpu', '--no-sandbox', '--no-pdf-header-footer', '--print-to-pdf='.$pdfPath, 'file://'.$htmlPath]);
        $process->run();

        abort_unless($process->isSuccessful() && is_file($pdfPath), 500, 'PDF renderer unavailable.');

        $output = file_get_contents($pdfPath);
        @unlink($htmlPath);
        @unlink($pdfPath);

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="panduan-absensi-pt-ast-indonesia.pdf"',
        ]);
    }
}
