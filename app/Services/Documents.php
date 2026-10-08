<?php
namespace App\Services;
use App\Models\Employee;
use App\Models\Payroll;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Modules\SafetyShop\Shared\Models\ReceiptSetting;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
class Documents
{
    public static function render($view, $data, $paper = 'A4', $orientation = 'portrait', $pageFooter = true)
    {
        $brand = static::branding();
        $data['documentBrand'] = $brand;
        $data['documentBrandLogo'] = static::brandingLogo($brand);
        $cache = storage_path('app/dompdf');
        if (!is_dir($cache)) { mkdir($cache, 0755, true); }
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->set('chroot', storage_path('app'));
        $options->set('fontDir', $cache); $options->set('fontCache', $cache); $options->set('tempDir', $cache);
        $pdf = new Dompdf($options); $pdf->setPaper($paper, $orientation); $pdf->loadHtml(view($view, $data)->render(), 'UTF-8'); $pdf->render();
        if ($pageFooter) {
            $canvas = $pdf->getCanvas();
            $canvas->page_text(40, $canvas->get_height() - 30, ($brand ? $brand->company_name : 'Manpower').'  |  Page {PAGE_NUM} of {PAGE_COUNT}', $pdf->getFontMetrics()->getFont('DejaVu Sans'), 8, [0.4, 0.5, 0.5]);
        }
        return $pdf->output();
    }
    public static function validateAttachment($path)
    {
        $pdf = new Fpdi; $count = $pdf->setSourceFile($path);
        if ($count < 1 || $count > 30) { throw new \RuntimeException('Attach a PDF with 1 to 30 pages.'); }
        for ($page = 1; $page <= $count; $page++) { $pdf->importPage($page); }
        return $count;
    }
    public static function cv(Employee $employee)
    {
        $employee->loadMissing(['company', 'designation']);
        $photo = null;
        if (Storage::disk('local')->exists($employee->photo_path)) {
            $path = Storage::disk('local')->path($employee->photo_path);
            $source = @imagecreatefromstring(file_get_contents($path));
            if ($source) { ob_start(); imagepng($source); $bytes = ob_get_clean(); imagedestroy($source); $photo = 'data:image/png;base64,'.base64_encode($bytes); }
        }
        $generated = static::render('pdf.cv-professional', compact('employee', 'photo'));
        if (!$employee->document_path) { return $generated; }
        if (!Storage::disk('local')->exists($employee->document_path)) { throw new \RuntimeException('The supporting PDF is missing. Re-upload it from the employee profile.'); }
        $merged = new Fpdi; $merged->SetTitle($employee->name.' - Curriculum Vitae'); $merged->SetAuthor('Manpower');
        foreach ([StreamReader::createByString($generated), Storage::disk('local')->path($employee->document_path)] as $source) {
            $count = $merged->setSourceFile($source);
            for ($page = 1; $page <= $count; $page++) {
                $template = $merged->importPage($page); $size = $merged->getTemplateSize($template);
                $merged->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $merged->useTemplate($template);
            }
        }
        return $merged->Output('S');
    }
    public static function payslip(Payroll $payroll)
    {
        $payroll->loadMissing('approver');
        return $payroll->employment_type === 'own'
            ? static::render('pdf.payslip-own', compact('payroll'), 'A4', 'landscape')
            : static::render('pdf.payslip', compact('payroll'));
    }
    public static function branding()
    {
        return Schema::hasTable('safety_shop_receipt_settings') ? ReceiptSetting::first() : null;
    }
    public static function brandingLogo($brand = null)
    {
        $brand = $brand ?: static::branding();
        if (!$brand || !$brand->logo_path) return null;
        if (str_starts_with($brand->logo_path,'public:')) $path=public_path(substr($brand->logo_path,7));
        elseif (Storage::disk('local')->exists($brand->logo_path)) $path=Storage::disk('local')->path($brand->logo_path);
        else return null;
        if (!is_file($path)) return null;
        return 'data:'.mime_content_type($path).';base64,'.base64_encode(file_get_contents($path));
    }
    public static function download($bytes, $filename)
    {
        return response($bytes, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$filename.'"', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
