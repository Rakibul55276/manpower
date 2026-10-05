<?php
namespace App\Modules\Invoicing\Services;

use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Models\InvoiceSetting;
use App\Services\Pay;
use DOMDocument;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class InvoiceService
{
    public static function calculate(array $lines)
    {
        $calculated = []; $subtotal = $discount = $tax = $total = 0;
        foreach ($lines as $line) {
            $quantity = Pay::units($line['quantity']); $price = Pay::units($line['unit_price']); $lineDiscount = Pay::units($line['discount'] ?? 0);
            $lineSubtotal = Pay::rounded($quantity * $price, 100);
            if ($lineDiscount > $lineSubtotal) throw \Illuminate\Validation\ValidationException::withMessages(['lines' => 'A line discount cannot exceed its line subtotal.']);
            $rate = $line['tax_category'] === 'standard' ? (int) $line['tax_rate_units'] : 0;
            $lineTax = Pay::rounded(($lineSubtotal - $lineDiscount) * $rate, 10000);
            $lineTotal = $lineSubtotal - $lineDiscount + $lineTax;
            $calculated[] = array_merge($line, ['quantity_units'=>$quantity,'unit_price_cents'=>$price,'line_subtotal_cents'=>$lineSubtotal,'discount_cents'=>$lineDiscount,'tax_rate_units'=>$rate,'tax_cents'=>$lineTax,'line_total_cents'=>$lineTotal]);
            $subtotal += $lineSubtotal; $discount += $lineDiscount; $tax += $lineTax; $total += $lineTotal;
        }
        return compact('calculated','subtotal','discount','tax','total');
    }

    public static function invoiceQrPayload(Invoice $invoice, InvoiceSetting $settings)
    {
        return implode("\n", [
            'INVOICE: '.($invoice->invoice_number ?: $invoice->uuid),
            'SUPPLIER: '.$settings->legal_name,
            'CUSTOMER: '.$invoice->customer->name,
            'DATE: '.$invoice->issue_date->format('Y-m-d'),
            'TOTAL: '.$invoice->currency.' '.Pay::decimal($invoice->total_cents),
            'VAT: '.$invoice->currency.' '.Pay::decimal($invoice->tax_cents),
            'UUID: '.$invoice->uuid,
        ]);
    }

    public static function amountInWords($cents)
    {
        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
            $riyals = intdiv((int) $cents, 100); $halalas = (int) $cents % 100;
            $words = ucfirst($formatter->format($riyals)).' Saudi Riyals';
            if ($halalas) $words .= ' and '.$formatter->format($halalas).' Halalas';
            return $words.' only';
        }
        return 'SAR '.Pay::money($cents).' only';
    }

    public static function arabicPdf($text)
    {
        if (!$text) return '';
        return (new \ArPHP\I18N\Arabic())->utf8Glyphs((string) $text, 200, false, true);
    }

    public static function qrDataUri(Invoice $invoice, InvoiceSetting $settings)
    {
        $invoice->loadMissing('customer');
        $renderer = new ImageRenderer(new RendererStyle(220, 2), new SvgImageBackEnd());
        $svg = (new Writer($renderer))->writeString(static::invoiceQrPayload($invoice, $settings));
        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    public static function xml(Invoice $invoice, InvoiceSetting $settings)
    {
        $invoice->loadMissing(['customer','lines']);
        $dom = new DOMDocument('1.0','UTF-8'); $dom->formatOutput = true;
        $root = $dom->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:Invoice-2','Invoice'); $dom->appendChild($root);
        $root->setAttribute('xmlns:cac','urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $root->setAttribute('xmlns:cbc','urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        self::node($dom,$root,'cbc:ProfileID','reporting:1.0'); self::node($dom,$root,'cbc:ID',$invoice->invoice_number);
        self::node($dom,$root,'cbc:UUID',$invoice->uuid); self::node($dom,$root,'cbc:IssueDate',$invoice->issue_date->format('Y-m-d'));
        self::node($dom,$root,'cbc:DocumentCurrencyCode',$invoice->currency); if($invoice->notes) self::node($dom,$root,'cbc:Note',$invoice->notes);
        $supplier = self::node($dom,$root,'cac:AccountingSupplierParty'); $party = self::node($dom,$supplier,'cac:Party'); self::node($dom,$party,'cbc:Name',$settings->legal_name); self::node($dom,$party,'cbc:CompanyID',$settings->vat_number);
        $customer = self::node($dom,$root,'cac:AccountingCustomerParty'); $party = self::node($dom,$customer,'cac:Party'); self::node($dom,$party,'cbc:Name',$invoice->customer->name); if ($invoice->customer->vat_number) self::node($dom,$party,'cbc:CompanyID',$invoice->customer->vat_number);
        foreach ($invoice->lines as $index => $line) { $lineNode=self::node($dom,$root,'cac:InvoiceLine'); self::node($dom,$lineNode,'cbc:ID',$index+1); self::node($dom,$lineNode,'cbc:InvoicedQuantity',Pay::decimal($line->quantity_units)); self::node($dom,$lineNode,'cbc:LineExtensionAmount',Pay::decimal($line->line_subtotal_cents-$line->discount_cents)); $item=self::node($dom,$lineNode,'cac:Item'); self::node($dom,$item,'cbc:Name',$line->description); }
        $totals=self::node($dom,$root,'cac:LegalMonetaryTotal'); self::node($dom,$totals,'cbc:TaxExclusiveAmount',Pay::decimal($invoice->subtotal_cents-$invoice->discount_cents)); self::node($dom,$totals,'cbc:TaxInclusiveAmount',Pay::decimal($invoice->total_cents)); self::node($dom,$totals,'cbc:PayableAmount',Pay::decimal($invoice->total_cents));
        return $dom->saveXML();
    }

    private static function node(DOMDocument $dom, $parent, $name, $value = null) { $node=$dom->createElement($name); if ($value !== null) $node->appendChild($dom->createTextNode((string)$value)); $parent->appendChild($node); return $node; }
}
