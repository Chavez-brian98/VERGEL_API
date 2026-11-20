<?php

namespace App\Http\Requests\Reports;

class CollectionsReportRequest extends ReportRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), $this->paginationRules(), [
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ]);
    }
}
