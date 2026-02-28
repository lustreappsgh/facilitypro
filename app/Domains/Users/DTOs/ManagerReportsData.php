<?php

namespace App\Domains\Users\DTOs;

class ManagerReportsData
{
    /**
     * @param  array<int, int>  $report_ids
     */
    public function __construct(
        public array $report_ids = [],
    ) {}
}
