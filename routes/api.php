<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TempEmailController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\DomainController;
use App\Http\Controllers\Api\TemplateController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    
    // Public endpoints - Temp Email Management
    Route::prefix('email')->group(function () {
        Route::post('/generate', [TempEmailController::class, 'generate']);
        Route::get('/{email}', [TempEmailController::class, 'show']);
        Route::get('/{email}/inbox', [TempEmailController::class, 'getInbox']);
        Route::get('/{email}/check', [TempEmailController::class, 'checkNew']);
        Route::delete('/{email}', [TempEmailController::class, 'delete']);
    });

    // Message endpoints
    Route::prefix('message')->group(function () {
        Route::get('/{id}', [MessageController::class, 'show']);
        Route::get('/{id}/html', [MessageController::class, 'showHtml']);
        Route::post('/{id}/read', [MessageController::class, 'markAsRead']);
        Route::delete('/{id}', [MessageController::class, 'delete']);
    });

    // Attachment download
    Route::get('/attachment/{id}/download', [MessageController::class, 'downloadAttachment'])
        ->name('api.attachment.download');

    // Public domain list
    Route::get('/domains', [DomainController::class, 'index']);

    // Admin/Authenticated endpoints
    Route::middleware(['auth:sanctum'])->group(function () {
        
        // Domain management (admin only)
        Route::prefix('admin/domains')->group(function () {
            Route::post('/', [DomainController::class, 'store']);
            Route::put('/{id}', [DomainController::class, 'update']);
            Route::delete('/{id}', [DomainController::class, 'destroy']);
        });

        // Template management (admin only)
        Route::prefix('admin/templates')->group(function () {
            Route::get('/', [TemplateController::class, 'index']);
            Route::get('/{id}', [TemplateController::class, 'show']);
            Route::post('/', [TemplateController::class, 'store']);
            Route::put('/{id}', [TemplateController::class, 'update']);
            Route::delete('/{id}', [TemplateController::class, 'destroy']);
            Route::post('/{id}/preview', [TemplateController::class, 'preview']);
        });
    });
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});
