<?php

namespace App\Http\Requests\Owner;

use App\Services\Owner\OwnerDashboard;
use Illuminate\Validation\Rule;

/**
 * فترة رئيسة المالك: اليوم أو الأسبوع أو الشهر أو السنة، وبلا فترة كل ما مضى.
 */
class DashboardRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::in(OwnerDashboard::PERIODS)],
        ];
    }

    public function attributes(): array
    {
        return ['period' => 'الفترة'];
    }

    public function period(): string
    {
        return $this->validated('period') ?? 'all';
    }
}
