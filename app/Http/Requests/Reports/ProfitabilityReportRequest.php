<?php

namespace App\Http\Requests\Reports;

class ProfitabilityReportRequest extends ReportRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->dateRangeRules();
    }
}
