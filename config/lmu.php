<?php

return [
    'pagination' => [
        'min' => (int) env('LMU_PAGINATION_MIN', 10),
        'max' => (int) env('LMU_PAGINATION_MAX', 100),
        'default' => (int) env('LMU_PAGINATION_DEFAULT', 30),
    ],
];
