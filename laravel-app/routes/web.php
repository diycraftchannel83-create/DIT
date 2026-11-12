<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Temporary route-loading diagnostic; returns no configuration or database data.
Route::get('/route-probe-2026', function () {
    return response('LARAVEL_WEB_ROUTES_LOADED', 200)
        ->header('Content-Type', 'text/plain; charset=UTF-8')
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
})->name('diagnostic.route-probe');

Route::match(['GET', 'POST'], '/academic-inspect-check-2026', function (Request $request) {
    $token = (string) env('ACADEMIC_MAINTENANCE_TOKEN', '');

    abort_if($token === '', 404, 'Maintenance endpoint is disabled.');
    
    if ($request->isMethod('GET')) {
        return response()->make(
            '<!doctype html><html lang="id"><meta charset="utf-8"><title>SIAKAD Inspector</title>'
            . '<body style="font-family:Arial;max-width:640px;margin:48px auto">'
            . '<h2>SIAKAD Database Inspector</h2>'
            . '<p>Endpoint sementara. Gunakan token yang disimpan di .env.</p>'
            . '<form method="POST">' . csrf_field()
            . '<input type="password" name="token" required autocomplete="off" placeholder="Maintenance token">'
            . '<button type="submit">Jalankan academic:inspect</button></form></body></html>'
        );
    }

    abort_unless(hash_equals($token, (string) $request->input('token')), 403);

    try {
        Artisan::call('academic:inspect');
        $output = Artisan::output();

        return response(
            '<pre>' . htmlspecialchars($output, ENT_QUOTES, 'UTF-8') . '</pre>'
        );
    } catch (\Throwable $exception) {
        report($exception);

        return response('Pemeriksaan gagal. Periksa storage/logs/laravel.log.', 500);
    }
})->name('academic.inspect.web');
