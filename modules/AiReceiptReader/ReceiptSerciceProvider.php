<?php

namespace Modules\AiReceiptReader;

use Illuminate\Support\ServiceProvider;

class ReceiptServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'aireceipt');
    }
}
