<?php
namespace App\Modules\Zatca\Services;

use App\Modules\SafetyShop\Sales\Models\Sale;
use App\Modules\SafetyShop\Sales\Models\SaleReturn;
use App\Modules\SafetyShop\Shared\Models\ReceiptSetting;
use App\Modules\Zatca\Models\ZatcaDevice;
use App\Modules\Zatca\Models\ZatcaDocument;
use App\Services\Pay;
use DOMDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ZatcaInvoiceService
{
    private const NS_INVOICE='urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';
    private const NS_CREDIT='urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2';
    private const NS_CAC='urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';
    private const NS_CBC='urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';
    private const NS_EXT='urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2';

    public function prepare(Sale $sale): ZatcaDocument
    {
        return DB::transaction(function()use($sale){
            $sale=Sale::with(['lines','location','customerRecord'])->whereKey($sale->id)->lockForUpdate()->firstOrFail();
            if($sale->status!=='finalized')$this->fail('sale','Only finalized invoices can be prepared for ZATCA.');
            $companyId=(int)$sale->location->company_id;
            if($existing=ZatcaDocument::where('source_type','safety_shop_sale')->where('source_id',$sale->id)->first())return $existing;
            $seller=ReceiptSetting::forCompany($companyId)->first();
            $errors=$this->validateSource($sale,$seller);
            if($errors)$this->fail('sale',implode(' ',$errors));
            $device=ZatcaDevice::firstOrCreate(['company_id'=>$companyId],['environment'=>'disabled','status'=>'not_configured']);
            $device=ZatcaDevice::whereKey($device->id)->lockForUpdate()->firstOrFail();
            $icv=$device->last_icv+1;
            $uuid=(string)Str::uuid();
            $xml=$this->buildXml($sale,$seller,$uuid,$icv,$device->previous_invoice_hash);
            $hash=hash('sha256',$this->canonicalize($xml));
            $document=ZatcaDocument::create(['company_id'=>$companyId,'device_id'=>$device->id,'source_type'=>'safety_shop_sale','source_id'=>$sale->id,'uuid'=>$uuid,'icv'=>$icv,'document_kind'=>'invoice','invoice_type'=>$sale->invoice_type,'previous_invoice_hash'=>$device->previous_invoice_hash,'invoice_hash'=>$hash,'status'=>'prepared','submission_mode'=>$sale->invoice_type==='standard'?'clearance':'reporting','unsigned_xml'=>$xml,'validation_results'=>['local'=>['status'=>'passed','checked_at'=>now()->toIso8601String()],'zatca_sdk'=>['status'=>'pending','message'=>'Run the official SDK validation before onboarding.']]]);
            $device->update(['last_icv'=>$icv,'previous_invoice_hash'=>base64_encode($hash)]);
            return $document;
        },3);
    }

    public function prepareReturn(SaleReturn $return): ZatcaDocument
    {
        return DB::transaction(function()use($return){
            $return=SaleReturn::with(['sale.customerRecord','sale.location','lines.saleLine'])->whereKey($return->id)->lockForUpdate()->firstOrFail();$sale=$return->sale;$companyId=(int)$sale->location->company_id;
            if($existing=ZatcaDocument::where('source_type','safety_shop_return')->where('source_id',$return->id)->first())return $existing;
            $seller=ReceiptSetting::forCompany($companyId)->first();$errors=$this->validateSource($sale,$seller);if($errors)$this->fail('return',implode(' ',$errors));
            $device=ZatcaDevice::firstOrCreate(['company_id'=>$companyId],['environment'=>'disabled','status'=>'not_configured']);$device=ZatcaDevice::whereKey($device->id)->lockForUpdate()->firstOrFail();$icv=$device->last_icv+1;$uuid=(string)Str::uuid();
            $xml=$this->buildCreditNoteXml($return,$seller,$uuid,$icv,$device->previous_invoice_hash);$hash=hash('sha256',$this->canonicalize($xml));
            $document=ZatcaDocument::create(['company_id'=>$companyId,'device_id'=>$device->id,'source_type'=>'safety_shop_return','source_id'=>$return->id,'uuid'=>$uuid,'icv'=>$icv,'document_kind'=>'credit_note','invoice_type'=>$sale->invoice_type,'previous_invoice_hash'=>$device->previous_invoice_hash,'invoice_hash'=>$hash,'status'=>'prepared','submission_mode'=>$sale->invoice_type==='standard'?'clearance':'reporting','unsigned_xml'=>$xml,'validation_results'=>['local'=>['status'=>'passed','checked_at'=>now()->toIso8601String()],'zatca_sdk'=>['status'=>'pending']]]);
            $device->update(['last_icv'=>$icv,'previous_invoice_hash'=>base64_encode($hash)]);return $document;
        },3);
    }

    public function buildCreditNoteXml(SaleReturn $return,ReceiptSetting $seller,string $uuid,int $icv,?string $previousHash): string
    {
        $sale=$return->sale;$dom=new DOMDocument('1.0','UTF-8');$dom->formatOutput=true;$root=$dom->createElementNS(self::NS_INVOICE,'Invoice');$dom->appendChild($root);$root->setAttributeNS('http://www.w3.org/2000/xmlns/','xmlns:cac',self::NS_CAC);$root->setAttributeNS('http://www.w3.org/2000/xmlns/','xmlns:cbc',self::NS_CBC);$root->setAttributeNS('http://www.w3.org/2000/xmlns/','xmlns:ext',self::NS_EXT);
        $this->cbc($dom,$root,'ProfileID','reporting:1.0');$this->cbc($dom,$root,'ID',$return->credit_note_number);$this->cbc($dom,$root,'UUID',$uuid);$this->cbc($dom,$root,'IssueDate',$return->created_at->format('Y-m-d'));$this->cbc($dom,$root,'IssueTime',$return->created_at->format('H:i:s'));$type=$this->cbc($dom,$root,'InvoiceTypeCode','381');$type->setAttribute('name',$sale->invoice_type==='simplified'?'0200000':'0100000');$this->cbc($dom,$root,'DocumentCurrencyCode','SAR');$this->cbc($dom,$root,'TaxCurrencyCode','SAR');
        $billing=$this->cac($dom,$root,'BillingReference');$invoiceRef=$this->cac($dom,$billing,'InvoiceDocumentReference');$this->cbc($dom,$invoiceRef,'ID',$sale->invoice_number);
        $ref=$this->cac($dom,$root,'AdditionalDocumentReference');$this->cbc($dom,$ref,'ID','ICV');$this->cbc($dom,$ref,'UUID',(string)$icv);$pih=$this->cac($dom,$root,'AdditionalDocumentReference');$this->cbc($dom,$pih,'ID','PIH');$att=$this->cac($dom,$pih,'Attachment');$embedded=$this->cbc($dom,$att,'EmbeddedDocumentBinaryObject',$previousHash?:base64_encode(hash('sha256','0')));$embedded->setAttribute('mimeCode','text/plain');
        $this->qrReference($dom,$root,$this->phaseOneQr($seller->company_name,$seller->vat_number,$return->created_at,$return->refund_cents,$return->tax_refund_cents));
        $this->party($dom,$root,'AccountingSupplierParty',$seller->company_name,$seller->vat_number,$seller->commercial_registration,$seller->address,$seller->city,$seller->postal_code??null,$seller->building_number,$seller->street,$seller->district,$seller->additional_number,$seller->country_code??'SA',true);$this->party($dom,$root,'AccountingCustomerParty',$sale->customer,$sale->customer_vat_number,$sale->customer_commercial_registration,$sale->customer_address,optional($sale->customerRecord)->city,optional($sale->customerRecord)->postal_code,optional($sale->customerRecord)->building_number,optional($sale->customerRecord)->street,optional($sale->customerRecord)->district,null,optional($sale->customerRecord)->country_code??'SA');
        $payment=$this->cac($dom,$root,'PaymentMeans');$this->cbc($dom,$payment,'PaymentMeansCode','10');$this->cbc($dom,$payment,'InstructionNote',$return->reason);
        $exclusive=max(0,$return->refund_cents-$return->tax_refund_cents);$taxTotal=$this->cac($dom,$root,'TaxTotal');$this->amount($dom,$taxTotal,'TaxAmount',$return->tax_refund_cents);$taxTotal2=$this->cac($dom,$root,'TaxTotal');$this->amount($dom,$taxTotal2,'TaxAmount',$return->tax_refund_cents);$sub=$this->cac($dom,$taxTotal2,'TaxSubtotal');$this->amount($dom,$sub,'TaxableAmount',$exclusive);$this->amount($dom,$sub,'TaxAmount',$return->tax_refund_cents);$cat=$this->cac($dom,$sub,'TaxCategory');$this->cbc($dom,$cat,'ID','S');$this->cbc($dom,$cat,'Percent',number_format($sale->tax_rate_units/100,2,'.',''));$scheme=$this->cac($dom,$cat,'TaxScheme');$this->cbc($dom,$scheme,'ID','VAT');$legal=$this->cac($dom,$root,'LegalMonetaryTotal');$this->amount($dom,$legal,'LineExtensionAmount',$return->gross_refund_cents);$this->amount($dom,$legal,'TaxExclusiveAmount',$exclusive);$this->amount($dom,$legal,'TaxInclusiveAmount',$return->refund_cents);$this->amount($dom,$legal,'AllowanceTotalAmount',$return->discount_refund_cents);$this->amount($dom,$legal,'PayableAmount',$return->refund_cents);
        foreach($return->lines->values() as $i=>$line){$sold=$line->saleLine;$node=$this->cac($dom,$root,'InvoiceLine');$this->cbc($dom,$node,'ID',(string)($i+1));$qty=$this->cbc($dom,$node,'InvoicedQuantity',(string)$line->quantity);$qty->setAttribute('unitCode',$this->unitCode($sold->unit));$gross=$line->quantity*$sold->price_cents;$this->amount($dom,$node,'LineExtensionAmount',$gross);$item=$this->cac($dom,$node,'Item');$this->cbc($dom,$item,'Name',$sold->name);$class=$this->cac($dom,$item,'ClassifiedTaxCategory');$this->cbc($dom,$class,'ID','S');$this->cbc($dom,$class,'Percent',number_format($sale->tax_rate_units/100,2,'.',''));$scheme=$this->cac($dom,$class,'TaxScheme');$this->cbc($dom,$scheme,'ID','VAT');$price=$this->cac($dom,$node,'Price');$this->amount($dom,$price,'PriceAmount',$sold->price_cents);}
        return $dom->saveXML();
    }

    public function validateSource(Sale $sale,?ReceiptSetting $seller): array
    {
        $errors=[];
        if(!$seller)$errors[]='Seller configuration is missing.';
        else{
            if(!filled($seller->company_name)||!filled($seller->company_name_ar))$errors[]='Seller legal names are incomplete.';
            if(!preg_match('/^3\d{13}3$/',(string)$seller->vat_number))$errors[]='Seller VAT number must be a valid 15-digit Saudi VAT number.';
            if(!filled($seller->address)||!filled($seller->city)||!preg_match('/^\d{5}$/',(string)$seller->postal_code)||!preg_match('/^\d{4}$/',(string)$seller->building_number)||!preg_match('/^\d{4}$/',(string)$seller->additional_number)||!filled($seller->street)||!filled($seller->district))$errors[]='Seller ZATCA National Address is incomplete.';
        }
        if(!$sale->invoice_number||!$sale->invoice_sequence_number)$errors[]='Controlled invoice number is missing.';
        if(!$sale->lines->count())$errors[]='Invoice must contain at least one line.';
        if($sale->invoice_type==='standard'&&!preg_match('/^3\d{13}3$/',(string)$sale->customer_vat_number))$errors[]='A standard invoice requires a valid 15-digit Saudi buyer VAT number beginning and ending with 3.';
        if($sale->invoice_type==='standard'&&(!filled($sale->customer_address)||!filled(optional($sale->customerRecord)->city)||!filled(optional($sale->customerRecord)->postal_code)))$errors[]='A standard invoice requires the buyer address, city, and postal code.';
        $lineSubtotal=(int)$sale->lines->sum('total_cents');
        if($lineSubtotal!==$sale->subtotal_cents)$errors[]='Stored line subtotal does not match the invoice subtotal.';
        if($sale->taxable_cents+$sale->tax_cents!==$sale->total_cents)$errors[]='Stored VAT totals do not reconcile.';
        return $errors;
    }

    public function buildXml(Sale $sale,ReceiptSetting $seller,string $uuid,int $icv,?string $previousHash): string
    {
        $dom=new DOMDocument('1.0','UTF-8');$dom->formatOutput=true;
        $root=$dom->createElementNS(self::NS_INVOICE,'Invoice');$dom->appendChild($root);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/','xmlns:cac',self::NS_CAC);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/','xmlns:cbc',self::NS_CBC);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/','xmlns:ext',self::NS_EXT);
        $this->cbc($dom,$root,'ProfileID','reporting:1.0');
        $this->cbc($dom,$root,'ID',$sale->invoice_number);$this->cbc($dom,$root,'UUID',$uuid);
        $this->cbc($dom,$root,'IssueDate',$sale->created_at->format('Y-m-d'));$this->cbc($dom,$root,'IssueTime',$sale->created_at->format('H:i:s'));
        $type=$this->cbc($dom,$root,'InvoiceTypeCode','388');$type->setAttribute('name',$sale->invoice_type==='simplified'?'0200000':'0100000');
        if($sale->invoice_notes)$this->cbc($dom,$root,'Note',$sale->invoice_notes);
        $this->cbc($dom,$root,'DocumentCurrencyCode','SAR');$this->cbc($dom,$root,'TaxCurrencyCode','SAR');
        $ref=$this->cac($dom,$root,'AdditionalDocumentReference');$this->cbc($dom,$ref,'ID','ICV');$this->cbc($dom,$ref,'UUID',(string)$icv);
        $pih=$this->cac($dom,$root,'AdditionalDocumentReference');$this->cbc($dom,$pih,'ID','PIH');$att=$this->cac($dom,$pih,'Attachment');$embedded=$this->cbc($dom,$att,'EmbeddedDocumentBinaryObject',$previousHash?:base64_encode(hash('sha256','0')));$embedded->setAttribute('mimeCode','text/plain');
        $this->qrReference($dom,$root,$this->phaseOneQr($seller->company_name,$seller->vat_number,$sale->created_at,$sale->total_cents,$sale->tax_cents));
        $this->party($dom,$root,'AccountingSupplierParty',$seller->company_name,$seller->vat_number,$seller->commercial_registration,$seller->address,$seller->city,$seller->postal_code??null,$seller->building_number,$seller->street,$seller->district,$seller->additional_number,$seller->country_code??'SA',true);
        $this->party($dom,$root,'AccountingCustomerParty',$sale->customer,$sale->customer_vat_number,$sale->customer_commercial_registration,$sale->customer_address,optional($sale->customerRecord)->city,optional($sale->customerRecord)->postal_code,optional($sale->customerRecord)->building_number,optional($sale->customerRecord)->street,optional($sale->customerRecord)->district,null,optional($sale->customerRecord)->country_code??'SA');
        if($sale->discount_cents>0){$allow=$this->cac($dom,$root,'AllowanceCharge');$this->cbc($dom,$allow,'ChargeIndicator','false');$this->cbc($dom,$allow,'AllowanceChargeReason','Discount');$this->amount($dom,$allow,'Amount',$sale->discount_cents);$tax=$this->cac($dom,$allow,'TaxCategory');$this->cbc($dom,$tax,'ID','S');$this->cbc($dom,$tax,'Percent',number_format($sale->tax_rate_units/100,2,'.',''));$scheme=$this->cac($dom,$tax,'TaxScheme');$this->cbc($dom,$scheme,'ID','VAT');}
        $taxTotal=$this->cac($dom,$root,'TaxTotal');$this->amount($dom,$taxTotal,'TaxAmount',$sale->tax_cents);
        $taxTotal2=$this->cac($dom,$root,'TaxTotal');$this->amount($dom,$taxTotal2,'TaxAmount',$sale->tax_cents);$sub=$this->cac($dom,$taxTotal2,'TaxSubtotal');$this->amount($dom,$sub,'TaxableAmount',$sale->taxable_cents);$this->amount($dom,$sub,'TaxAmount',$sale->tax_cents);$cat=$this->cac($dom,$sub,'TaxCategory');$this->cbc($dom,$cat,'ID','S');$this->cbc($dom,$cat,'Percent',number_format($sale->tax_rate_units/100,2,'.',''));$scheme=$this->cac($dom,$cat,'TaxScheme');$this->cbc($dom,$scheme,'ID','VAT');
        $legal=$this->cac($dom,$root,'LegalMonetaryTotal');$this->amount($dom,$legal,'LineExtensionAmount',$sale->subtotal_cents);$this->amount($dom,$legal,'TaxExclusiveAmount',$sale->taxable_cents);$this->amount($dom,$legal,'TaxInclusiveAmount',$sale->total_cents);$this->amount($dom,$legal,'AllowanceTotalAmount',$sale->discount_cents);$this->amount($dom,$legal,'PayableAmount',$sale->total_cents);
        foreach($sale->lines->values() as $i=>$line){$node=$this->cac($dom,$root,'InvoiceLine');$this->cbc($dom,$node,'ID',(string)($i+1));$qty=$this->cbc($dom,$node,'InvoicedQuantity',(string)$line->quantity);$qty->setAttribute('unitCode',$this->unitCode($line->unit));$this->amount($dom,$node,'LineExtensionAmount',$line->total_cents);$lineTax=$this->cac($dom,$node,'TaxTotal');$tax=(int)round($line->total_cents*$sale->tax_rate_units/10000);$this->amount($dom,$lineTax,'TaxAmount',$tax);$this->amount($dom,$lineTax,'RoundingAmount',$line->total_cents+$tax);$item=$this->cac($dom,$node,'Item');$this->cbc($dom,$item,'Name',$line->name);$class=$this->cac($dom,$item,'ClassifiedTaxCategory');$this->cbc($dom,$class,'ID','S');$this->cbc($dom,$class,'Percent',number_format($sale->tax_rate_units/100,2,'.',''));$scheme=$this->cac($dom,$class,'TaxScheme');$this->cbc($dom,$scheme,'ID','VAT');$price=$this->cac($dom,$node,'Price');$this->amount($dom,$price,'PriceAmount',$line->price_cents);}
        return $dom->saveXML();
    }

    private function party(DOMDocument $dom,$root,string $name,string $legalName,?string $vat,?string $cr,?string $address,?string $city,?string $postal,?string $building=null,?string $street=null,?string $district=null,?string $additional=null,string $countryCode='SA',bool $seller=false): void
    { $wrapper=$this->cac($dom,$root,$name);$party=$this->cac($dom,$wrapper,'Party');if($cr){$id=$this->cac($dom,$party,'PartyIdentification');$identifier=$this->cbc($dom,$id,'ID',$cr);if($seller)$identifier->setAttribute('schemeID','CRN');} if($seller||$address||$city||$postal||$building||$street||$district){$postalNode=$this->cac($dom,$party,'PostalAddress');$this->cbc($dom,$postalNode,'StreetName',$street?:($address?:'Not provided'));if($building)$this->cbc($dom,$postalNode,'BuildingNumber',$building);if($additional)$this->cbc($dom,$postalNode,'PlotIdentification',$additional);if($district)$this->cbc($dom,$postalNode,'CitySubdivisionName',$district);$this->cbc($dom,$postalNode,'CityName',$city?:'Not provided');if($postal)$this->cbc($dom,$postalNode,'PostalZone',$postal);$country=$this->cac($dom,$postalNode,'Country');$this->cbc($dom,$country,'IdentificationCode',$countryCode);}if($vat){$tax=$this->cac($dom,$party,'PartyTaxScheme');$this->cbc($dom,$tax,'CompanyID',$vat);$scheme=$this->cac($dom,$tax,'TaxScheme');$this->cbc($dom,$scheme,'ID','VAT');}$legal=$this->cac($dom,$party,'PartyLegalEntity');$this->cbc($dom,$legal,'RegistrationName',$legalName); }
    private function qrReference(DOMDocument $dom,$root,string $payload): void{$ref=$this->cac($dom,$root,'AdditionalDocumentReference');$this->cbc($dom,$ref,'ID','QR');$att=$this->cac($dom,$ref,'Attachment');$node=$this->cbc($dom,$att,'EmbeddedDocumentBinaryObject',$payload);$node->setAttribute('mimeCode','text/plain');}
    private function phaseOneQr(string $seller,string $vat,$issuedAt,int $totalCents,int $taxCents): string{$tlv='';foreach([1=>$seller,2=>$vat,3=>$issuedAt->toIso8601String(),4=>Pay::decimal($totalCents),5=>Pay::decimal($taxCents)] as $tag=>$value){$bytes=(string)$value;$tlv.=chr($tag).chr(strlen($bytes)).$bytes;}return base64_encode($tlv);}
    private function cbc(DOMDocument $dom,$parent,string $name,$value){$n=$dom->createElementNS(self::NS_CBC,'cbc:'.$name);$n->appendChild($dom->createTextNode((string)$value));$parent->appendChild($n);return $n;}
    private function cac(DOMDocument $dom,$parent,string $name){$n=$dom->createElementNS(self::NS_CAC,'cac:'.$name);$parent->appendChild($n);return $n;}
    private function amount(DOMDocument $dom,$parent,string $name,int $cents){$n=$this->cbc($dom,$parent,$name,Pay::decimal($cents));$n->setAttribute('currencyID','SAR');return $n;}
    private function unitCode(?string $unit): string{$map=['piece'=>'PCE','pair'=>'PR','box'=>'BX','set'=>'SET','hour'=>'HUR','kg'=>'KGM'];return $map[strtolower((string)$unit)]??'PCE';}
    private function canonicalize(string $xml): string{$dom=new DOMDocument();$dom->loadXML($xml,LIBXML_NONET);return $dom->C14N(false,false);}
    private function fail(string $field,string $message): void{throw ValidationException::withMessages([$field=>$message]);}
}
