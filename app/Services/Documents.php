<?php
namespace App\Services;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Company;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Modules\SafetyShop\Shared\Models\ReceiptSetting;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
class Documents
{
    public static function render($view, $data, $paper = 'A4', $orientation = 'portrait', $pageFooter = true, $fixedLetterhead = false, $suppressBrandingAssets = false)
    {
        $brand = static::branding();
        $data['documentBrand'] = $brand;
        $data['documentBrandLogo'] = static::brandingLogo($brand);
        $data['documentBrandHeader'] = null;
        $data['documentBrandFooter'] = null;
        $data['documentBrandWatermark'] = $suppressBrandingAssets ? null : $data['documentBrandLogo'];
        $data['fixedLetterhead'] = $fixedLetterhead;
        $cache = storage_path('app/dompdf');
        if (!is_dir($cache)) { mkdir($cache, 0755, true); }
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->set('chroot', storage_path('app'));
        $options->set('fontDir', $cache); $options->set('fontCache', $cache); $options->set('tempDir', $cache);
        $html=view($view, $data)->render();
        if(!$fixedLetterhead && !$suppressBrandingAssets && $brand){
            $letterhead=view('pdf.letterhead',$data)->render();
            $html=preg_replace('/<body([^>]*)>/i','$0'.$letterhead,$html,1);
        }
        if ($view === 'pdf.safety-shop-bulk-invoice') {
            $pdf = InvoicePdfLayout::render($data, $options);
        } else {
            $pdf = new Dompdf($options); $pdf->setPaper($paper, $orientation); $pdf->loadHtml($html, 'UTF-8'); $pdf->render();
        }
        if ($view === 'pdf.safety-shop-bulk-invoice') {
            $canvas = $pdf->getCanvas();
            $font = $pdf->getFontMetrics()->getFont('DejaVu Sans');
            if ($data['sale']->status === 'draft') {
                $canvas->page_script(function (int $pageNumber, int $pageCount, $pageCanvas, $fontMetrics): void {
                    $draftFont = $fontMetrics->getFont('DejaVu Sans', 'bold');
                    $label = 'DRAFT';
                    $size = 62;
                    $width = $fontMetrics->getTextWidth($label, $draftFont, $size);
                    $pageCanvas->text(($pageCanvas->get_width() - $width) / 2, $pageCanvas->get_height() * .48, $label, $draftFont, $size, [0.12, 0.32, 0.58, 'alpha' => .22], 0, 2, -18);
                });
            }
            $canvas->page_text(24, $canvas->get_height() - 20, $data['sale']->invoice_number, $font, 7, [0.4, 0.5, 0.55]);
            $canvas->page_text($canvas->get_width() - 112, $canvas->get_height() - 20, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 7, [0.4, 0.5, 0.55]);
        }
        if ($pageFooter && !$fixedLetterhead && !$data['documentBrandFooter']) {
            $canvas = $pdf->getCanvas();
            $canvas->page_text(40, $canvas->get_height() - 30, ($brand ? $brand->company_name : 'Manpower').'  |  Page {PAGE_NUM} of {PAGE_COUNT}', $pdf->getFontMetrics()->getFont('DejaVu Sans'), 8, [0.4, 0.5, 0.5]);
        }
        $bytes = $pdf->output();
        if ($fixedLetterhead) $bytes = static::applyFixedLetterhead($bytes);
        return $bytes;
    }
    public static function applyFixedLetterhead($content)
    {
        $letterhead = config('documents.letterhead_pdf');
        if (!$letterhead || !is_file($letterhead)) throw new \RuntimeException('The fixed A4 letterhead PDF is missing.');
        $output = new Fpdi;
        $output->setSourceFile($letterhead);
        $background = $output->importPage(1);
        $pageSize = $output->getTemplateSize($background);
        $count = $output->setSourceFile(StreamReader::createByString($content));
        for ($page = 1; $page <= $count; $page++) {
            $template = $output->importPage($page);
            $output->AddPage('P', [$pageSize['width'], $pageSize['height']]);
            $output->useTemplate($background, 0, 0, $pageSize['width'], $pageSize['height']);
            $output->useTemplate($template, 0, 0, $pageSize['width'], $pageSize['height']);
            // Retain the supplied artwork, except for the email and website lines
            // explicitly excluded below its mobile-number line.
            $output->SetFillColor(253, 253, 253);
            $output->Rect(115.5, 275.2, 61, 5.3, 'F');
            $output->SetFillColor(245, 247, 248);
            $output->Rect(115.5, 285.0, 61, 6.0, 'F');
        }
        return $output->Output('S');
    }
    public static function qrDataUri($payload)
    {
        $renderer = new ImageRenderer(new RendererStyle(220, 2), new SvgImageBackEnd());
        return 'data:image/svg+xml;base64,'.base64_encode((new Writer($renderer))->writeString((string)$payload));
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
        $qr=static::qrDataUri(implode("\n",[
            'PAYSLIP: PS-'.str_pad($payroll->id,6,'0',STR_PAD_LEFT),
            'EMPLOYEE: '.$payroll->employee_name,
            'PERIOD: '.$payroll->month,
            'NET PAY: SAR '.Pay::money($payroll->net_pay_cents),
            'STATUS: '.strtoupper($payroll->status),
        ]));
        return $payroll->employment_type === 'own'
            ? static::render('pdf.payslip-own', compact('payroll','qr'), 'A4', 'portrait', false, true)
            : static::render('pdf.payslip', compact('payroll','qr'), 'A4', 'portrait', false, true);
    }
    public static function branding()
    {
        if (!Schema::hasTable('safety_shop_receipt_settings')) return null;
        $companyId=auth()->check() ? auth()->user()->company_id : null;
        if(!$companyId) return null;
        $brand=ReceiptSetting::forCompany($companyId)->first();
        if(!$brand) return null;
        $company=Company::find($companyId);
        if($company){
            $brand->setAttribute('company_name',$company->name);
            if($company->name_ar)$brand->setAttribute('company_name_ar',$company->name_ar);
            if(!$brand->address)$brand->setAttribute('address',$company->address);
            if(!$brand->phone)$brand->setAttribute('phone',$company->phone);
            if(!$brand->email)$brand->setAttribute('email',$company->email);
            if(!$brand->commercial_registration)$brand->setAttribute('commercial_registration',$company->registration_number);
        }
        return $brand;
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
    public static function brandingAsset($storedPath)
    {
        if(!$storedPath)return null;
        if(str_starts_with($storedPath,'public:'))$path=public_path(substr($storedPath,7));
        elseif(Storage::disk('local')->exists($storedPath))$path=Storage::disk('local')->path($storedPath);
        else return null;
        if(!is_file($path))return null;
        return 'data:'.mime_content_type($path).';base64,'.base64_encode(file_get_contents($path));
    }
    public static function download($bytes, $filename)
    {
        return response($bytes, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$filename.'"', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
