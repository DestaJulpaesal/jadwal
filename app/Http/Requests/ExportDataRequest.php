<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ExportDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Transaction::class) ?? false;
    }

    public function rules(): array
    {
        $transactions = $this->route('type') === 'transactions';

        return [
            'start_date' => [Rule::requiredIf($transactions), 'nullable', 'date_format:Y-m-d'],
            'end_date' => [
                Rule::requiredIf($transactions),
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal akhir',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->route('type') !== 'transactions'
                || $validator->errors()->has('start_date')
                || $validator->errors()->has('end_date')) {
                return;
            }

            $start = CarbonImmutable::createFromFormat('!Y-m-d', $this->input('start_date'));
            $end = CarbonImmutable::createFromFormat('!Y-m-d', $this->input('end_date'));

            if ($start !== false && $end !== false && $end->gt($start->addYearsNoOverflow(5))) {
                $validator->errors()->add('end_date', __('ui.export.max_range'));
            }
        });
    }
}
