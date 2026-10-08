<?php
namespace App\Services;

use App\Models\Company;
use App\Models\SaasVoucher;
use App\Models\SaasVoucherRedemption;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaasVoucherService
{
    public function issue(array $data, User $issuer): string
    {
        $plain = 'VCH-'.strtoupper(bin2hex(random_bytes(4))).'-'.strtoupper(bin2hex(random_bytes(4))).'-'.strtoupper(bin2hex(random_bytes(4)));
        SaasVoucher::create([
            'code_hash'=>hash('sha256', $this->normalize($plain)),
            'code_prefix'=>substr($plain, 0, 12),
            'duration_days'=>(int)$data['duration_days'],
            'assigned_company_id'=>$data['assigned_company_id'] ?? null,
            'valid_until'=>$data['valid_until'] ?? null,
            'created_by'=>$issuer->id,
        ]);
        return $plain;
    }

    public function redeem(Company $company, string $code, User $user): SaasVoucherRedemption
    {
        return DB::transaction(function () use ($company, $code, $user) {
            $company = Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
            $voucher = SaasVoucher::where('code_hash', hash('sha256', $this->normalize($code)))->lockForUpdate()->first();
            if (!$voucher || $voucher->status !== 'unused') $this->fail('voucher', 'Voucher is invalid or has already been used.');
            if ($voucher->valid_until && $voucher->valid_until->lt(now()->startOfDay())) $this->fail('voucher', 'Voucher has expired.');
            if ($voucher->assigned_company_id && $voucher->assigned_company_id !== $company->id) $this->fail('voucher', 'Voucher is assigned to another company.');
            if ($company->subscription_status === 'suspended') $this->fail('voucher', 'Suspended companies cannot redeem vouchers.');

            $today = now()->startOfDay();
            $previous = $company->subscription_expires_at;
            $base = $previous && $previous->gte($today) ? $previous->copy() : $today;
            $expiry = $base->addDays($voucher->duration_days);
            $company->update([
                'subscription_status'=>'active',
                'subscription_started_at'=>$company->subscription_started_at ?: $today,
                'subscription_expires_at'=>$expiry,
                'subscription_grace_until'=>$expiry->copy()->addDays(max(0, (int)config('saas.grace_days', 7))),
            ]);
            $voucher->update(['status'=>'redeemed', 'redeemed_company_id'=>$company->id, 'redeemed_at'=>now()]);
            return SaasVoucherRedemption::create([
                'voucher_id'=>$voucher->id, 'company_id'=>$company->id, 'redeemed_by'=>$user->id,
                'previous_expiry'=>$previous, 'new_expiry'=>$expiry, 'redeemed_at'=>now(),
            ]);
        }, 3);
    }

    private function normalize(string $code): string { return strtoupper(preg_replace('/\s+/', '', trim($code))); }
    private function fail(string $field, string $message): void { throw ValidationException::withMessages([$field=>$message]); }
}
