<?php

use App\Jobs\ImportFrom1CJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::any('/exchange', function (Request $request) {

    $type = $request->query('type');
    $mode = $request->query('mode');
    $filename = $request->query('filename');

    Log::channel('onec')->info('1C request received', [
        'type' => $type,
        'mode' => $mode,
        'filename' => $filename,
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'timestamp' => now()->toDateTimeString(),
    ]);

    /*
    |--------------------------------------------------------------------------
    | 1️⃣ checkauth
    |--------------------------------------------------------------------------
    */
    if ($type === 'catalog' && $mode === 'checkauth') {
        return response(
            "success\nSESSION_ID\nsession123",
            200,
            ['Content-Type' => 'text/plain']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 2️⃣ init
    |--------------------------------------------------------------------------
    */
    if ($type === 'catalog' && $mode === 'init') {
        return response(
            "zip=no\nfile_limit=10485760",
            200,
            ['Content-Type' => 'text/plain']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 3️⃣ file — сохраняем ВСЕ файлы (XML + картинки)
    |--------------------------------------------------------------------------
    */
    if ($type === 'catalog' && $mode === 'file' && $filename) {

        $rawData = file_get_contents('php://input');

        if ($rawData === false || $rawData === '') {
            Log::channel('onec')->warning('Empty file received from 1C', [
                'filename' => $filename,
            ]);

            return response('success', 200, ['Content-Type' => 'text/plain']);
        }

        // защита от ../
        $safeFilename = ltrim(str_replace('..', '', $filename), '/');

        Storage::disk('local')->put("onec/{$safeFilename}", $rawData);

        Log::channel('onec')->info('1C file saved', [
            'file' => $safeFilename,
            'size' => strlen($rawData),
        ]);

        return response('success', 200, ['Content-Type' => 'text/plain']);
    }

    /*
    |--------------------------------------------------------------------------
    | 4️⃣ import — ЗАПУСК обработки (БЕЗ чтения php://input)
    |--------------------------------------------------------------------------
    */
    if ($type === 'catalog' && $mode === 'import') {

        Log::channel('onec')->info('1C import command received', [
            'filename' => $filename,
        ]);

        \Log::info('Dispatching ImportFrom1CJob', [
            'filename' => $filename,
        ]);

        ImportFrom1CJob::dispatch($filename);

        return response('success', 200, ['Content-Type' => 'text/plain']);
    }

    /*
    |--------------------------------------------------------------------------
    | 5️⃣ fallback
    |--------------------------------------------------------------------------
    */
    return response('success', 200, ['Content-Type' => 'text/plain']);
})->middleware('auth.basic');
