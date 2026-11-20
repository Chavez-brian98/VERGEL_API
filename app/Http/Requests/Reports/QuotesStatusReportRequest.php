<?php

namespace App\Http\Requests\Reports;

class QuotesStatusReportRequest extends ReportRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        ]);
    }
}
