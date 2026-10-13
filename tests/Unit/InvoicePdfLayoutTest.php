<?php
namespace Tests\Unit;
use App\Services\InvoicePdfLayout;
use PHPUnit\Framework\TestCase;

class InvoicePdfLayoutTest extends TestCase
{
    public function test_seven_rows_fit_when_measured_space_allows_it(): void
    {
        $this->assertSame([7], InvoicePdfLayout::plan(array_fill(0,7,20),200,300,50));
    }
    public function test_final_single_row_is_balanced_without_losing_items(): void
    {
        $this->assertSame([5,2], InvoicePdfLayout::plan(array_fill(0,7,30),180,300,100));
    }
    public function test_tall_rows_reserve_room_for_closing_details(): void
    {
        $this->assertSame([1,2], InvoicePdfLayout::plan([100,100,100],220,300,100));
    }
    public function test_many_pages_preserve_every_item(): void
    {
        $plan=InvoicePdfLayout::plan(array_fill(0,40,30),350,600,180);
        $this->assertSame(40,array_sum($plan));
        $this->assertGreaterThan(2,count($plan));
        $this->assertGreaterThan(1,end($plan));
        $this->assertLessThanOrEqual(600,end($plan)*30+180);
    }
}
