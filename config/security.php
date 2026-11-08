<?php

return [
    'csp_mode' => env('CSP_MODE', 'off'),
    'csp_report_uri' => env('CSP_REPORT_URI', '/csp-report'),
    'csp_report_max_bytes' => 16384,
];
