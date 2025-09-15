<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

use App\Http\Controllers\Admin\GiftcardAdminController;
use App\Http\Controllers\Admin\UserAdminController;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('giftcards', [GiftcardAdminController::class, 'index'])->name('giftcards.index');
    Route::get('giftcards/{giftcard}', [GiftcardAdminController::class, 'show'])->name('giftcards.show');

    // User management (admin only)
    Route::get('users', [UserAdminController::class, 'index'])->name('users.index');
    Route::post('users', [UserAdminController::class, 'store'])->name('users.store');
    Route::delete('users/{user}', [UserAdminController::class, 'destroy'])->name('users.destroy');
});

use App\Http\Controllers\DashboardController;

Route::middleware(['auth','verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});


require __DIR__.'/auth.php';
