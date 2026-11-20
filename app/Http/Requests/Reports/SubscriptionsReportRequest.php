<?php

namespace App\Http\Requests\Reports;

class SubscriptionsReportRequest extends ReportRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), $this->paginationRules(), [
            'plan_id' => ['nullable', 'integer', 'exists:subscription_plans,id'],
        ]);
    }
}
