<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AdminAuditLogController;
use App\Http\Controllers\AdminPageController;
use App\Http\Controllers\AdminPaymentController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminSectionController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AdminSubscriberController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\DecisionController;
use App\Http\Controllers\EditorAnnouncementController;
use App\Http\Controllers\EditorDashboardController;
use App\Http\Controllers\EditorIssueController;
use App\Http\Controllers\EditorSubmissionController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OaiController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewerInviteController;
use App\Http\Controllers\ReviewerManagementController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| JOSHNU — Routes (Lampiran C)
|--------------------------------------------------------------------------
*/

// --- Publik: interoperabilitas ---
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Toggle bahasa UI (ID/EN)
Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['id', 'en'], true)) {
        session(['locale' => $locale]);
    }

    return redirect()->back(fallback: '/');
})->name('locale.set');
Route::get('/oai', [OaiController::class, 'handle'])->name('oai');

// --- Portal publik ---
Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/current-issue', [IssueController::class, 'current'])->name('issues.current');
Route::get('/archive', [IssueController::class, 'index'])->name('issues.index');
Route::get('/archive/{issue}', [IssueController::class, 'show'])->name('issues.show');
Route::get('/issues/{issue}/issue.pdf', [IssueController::class, 'pdf'])->name('issues.pdf');
Route::get('/articles', [SearchController::class, 'index'])->name('articles.index');
Route::get('/article/{slug}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/article/{slug}/download/{galley}', [ArticleController::class, 'download'])->name('articles.download');
Route::get('/article/{slug}/citations', [ArticleController::class, 'citations'])->name('articles.citations');
Route::get('/article/{slug}/related', [ArticleController::class, 'related'])->name('articles.related');
Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');
Route::get('/page/{name}', [PageController::class, 'show'])->where('name', '[a-z0-9-]+')->name('pages.show');
Route::post('/subscribe', [SubscribeController::class, 'store'])->name('subscribe.store');
Route::get('/subscribe/confirm', [SubscribeController::class, 'confirm'])->name('subscribe.confirm');
Route::get('/unsubscribe', [SubscribeController::class, 'unsubscribe'])->name('subscribe.unsubscribe');

// --- Undangan reviewer berbasis token (tanpa login) ---
Route::get('/review/invite/{token}', [ReviewerInviteController::class, 'show'])->name('reviewer.invite');
Route::post('/review/invite/{token}/accept', [ReviewerInviteController::class, 'accept'])->name('reviewer.invite.accept');
Route::post('/review/invite/{token}/decline', [ReviewerInviteController::class, 'decline'])->name('reviewer.invite.decline');

// --- Authenticated ---
Route::middleware('auth')->group(function () {
    // Profil (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::match(['put', 'patch'], '/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notifikasi in-app
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    // Account / profil user (PRD §12.2)
    Route::get('/account', fn () => redirect()->route('profile.edit'));

    // Tujuan redirect default setelah login: berdasarkan role
    Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
        $u = $request->user();
        if ($u->hasRole('admin') || $u->isEditorial() || $u->hasRole('eic')) {
            return redirect()->route('editor.dashboard');
        }
        if ($u->isProduction()) {
            return redirect()->route('production.index');
        }
        return redirect()->route('account.submissions.index');
    })->middleware('auth')->name('dashboard');
});

// --- Author: submission (semua user terautentikasi boleh submit) ---
Route::middleware('auth')->prefix('account/submissions')->name('account.submissions.')->group(function () {
    Route::get('/', [SubmissionController::class, 'index'])->name('index');
    Route::get('/new', [SubmissionController::class, 'create'])->name('create');
    Route::post('/', [SubmissionController::class, 'store'])->name('store');
    Route::get('/{submission}', [SubmissionController::class, 'show'])->name('show');
    Route::get('/{submission}/edit', [SubmissionController::class, 'edit'])->name('edit');
    Route::put('/{submission}', [SubmissionController::class, 'update'])->name('update');
    Route::post('/{submission}/submit', [SubmissionController::class, 'submit'])->name('submit');
    Route::post('/{submission}/withdraw', [SubmissionController::class, 'withdraw'])->name('withdraw');
    Route::get('/{submission}/files', [SubmissionController::class, 'files'])->name('files');
    Route::post('/{submission}/files', [SubmissionController::class, 'storeFile'])->name('files.store');
    Route::delete('/{submission}/files/{file}', [SubmissionController::class, 'destroyFile'])->name('files.destroy');
    Route::get('/{submission}/files/{file}/download', [SubmissionController::class, 'downloadFile'])->name('files.download');
    Route::get('/{submission}/revisions', [SubmissionController::class, 'createRevision'])->name('revisions.create');
    Route::post('/{submission}/revisions', [SubmissionController::class, 'storeRevision'])->name('revisions.store');
    Route::get('/{submission}/payment', [PaymentController::class, 'show'])->name('payment');
    Route::post('/{submission}/payment/proof', [PaymentController::class, 'storeProof'])->name('payment.proof');
    Route::get('/{submission}/payment/proof/download', [PaymentController::class, 'downloadProof'])->name('payment.proof.download');
});

