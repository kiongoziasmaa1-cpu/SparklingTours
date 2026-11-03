<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/tours', [PublicController::class, 'catalog'])->name('tours.index');
Route::get('/explore/{region}/{category}', [PublicController::class, 'catalog'])->name('catalog.index');
Route::get('/experience/{slug}', [PublicController::class, 'detail'])->name('catalog.detail');
Route::get('/book-tour/{slug}', [BookingController::class, 'tourContactForm'])->name('tour.contact');
Route::get('/transport', [PublicController::class, 'transport'])->name('transport.index');
Route::get('/transport/{slug}', [BookingController::class, 'transportForm'])->name('transport.book');
Route::get('/plan-your-trip', [BookingController::class, 'tourForm'])->name('tour.book');
Route::get('/contact', [PublicController::class, 'contact'])->name('contact');
Route::post('/contact', [PublicController::class, 'sendContact'])->middleware('throttle:public-forms');
Route::get('/photo-credits', [PublicController::class, 'credits'])->name('credits');
Route::get('/sitemap.xml', [PublicController::class, 'sitemap']);
Route::post('/language/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['en', 'sw'], true), 404);
    session(['locale' => $locale]);

    return back();
})->name('language');
Route::post('/estimate/{kind}', [BookingController::class, 'estimate'])->middleware('throttle:estimates')->name('estimate');
Route::middleware('throttle:public-forms')->group(function () {
    Route::post('/book-tour/{slug}', [BookingController::class, 'submitTourContact'])->name('tour.contact.submit');
    Route::post('/booking/tour/review', [BookingController::class, 'reviewTour'])->name('tour.review');
    Route::post('/booking/transport/review', [BookingController::class, 'reviewTransport'])->name('transport.review');
    Route::post('/booking/{kind}/submit', [BookingController::class, 'submit'])->name('bookings.submit');
});
Route::get('/booking/received/{booking}', [BookingController::class, 'received'])->middleware('signed')->name('bookings.received');
Route::get('/booking/review/{draftId}', [BookingController::class, 'review'])->name('bookings.review');
Route::get('/booking/review/{draftId}/edit', [BookingController::class, 'edit'])->name('bookings.edit');
Route::get('/documents/{invoice}/{token}', [DocumentController::class, 'show'])->middleware('throttle:documents')->name('documents.show');
Route::get('/documents/{invoice}/{token}/pdf', [DocumentController::class, 'pdf'])->middleware('throttle:documents')->name('documents.pdf');
Route::get('/documents/{invoice}/{token}/receipt/{payment}', [DocumentController::class, 'receipt'])->middleware('throttle:documents')->name('documents.receipt');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:staff-login');
    Route::get('/forgot-password', [AuthController::class, 'forgotForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'forgot'])->middleware('throttle:staff-login')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:staff-login')->name('password.update');
});
Route::middleware(['auth', 'staff'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [Admin\BookingController::class, 'dashboard'])->name('dashboard');
        Route::get('/bookings', [Admin\BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{booking}', [Admin\BookingController::class, 'show'])->name('bookings.show');
        Route::patch('/bookings/{booking}', [Admin\BookingController::class, 'update'])->name('bookings.update');
        Route::post('/bookings/{booking}/hotels', [Admin\AccommodationController::class, 'store'])->name('hotels.store');
        Route::post('/bookings/{booking}/hotels/{proposal}/select', [Admin\AccommodationController::class, 'select'])->name('hotels.select');
        Route::patch('/bookings/{booking}/accommodation', [Admin\AccommodationController::class, 'status'])->name('hotels.status');
        Route::post('/bookings/{booking}/invoices', [Admin\InvoiceController::class, 'create'])->name('invoices.create');
        Route::get('/invoices/{invoice}', [Admin\InvoiceController::class, 'edit'])->name('invoices.edit');
        Route::patch('/invoices/{invoice}', [Admin\InvoiceController::class, 'update'])->name('invoices.update');
        Route::post('/invoices/{invoice}/issue', [Admin\InvoiceController::class, 'issue'])->name('invoices.issue');
        Route::post('/invoices/{invoice}/send', [Admin\InvoiceController::class, 'send'])->name('invoices.send');
        Route::post('/invoices/{invoice}/payments', [Admin\InvoiceController::class, 'payment'])->name('invoices.payment');
        Route::post('/invoices/{invoice}/void', [Admin\InvoiceController::class, 'void'])->name('invoices.void');
        Route::get('/invoices/{invoice}/pdf', [Admin\InvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::get('/catalog/{type}', [Admin\CatalogController::class, 'index'])->name('catalog.index');
        Route::get('/catalog/{type}/create', [Admin\CatalogController::class, 'form'])->name('catalog.create');
        Route::get('/catalog/{type}/{id}/edit', [Admin\CatalogController::class, 'form'])->name('catalog.edit');
        Route::post('/catalog/{type}', [Admin\CatalogController::class, 'save'])->name('catalog.store');
        Route::patch('/catalog/{type}/{id}', [Admin\CatalogController::class, 'save'])->name('catalog.update');
        Route::delete('/catalog/{type}/{id}', [Admin\CatalogController::class, 'destroy'])->name('catalog.destroy');
        Route::get('/routes', [Admin\CatalogController::class, 'routesIndex'])->name('routes.index');
        Route::get('/routes/create', [Admin\CatalogController::class, 'routeForm'])->name('routes.create');
        Route::get('/routes/{route}/edit', [Admin\CatalogController::class, 'routeForm'])->name('routes.edit');
        Route::post('/routes', [Admin\CatalogController::class, 'saveRoute'])->name('routes.store');
        Route::patch('/routes/{route}', [Admin\CatalogController::class, 'saveRoute'])->name('routes.update');
        Route::delete('/routes/{route}', [Admin\CatalogController::class, 'destroyRoute'])->name('routes.destroy');
        Route::get('/settings', [Admin\OperationsController::class, 'settings'])->name('settings');
        Route::post('/settings', [Admin\OperationsController::class, 'saveSettings']);
        Route::get('/media', [Admin\OperationsController::class, 'media'])->name('media');
        Route::post('/media', [Admin\OperationsController::class, 'upload']);
        Route::patch('/media/{asset}', [Admin\OperationsController::class, 'updateMedia'])->name('media.update');
        Route::post('/media/credits', [Admin\OperationsController::class, 'saveCreditPhotos'])->name('media.credits');
        Route::get('/staff', [Admin\OperationsController::class, 'staff'])->name('staff');
        Route::post('/staff', [Admin\OperationsController::class, 'saveStaff'])->name('staff.create');
        Route::patch('/staff/{user}', [Admin\OperationsController::class, 'saveStaff'])->name('staff.update');
        Route::get('/deliveries', [Admin\OperationsController::class, 'deliveries'])->name('deliveries');
        Route::post('/deliveries/{message}/retry',[Admin\OperationsController::class, 'retry'])->name('deliveries.retry');
    });
});
Route::get('/{slug}',[PublicController::class, 'page'])->where('slug','about|privacy|terms')->name('pages.show');

Route::get('/_debug/php-upload', function () {
    return response()->json([
        'php_version' => PHP_VERSION,
        'loaded_ini' => php_ini_loaded_file(),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'memory_limit' => ini_get('memory_limit'),
        'file_uploads' => ini_get('file_uploads'),
    ]);
});