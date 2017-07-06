<?php return [
    'app_init' => [],
    'app_begin' => [],
    'module_init' =>
        [
            0 => 'app\\common\\behavior\\WebLog',
        ],
    'action_begin' => [],
    'view_filter' => [],
    'log_write' => [],
    'app_end' =>
        [
            0 => 'app\\admin\\behavior\\Cron',
        ]
];