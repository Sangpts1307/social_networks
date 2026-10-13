<?php

use App\Http\Controllers\KnowledgeDocumentUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/admin/knowledge-documents/upload', KnowledgeDocumentUploadController::class)
    ->middleware('auth:admin')
    ->name('admin.knowledge-documents.upload');
