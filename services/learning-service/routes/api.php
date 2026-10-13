<?php

use App\Http\Controllers\Api\KnowledgeReadController;
use App\Http\Controllers\Api\CourseReadController;
use App\Http\Controllers\Api\LearningAdminController;
use App\Http\Controllers\Api\QuestionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', fn () => response()->json(['service' => 'learning-service', 'status' => 'ok']));
    Route::middleware('learning.gateway')->group(function (): void {
        Route::get('/knowledge/topics', [KnowledgeReadController::class, 'topics']);
        Route::get('/knowledge-items', [KnowledgeReadController::class, 'documents']);
        Route::get('/knowledge-items/{documentId}', [KnowledgeReadController::class, 'document'])->whereUuid('documentId');
        Route::get('/knowledge-items/{documentId}/download', [KnowledgeReadController::class, 'download'])->whereUuid('documentId');
        Route::get('/questions', [QuestionController::class, 'index']);
        Route::post('/questions', [QuestionController::class, 'store']);
        Route::get('/questions/{questionId}', [QuestionController::class, 'show'])->whereUuid('questionId');
        Route::post('/questions/{questionId}/answers', [QuestionController::class, 'storeAnswer'])->whereUuid('questionId');
        Route::patch('/answers/{answerId}', [QuestionController::class, 'updateAnswer'])->whereUuid('answerId');
        Route::delete('/answers/{answerId}', [QuestionController::class, 'destroyAnswer'])->whereUuid('answerId');
        Route::put('/questions/{questionId}/votes', [QuestionController::class, 'voteQuestion'])->whereUuid('questionId');
        Route::put('/answers/{answerId}/votes', [QuestionController::class, 'voteAnswer'])->whereUuid('answerId');
        Route::get('/courses', [CourseReadController::class, 'index']);
        Route::get('/admin/taxonomy', [LearningAdminController::class, 'taxonomy']);
        Route::post('/admin/topics', [LearningAdminController::class, 'storeTopic']);
        Route::patch('/admin/topics/{topicId}', [LearningAdminController::class, 'updateTopic'])->whereUuid('topicId');
        Route::post('/admin/categories', [LearningAdminController::class, 'storeCategory']);
        Route::patch('/admin/categories/{categoryId}', [LearningAdminController::class, 'updateCategory'])->whereUuid('categoryId');
        Route::get('/admin/questions', [LearningAdminController::class, 'questions']);
        Route::post('/admin/questions/{questionId}/moderation', [LearningAdminController::class, 'moderateQuestion'])->whereUuid('questionId');
        Route::get('/admin/documents', [LearningAdminController::class, 'documents']);
        Route::post('/admin/documents', [LearningAdminController::class, 'storeDocument']);
        Route::patch('/admin/documents/{documentId}', [LearningAdminController::class, 'updateDocument'])->whereUuid('documentId');
        Route::post('/admin/documents/{documentId}/files', [LearningAdminController::class, 'uploadDocumentFile'])->whereUuid('documentId');
        Route::post('/admin/documents/{documentId}/review', [LearningAdminController::class, 'reviewDocument'])->whereUuid('documentId');
        Route::get('/admin/documents/{documentId}/access-rules', [LearningAdminController::class, 'accessRules'])->whereUuid('documentId');
        Route::put('/admin/documents/{documentId}/access-rules', [LearningAdminController::class, 'syncAccessRules'])->whereUuid('documentId');
        Route::post('/admin/documents/{documentId}/lifecycle', [LearningAdminController::class, 'transitionDocument'])->whereUuid('documentId');
        Route::get('/admin/courses', [LearningAdminController::class, 'courses']);
        Route::post('/admin/courses', [LearningAdminController::class, 'storeCourse']);
        Route::post('/admin/courses/{courseId}/lifecycle', [LearningAdminController::class, 'transitionCourse'])->whereUuid('courseId');
    });
});
