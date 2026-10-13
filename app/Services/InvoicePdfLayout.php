<?php
namespace App\Services;
use Dompdf\Dompdf;
use Dompdf\Options;

class InvoicePdfLayout
{
    public static function render(array $data, Options $options): Dompdf
    {
        $lines = $data['sale']->lines->values();
        $data['invoiceLinePages'] = collect([$lines]);
        $data['invoiceClosingSpacer'] = 0;
        $pdf = static::pass($data, $options, $metrics);
        $pageBottom = $pdf->getCanvas()->get_height() - 54;
        $firstBudget = $pageBottom - ($metrics['firstRowY'] ?? 350) - 8;
        $nextBudget = $pageBottom - 26 - 65 - ($metrics['headingHeight'] ?? 35) - 8;
        $heights = [];
        foreach ($lines as $index => $line) $heights[] = max(20, $metrics['rows'][$index] ?? 30);
        $closingHeight = ($metrics['closingHeight'] ?? 240) + 5;
        for ($attempt = 0; $attempt < 4; $attempt++) {
            $plan = static::plan($heights, $firstBudget - $attempt * 18, $nextBudget - $attempt * 18, $closingHeight);
            $offset = 0;
            $data['invoiceLinePages'] = collect($plan)->map(function ($count) use ($lines, &$offset) {
                $page = $lines->slice($offset, $count)->values(); $offset += $count; return $page;
            });
            $pdf = static::pass($data, $options, $metrics);
            if ($pdf->getCanvas()->get_page_count() === count($plan)) break;
        }
        if (isset($metrics['closingBottom'])) {
            $pageCount = $pdf->getCanvas()->get_page_count();
            $space = max(0, $pageBottom - $metrics['closingBottom'] - 3);
            for ($attempt = 0; $space > 1 && $attempt < 3; $attempt++, $space -= 10) {
                $data['invoiceClosingSpacer'] = $space;
                $candidate = static::pass($data, $options, $ignored);
                if ($candidate->getCanvas()->get_page_count() === $pageCount) return $candidate;
            }
        }
        return $pdf;
    }

    /** Pack measured rows, reserve closing space, and balance the final page. */
    public static function plan(array $heights, float $firstBudget, float $nextBudget, float $closingHeight): array
    {
        if (!$heights) return [0];
        $plan = []; $offset = 0; $total = count($heights);
        while ($offset < $total) {
            $budget = $plan ? $nextBudget : $firstBudget;
            $remaining = array_slice($heights, $offset);
            if (array_sum($remaining) + $closingHeight <= $budget) { $plan[] = count($remaining); break; }
            $count = 0; $height = 0;
            foreach ($remaining as $rowHeight) {
                if ($height + $rowHeight > $budget) break;
                $height += $rowHeight; $count++;
            }
            $count = max(1, $count);
            if ($count >= count($remaining) && count($remaining) > 1) $count = max(1, count($remaining) - 2);
            $plan[] = $count; $offset += $count;
        }
        if (count($plan) > 1 && end($plan) === 1 && $plan[count($plan)-2] > 1 && array_sum(array_slice($heights,-2)) + $closingHeight <= $nextBudget) {
            $plan[count($plan)-2]--; $plan[count($plan)-1]++;
        }
        return $plan;
    }

    private static function pass(array $data, Options $options, &$metrics): Dompdf
    {
        $metrics = ['rows'=>[]];
        $pdf = new Dompdf(clone $options); $pdf->setPaper('A4', 'portrait');
        $pdf->setCallbacks([['event'=>'end_frame', 'f'=>function ($frame) use (&$metrics) {
            $node = $frame->get_node();
            if (!$node instanceof \DOMElement) return;
            if ($node->hasAttribute('data-invoice-row')) {
                $index = (int)$node->getAttribute('data-invoice-row');
                $metrics['rows'][$index] = $frame->get_margin_height();
                if ($index === 0) $metrics['firstRowY'] = $frame->get_position('y');
            }
            if ($node->getAttribute('class') === 'invoice-items-heading') $metrics['headingHeight'] = $frame->get_margin_height();
            if (preg_match('/\blast-page-block\b/', $node->getAttribute('class'))) {
                $metrics['closingHeight'] = $frame->get_margin_height();
                $metrics['closingBottom'] = $frame->get_position('y') + $frame->get_margin_height();
            }
        }]]);
        $pdf->loadHtml(view('pdf.safety-shop-bulk-invoice', $data)->render(), 'UTF-8'); $pdf->render(); return $pdf;
    }
}
