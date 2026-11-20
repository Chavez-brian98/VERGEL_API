<?php

namespace App\Http\Requests\Reports;

class PlantsReportRequest extends ReportRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), $this->paginationRules(), [
            'plant_id' => ['nullable', 'integer', 'exists:plants,id'],
        ]);
    }
}