// --- Reviewer portal ---
Route::middleware(['auth', 'role:reviewer,eic,section_editor,admin,production'])->prefix('review')->name('review.')->group(function () {
    Route::get('/', [ReviewController::class, 'index'])->name('index');
    Route::get('/{submission}', [ReviewController::class, 'show'])->name('show');
    Route::post('/{submission}', [ReviewController::class, 'update'])->name('update');
    Route::post('/{submission}/files', [ReviewController::class, 'storeFile'])->name('files.store');
    Route::get('/{submission}/files/{file}', [ReviewController::class, 'downloadFile'])->name('files.download');
});

// --- Editorial workspace ---
Route::middleware(['auth', 'role:admin,eic,section_editor'])->prefix('editor')->name('editor.')->group(function () {
    Route::get('/', [EditorDashboardController::class, 'index'])->name('dashboard');
    Route::get('/submissions', [EditorSubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/submissions/{submission}', [EditorSubmissionController::class, 'show'])->name('submissions.show');
    Route::post('/submissions/{submission}/stage', [EditorSubmissionController::class, 'transitionStage'])->name('submissions.stage');
    Route::get('/submissions/{submission}/audit', [EditorSubmissionController::class, 'audit'])->name('submissions.audit');
    Route::get('/submissions/{submission}/files/{file}/download', [EditorSubmissionController::class, 'downloadFile'])->name('submissions.file.download');
    Route::post('/submissions/{submission}/decision', [DecisionController::class, 'store'])->name('submissions.decision');
    Route::post('/submissions/{submission}/messages', [MessageController::class, 'store'])->name('submissions.messages');
    Route::get('/reviewers', [ReviewerManagementController::class, 'index'])->name('reviewers.index');
    Route::post('/submissions/{submission}/reviewers/assign', [ReviewerManagementController::class, 'assign'])->name('submissions.reviewers.assign');
    Route::post('/submissions/{submission}/reviewers/invite', [ReviewerManagementController::class, 'invite'])->name('submissions.reviewers.invite');
    Route::delete('/submissions/{submission}/reviewers/{review}', [ReviewerManagementController::class, 'cancel'])->name('submissions.reviewers.cancel');
    Route::post('/submissions/{submission}/schedule', [ProductionController::class, 'schedule'])->name('submissions.schedule');
    Route::get('/issues', [EditorIssueController::class, 'index'])->name('issues.index');
    Route::get('/issues/create', [EditorIssueController::class, 'create'])->name('issues.create');
    Route::post('/issues', [EditorIssueController::class, 'store'])->name('issues.store');
    Route::get('/issues/{issue}/edit', [EditorIssueController::class, 'edit'])->name('issues.edit');
    Route::put('/issues/{issue}', [EditorIssueController::class, 'update'])->name('issues.update');
    Route::post('/issues/{issue}/publish', [EditorIssueController::class, 'publish'])->middleware('role:admin,eic')->name('issues.publish');
    Route::post('/issues/{issue}/unpublish', [EditorIssueController::class, 'unpublish'])->middleware('role:admin,eic')->name('issues.unpublish');
    Route::delete('/issues/{issue}', [EditorIssueController::class, 'destroy'])->middleware('role:admin,eic')->name('issues.destroy');
    Route::get('/announcements', [EditorAnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements', [EditorAnnouncementController::class, 'store'])->name('announcements.store');
    Route::put('/announcements/{announcement}', [EditorAnnouncementController::class, 'update'])->name('announcements.update');
    Route::post('/announcements/{announcement}/restore', [EditorAnnouncementController::class, 'restore'])->withTrashed()->name('announcements.restore');
    Route::delete('/announcements/{announcement}', [EditorAnnouncementController::class, 'destroy'])->withTrashed()->name('announcements.destroy');
});

// --- Production & publishing ---
Route::middleware(['auth', 'role:admin,eic,production'])->prefix('production')->name('production.')->group(function () {
    Route::get('/', [ProductionController::class, 'index'])->name('index');
    Route::put('/publications/{publication}/metadata', [ProductionController::class, 'updateMetadata'])->name('publications.metadata');
    Route::post('/submissions/{submission}/proof', [ProductionController::class, 'uploadProof'])->name('proof');
    Route::post('/publications/{publication}/pdf', [ProductionController::class, 'generatePdf'])->name('publications.pdf');
    Route::get('/publications/{publication}/pdf/status', [ProductionController::class, 'pdfStatus'])->name('publications.pdf.status');
    Route::post('/publications/{publication}/jats', [ProductionController::class, 'generateJats'])->name('publications.jats');
    Route::post('/publications/{publication}/doi', [ProductionController::class, 'depositDoi'])->name('publications.doi');
    Route::get('/publications/{publication}/doi/status', [ProductionController::class, 'doiStatus'])->name('publications.doi.status');
    Route::post('/publications/{publication}/publish', [ProductionController::class, 'publish'])->name('publications.publish');
    Route::post('/publications/{publication}/unpublish', [ProductionController::class, 'unpublish'])->name('publications.unpublish');
    Route::post('/publications/{publication}/correct', [ProductionController::class, 'correct'])->name('publications.correct');
    Route::post('/publications/{publication}/retract', [ProductionController::class, 'retract'])->name('publications.retract');
});

// --- Admin ---
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/settings', [AdminSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
    Route::put('/settings/theme', [AdminSettingsController::class, 'updateTheme'])->name('settings.theme');
    Route::post('/settings/board', [AdminSettingsController::class, 'storeBoardMember'])->name('settings.board.store');
    Route::put('/settings/board/{index}', [AdminSettingsController::class, 'updateBoardMember'])->name('settings.board.update');
    Route::delete('/settings/board/{index}', [AdminSettingsController::class, 'destroyBoardMember'])->name('settings.board.destroy');
    Route::get('/sections', [AdminSectionController::class, 'index'])->name('sections.index');
    Route::post('/sections', [AdminSectionController::class, 'store'])->name('sections.store');
    Route::put('/sections/{section}', [AdminSectionController::class, 'update'])->name('sections.update');
    Route::delete('/sections/{section}', [AdminSectionController::class, 'destroy'])->name('sections.destroy');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::put('/users/{user}/roles', [AdminUserController::class, 'updateRoles'])->name('users.roles');
    Route::post('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('users.toggle');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}/proof', [AdminPaymentController::class, 'downloadProof'])->name('payments.proof.download');
    Route::post('/payments/{payment}/verify', [AdminPaymentController::class, 'verify'])->name('payments.verify');
    Route::post('/payments/{payment}/reject', [AdminPaymentController::class, 'reject'])->name('payments.reject');
    Route::get('/subscribers', [AdminSubscriberController::class, 'index'])->name('subscribers.index');
    Route::get('/pages', [AdminPageController::class, 'index'])->name('pages.index');
    Route::post('/pages', [AdminPageController::class, 'store'])->name('pages.store');
    Route::put('/pages/{page}', [AdminPageController::class, 'update'])->name('pages.update');
    Route::post('/pages/{page}/toggle-active', [AdminPageController::class, 'toggleActive'])->name('pages.toggle');
    Route::delete('/pages/{page}', [AdminPageController::class, 'destroy'])->name('pages.destroy');
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/usage.csv', [AdminReportController::class, 'usageCsv'])->name('reports.usage.csv');
    Route::get('/reports/editorial.csv', [AdminReportController::class, 'editorialCsv'])->name('reports.editorial.csv');
    Route::get('/reports/doaj', [AdminReportController::class, 'doajExport'])->name('reports.doaj');
    Route::get('/reports/finance.csv', [AdminReportController::class, 'financeCsv'])->name('reports.finance.csv');
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');
});

require __DIR__.'/auth.php';
