<?php

namespace App\Http\Requests\Owner;

/**
 * المالك يحصّل دفعة على فاتورة لم تُسدَّد كاملة — لا تتجاوز المتبقي.
 */
class SalePaymentRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function attributes(): array
    {
        return ['amount' => 'المبلغ'];
    }
}
