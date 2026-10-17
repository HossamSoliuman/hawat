<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Customer;
use App\Models\DalalInvoiceReview;
use App\Models\DalalPartnership;
use App\Models\DalalWorker;
use App\Models\DalalWorkerCount;
use App\Models\Sale;
use App\Models\User;

/**
 * سجلات الدلال تُقرأ مقيّدةً به: فاتورة دلال آخر أو عميله أو طلبٌ لم يُرسل
 * إليه = 404 في الويب والـAPI.
 */
trait ResolvesDalalRecords
{
    protected function dalalSale(User $dalal, int|string $id): Sale
    {
        return Sale::forSeller($dalal)->findOrFail($id);
    }

    protected function dalalCustomer(User $dalal, int|string $id): Customer
    {
        return Customer::forAccount($dalal)->findOrFail($id);
    }

    protected function dalalPartnership(User $dalal, int|string $id): DalalPartnership
    {
        return DalalPartnership::forDalal($dalal)->findOrFail($id);
    }

    /**
     * مراجعة مالك لفاتورة من فواتير هذا الدلال.
     */
    protected function dalalInvoiceReview(User $dalal, Sale $sale, int|string $id): DalalInvoiceReview
    {
        return DalalInvoiceReview::forDalal($dalal)->where('sale_id', $sale->id)->findOrFail($id);
    }

    protected function dalalWorker(User $dalal, int|string $id): DalalWorker
    {
        return DalalWorker::forDalal($dalal)->findOrFail($id);
    }

    protected function dalalWorkerCount(User $dalal, int|string $id): DalalWorkerCount
    {
        return DalalWorkerCount::forDalal($dalal)->findOrFail($id);
    }
}
