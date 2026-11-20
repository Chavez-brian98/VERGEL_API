<?php

namespace App\Http\Requests\Reports;

class PurchasesReportRequest extends ReportRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), $this->paginationRules(), [
            'supplier' => ['nullable', 'string', 'max:150'],
        ]);
    }
}
