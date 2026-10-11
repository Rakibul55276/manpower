<?php

namespace App\Modules\SafetyShop\Products\Services;

use App\Modules\SafetyShop\Products\Models\Product;
use RuntimeException;

class BarcodeGenerator
{
    /**
     * Generate a 13-digit internal-use barcode.
     *
     * The 12-digit body is: 2 + company (5 digits) + product sequence (6 digits).
     * The final digit is the standard EAN-13 check digit. Prefix 2 identifies the
     * value as an internal/restricted-distribution code, not a GS1-issued GTIN.
     */
    public function forProduct(Product $product): string
    {
        $companyId = (int) $product->company_id;
        $sequence = (int) $product->id;

        if ($companyId < 1 || $companyId > 99999) {
            throw new RuntimeException('The company ID is outside the supported barcode range.');
        }

        for ($offset = 0; $offset < 1000; $offset++) {
            $candidateSequence = $sequence + $offset;
            if ($candidateSequence > 999999) {
                break;
            }

            $body = '2'
                .str_pad((string) $companyId, 5, '0', STR_PAD_LEFT)
                .str_pad((string) $candidateSequence, 6, '0', STR_PAD_LEFT);
            $candidate = $body.$this->checkDigit($body);

            $alreadyUsed = Product::where('company_id', $companyId)
                ->where('barcode', $candidate)
                ->where('id', '<>', $product->id)
                ->exists();

            if (!$alreadyUsed) {
                return $candidate;
            }
        }

        throw new RuntimeException('Unable to allocate a unique internal barcode.');
    }

    private function checkDigit(string $body): int
    {
        $sum = 0;
        foreach (str_split($body) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return (10 - ($sum % 10)) % 10;
    }
}
