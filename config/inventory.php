<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Low-stock threshold
    |--------------------------------------------------------------------------
    |
    | Products with a stock level below this number are considered low stock.
    | The API also accepts a "threshold" query parameter to override it for a
    | single request.
    |
    */
    'low_stock_threshold' => (int) env('LOW_STOCK_THRESHOLD', 5),
];
