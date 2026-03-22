<?php

use Combindma\Strapi\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('strapi/webhook', [WebhookController::class, 'webhookHandler'])
    ->name('laravel-strapi.webhook');
