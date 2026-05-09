<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Modules\Administration\Entities\ErrorLog;

class ErrorLogService
{
    public static function save(string $module, string $method, string $message)
    {
        try {
            ErrorLog::create([
                'module'  => $module,
                'methode' => $method,
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to save error log: " . $e->getMessage());
        }
    }
}
