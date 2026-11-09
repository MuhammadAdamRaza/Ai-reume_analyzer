<?php

use App\Http\Controllers\ResumeAnalysisController;
use App\Http\Controllers\ResumeBuilderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ResumeAnalysisController::class, 'index'])->name('home');
Route::get('/resume-builder', [ResumeBuilderController::class, 'index'])->name('resume-builder.index');
Route::get('/resume-builder/{template}', [ResumeBuilderController::class, 'edit'])
    ->whereIn('template', ['classic', 'modern', 'compact', 'minimal'])
    ->name('resume-builder.edit');
Route::post('/resume-builder/ai-assist', [ResumeBuilderController::class, 'assist'])
    ->middleware('throttle:5,1')
    ->name('resume-builder.assist');
Route::post('/resume-builder/download', [ResumeBuilderController::class, 'download'])
    ->middleware('throttle:20,1')
    ->name('resume-builder.download');
Route::post('/analyze', [ResumeAnalysisController::class, 'analyze'])
    ->middleware('throttle:10,1')
    ->name('analyses.analyze');
Route::get('/analyses/history', [ResumeAnalysisController::class, 'history'])->name('analyses.history');
Route::get('/analyses/{analysis}/status', [ResumeAnalysisController::class, 'status'])->name('analyses.status');
Route::get('/analyses/{analysis}', [ResumeAnalysisController::class, 'show'])->name('analyses.show');
Route::delete('/analyses/{analysis}', [ResumeAnalysisController::class, 'destroy'])->name('analyses.destroy');
Route::get('/analyses/{analysis}/export', [ResumeAnalysisController::class, 'export'])->name('analyses.export');
Route::get('/analyses/{analysis}/export-json', [ResumeAnalysisController::class, 'exportJson'])->name('analyses.export-json');
