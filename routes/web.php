<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocumentController;

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
    Route::get('/upload', function () {
        return view('upload');
    })->name('upload.page');
    
    Route::post('/upload', [DocumentController::class, 'upload'])->name('document.upload');
    
    Route::get('/plagiarism-check/{id}', [DocumentController::class, 'checkPlagiarism'])->name('plagiarism.check');
    
    Route::get('/upload-reference', function () {
        return view('upload_reference');
    })->name('upload.reference.page');
    
    Route::post('/upload-reference', [DocumentController::class, 'uploadReference'])->name('reference.document.upload');
});



require __DIR__.'/auth.php';

