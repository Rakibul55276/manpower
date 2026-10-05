<?php
namespace App\Modules\Zatca\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Models\InvoiceSetting;

class ZatcaController extends Controller
{
    public function index()
    {
        $settings = InvoiceSetting::firstOrFail();
        $stats = [
            'connected' => false,
            'compliance_csid' => false,
            'production_csid' => false,
            'submitted' => Invoice::whereNotIn('zatca_status', ['not_connected', 'not_submitted'])->count(),
        ];
        return view('zatca.index', compact('settings', 'stats'));
    }
}
