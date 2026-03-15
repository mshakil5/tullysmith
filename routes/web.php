<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// cache clear
Route::get('/clear', function() {
  Auth::logout();
  session()->flush();
  Artisan::call('cache:clear');
  Artisan::call('config:clear');
  Artisan::call('config:cache');
  Artisan::call('view:clear');
  return "Cleared!";
});

 Route::fallback(function () {
    return redirect('/');
});

require __DIR__.'/admin.php';

Auth::routes();

Route::get('/', [HomeController::class, 'dashboard'])->name('home');

Route::view('/privacy-policy', 'admin.pages.privacy');

Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

Route::group(['prefix' =>'client/', 'middleware' => ['auth', 'is_client', 'verified']], function(){
  
    Route::get('/dashboard', [HomeController::class, 'userHome'])->name('client.dashboard');

});