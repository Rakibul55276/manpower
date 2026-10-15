<?php

namespace Tests\Unit;

use App\Modules\Zatca\Models\ZatcaDevice;
use App\Modules\Zatca\Models\ZatcaDocument;
use App\Modules\Zatca\Services\FatooraClient;
use App\Modules\Zatca\Services\ZatcaQrService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZatcaIntegrationTest extends TestCase
{
    public function test_signed_phase_two_qr_is_extracted_and_rendered(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
  <cac:AdditionalDocumentReference><cbc:ID>QR</cbc:ID><cac:Attachment><cbc:EmbeddedDocumentBinaryObject mimeCode="text/plain">AQNUZXN0</cbc:EmbeddedDocumentBinaryObject></cac:Attachment></cac:AdditionalDocumentReference>
</Invoice>
XML;
        $document = new ZatcaDocument(['signed_xml' => $xml]);
        $service = app(ZatcaQrService::class);

        $this->assertSame('AQNUZXN0', $service->extract($xml));
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $service->dataUri($document));
    }

    public function test_simulation_uses_entered_otp_instead_of_overwriting_it(): void
    {
        Http::fake(['*' => Http::response([
            'binarySecurityToken' => 'certificate',
            'secret' => 'secret',
            'requestID' => 'request-1',
        ])]);
        config(['zatca.endpoints.simulation' => 'https://simulation.example.test', 'zatca.simulation_otp' => '123345']);
        $device = new ZatcaDevice([
            'environment' => 'simulation',
            'csr' => "-----BEGIN CERTIFICATE REQUEST-----\nTEST\n-----END CERTIFICATE REQUEST-----",
        ]);

        app(FatooraClient::class)->complianceCsid($device, '654321');

        Http::assertSent(fn (Request $request) => $request->hasHeader('OTP', '654321') && $request->hasHeader('Accept-Version', 'V2'));
    }
}
