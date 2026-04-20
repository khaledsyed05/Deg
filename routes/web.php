<?php

use App\Http\Controllers\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Admin\ClubController as AdminClubController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GeographyController as AdminGeographyController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\SettlementController as AdminSettlementController;
use App\Http\Controllers\Admin\WhatsAppController as AdminWhatsAppController;
use Dedoc\Scramble\Scramble;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/locale', function (Request $request) {
    $locale = $request->input('locale', 'ar');
    if (! in_array($locale, ['ar', 'en'])) {
        $locale = 'ar';
    }
    session(['locale' => $locale]);

    return redirect()->back();
})->name('locale.switch');

/*
|--------------------------------------------------------------------------
| Admin Panel (Inertia)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminLoginController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminLoginController::class, 'login']);
    Route::post('logout', [AdminLoginController::class, 'logout'])->name('logout');
});

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::prefix('clubs')->name('clubs.')->group(function () {
        Route::get('/', [AdminClubController::class, 'index'])->name('index');
        Route::post('{club}/approve', [AdminClubController::class, 'approve'])->name('approve');
        Route::post('{club}/reject', [AdminClubController::class, 'reject'])->name('reject');
    });

    Route::prefix('settlements')->name('settlements.')->group(function () {
        Route::get('/', [AdminSettlementController::class, 'index'])->name('index');
        Route::post('/', [AdminSettlementController::class, 'store'])->name('store');
        Route::get('{settlement}', [AdminSettlementController::class, 'show'])->name('show');
    });

    Route::prefix('geography')->name('geography.')->group(function () {
        Route::get('/cities', [AdminGeographyController::class, 'cities'])->name('cities');
        Route::post('/cities/{city}/toggle', [AdminGeographyController::class, 'toggleCity'])->name('cities.toggle');
    });

    Route::get('profile', [AdminProfileController::class, 'show'])->name('profile');
    Route::put('profile', [AdminProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password');

    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        Route::get('clubs/{club}', [AdminWhatsAppController::class, 'show'])->name('show');
        Route::post('clubs/{club}/init', [AdminWhatsAppController::class, 'initiate'])->name('initiate');
        Route::delete('clubs/{club}', [AdminWhatsAppController::class, 'disconnect'])->name('disconnect');
    });
});

if (app()->environment('local') && class_exists(Scramble::class)) {
    Scramble::registerUiRoute('api/documentation');
    Scramble::registerJsonSpecificationRoute('api/documentation.json');
}
