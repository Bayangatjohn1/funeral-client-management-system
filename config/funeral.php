<?php

return [
    // Senior citizen discount applied when deceased age is 60+.
    'senior_discount_percent' => env('FUNERAL_SENIOR_DISCOUNT_PERCENT', 20),
    // PWD discount applied when verified in intake.
    'pwd_discount_percent' => env('FUNERAL_PWD_DISCOUNT_PERCENT', 20),
    // Daily batch cutoff for other-branch reporting (24-hour format).
    'other_branch_report_cutoff_hour' => env('FUNERAL_OTHER_BRANCH_CUTOFF_HOUR', 18),
];
