<?php
namespace App\Modules\Zatca\Services;

use App\Modules\Zatca\Models\ZatcaDocument;
use App\Services\Documents;
use DOMDocument;
use DOMXPath;

class ZatcaQrService
{
    public function dataUri(?ZatcaDocument $document, ?string $fallback = null): ?string
    {
        $value=$document ? $this->extract($document->cleared_xml ?: $document->signed_xml) : null;
        if(!filled($value))$value=$fallback;
        return filled($value)?Documents::qrDataUri($value):null;
    }

    public function extract(?string $xml): ?string
    {
        if(!filled($xml))return null;
        $dom=new DOMDocument();
        if(!@$dom->loadXML($xml,LIBXML_NONET))return null;
        $xpath=new DOMXPath($dom);
        $xpath->registerNamespace('cac','urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $xpath->registerNamespace('cbc','urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $node=$xpath->query("//cac:AdditionalDocumentReference[cbc:ID='QR']/cac:Attachment/cbc:EmbeddedDocumentBinaryObject")->item(0);
        return $node?trim($node->textContent):null;
    }
}
