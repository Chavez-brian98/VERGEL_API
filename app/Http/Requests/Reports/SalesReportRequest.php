<?php

namespace App\Http\Requests\Reports;

class SalesReportRequest extends ReportRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), $this->paginationRules(), [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        ]);
    }
}
