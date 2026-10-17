<?php

namespace App\Http\Requests\Dalal;

use App\Models\DalalWorker;
use Illuminate\Validation\Rule;

/**
 * عامل في الدكة سجلًّا فرديًّا — الويب والتطبيق. رقم الهوية لا يتكرر عند
 * الدلال نفسه. التعطيل بالحالة لا بالحذف.
 */
class WorkerRequest extends DalalRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^05\d{8}$/'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'id_type_id' => ['nullable', $this->lookup('id_types')],
            'id_number' => ['nullable', 'string', 'max:50', Rule::unique('dalal_workers', 'id_number')->where('dalal_id', $this->dalal()->id)->ignore($this->route('worker'))],
            'dalal_worker_type_id' => ['required', $this->lookup('dalal_worker_types')],
            'status' => ['nullable', Rule::in(DalalWorker::STATUSES)],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'id_number.unique' => 'رقم الهوية مسجّل لعامل آخر في دكتك.',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'اسم العامل', 'dalal_worker_type_id' => 'نوع العامل', 'id_number' => 'رقم الهوية'];
    }
}
