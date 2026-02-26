<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dateTime('cancelled_at')->nullable()->after('sale_status');
            $table->dateTime('refunded_at')->nullable()->after('cancelled_at');
            $table->dateTime('stock_restocked_at')->nullable()->after('refunded_at');
        });

        $this->backfillMissingInvoices();
        $this->deduplicateInvoices();

        Schema::table('sales', function (Blueprint $table) {
            $table->unique('invoice_no', 'sales_invoice_no_unique');
            $table->index('sold_at', 'sales_sold_at_index');
            $table->index('customer_name', 'sales_customer_name_index');
            $table->index('payment_status', 'sales_payment_status_index');
            $table->index('sale_status', 'sales_sale_status_index');
            $table->index(['sold_at', 'payment_status'], 'sales_sold_payment_index');
            $table->index(['cancelled_at', 'refunded_at'], 'sales_lifecycle_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_lifecycle_index');
            $table->dropIndex('sales_sold_payment_index');
            $table->dropIndex('sales_sale_status_index');
            $table->dropIndex('sales_payment_status_index');
            $table->dropIndex('sales_customer_name_index');
            $table->dropIndex('sales_sold_at_index');
            $table->dropUnique('sales_invoice_no_unique');
            $table->dropColumn(['cancelled_at', 'refunded_at', 'stock_restocked_at']);
        });
    }

    private function backfillMissingInvoices(): void
    {
        DB::table('sales')
            ->select(['id', 'invoice_no', 'sold_at'])
            ->where(function ($query): void {
                $query->whereNull('invoice_no')->orWhere('invoice_no', '');
            })
            ->orderBy('id')
            ->chunkById(200, function ($sales): void {
                foreach ($sales as $sale) {
                    $date = 'LEGACY';

                    if (! empty($sale->sold_at)) {
                        try {
                            $date = Carbon::parse((string) $sale->sold_at)->format('Ymd');
                        } catch (\Throwable) {
                            $date = 'LEGACY';
                        }
                    }

                    DB::table('sales')
                        ->where('id', $sale->id)
                        ->update([
                            'invoice_no' => 'INV-'.$date.'-'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT),
                        ]);
                }
            });
    }

    private function deduplicateInvoices(): void
    {
        $duplicates = DB::table('sales')
            ->select('invoice_no')
            ->whereNotNull('invoice_no')
            ->where('invoice_no', '!=', '')
            ->groupBy('invoice_no')
            ->havingRaw('count(*) > 1')
            ->pluck('invoice_no');

        foreach ($duplicates as $invoiceNo) {
            $ids = DB::table('sales')
                ->where('invoice_no', $invoiceNo)
                ->orderBy('id')
                ->pluck('id')
                ->values();

            foreach ($ids->slice(1) as $id) {
                DB::table('sales')
                    ->where('id', $id)
                    ->update([
                        'invoice_no' => trim((string) $invoiceNo).'-'.$id,
                    ]);
            }
        }
    }
};
