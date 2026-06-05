<?php

use App\Http\Controllers\BulkImportTemplateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportUploadReportController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\SmsDeliveryCallbackController;
use App\Livewire\Calendar\Index as CalendarIndex;
use App\Livewire\Departments\Index as DepartmentsIndex;
use App\Livewire\Finance\Index as FinanceIndex;
use App\Livewire\Leadership\Index as LeadershipIndex;
use App\Livewire\Members\Index as MembersIndex;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Services\Index as ServicesIndex;
use App\Livewire\Sms\Index as SmsIndex;
use App\Livewire\Users\Index as UsersIndex;
use App\Livewire\Visitors\Index as VisitorsIndex;
use App\Livewire\Zones\Index as ZonesIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicSiteController::class)->name('home');
Route::view('about', 'public.about')->name('public.about');
Route::view('ministries', 'public.ministries')->name('public.ministries');
Route::get('public-calendar', [PublicSiteController::class, 'calendar'])->name('public.calendar');
Route::get('weekly-leadership', [PublicSiteController::class, 'weeklyLeadership'])->name('public.weekly-leadership');
Route::post('sms/beem/callback', SmsDeliveryCallbackController::class)->name('sms.beem.callback');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::post('language', function (Request $request) {
    $validated = $request->validate([
        'locale' => ['required', 'in:sw,en'],
    ]);

    $request->session()->put('locale', $validated['locale']);

    if ($request->user()) {
        $request->user()->update([
            'preferred_locale' => $validated['locale'],
        ]);
    }

    return back();
})->name('language.update');

Route::post('logout', function (Request $request) {
    Auth::guard('web')->logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('bulk-import-templates/{type}', BulkImportTemplateController::class)
        ->middleware('permission:members.import|departments.manage|zones.manage')
        ->name('bulk-import-templates.download');

    Route::get('import-uploads/{importUpload}/report', ImportUploadReportController::class)
        ->middleware('permission:members.import|departments.manage|zones.manage')
        ->name('import-uploads.report');

    Route::get('members/{section?}', MembersIndex::class)
        ->whereIn('section', ['list', 'create', 'import', 'relationships'])
        ->middleware('permission:members.view|members.list|members.create|members.import|members.relationships')
        ->name('members.index');
    Route::get('visitors', VisitorsIndex::class)
        ->middleware('permission:visitors.manage')
        ->name('visitors.index');
    Route::get('departments/{section?}', DepartmentsIndex::class)
        ->whereIn('section', ['list', 'create', 'import'])
        ->middleware('permission:departments.manage|departments.list|departments.create|departments.import')
        ->name('departments.index');
    Route::get('zones/{section?}', ZonesIndex::class)
        ->whereIn('section', ['list', 'create', 'import'])
        ->middleware('permission:zones.manage|zones.list|zones.create|zones.import')
        ->name('zones.index');
    Route::get('services/{section?}', ServicesIndex::class)
        ->whereIn('section', ['list', 'create'])
        ->middleware('permission:services.manage|services.list|services.record')
        ->name('services.index');
    Route::get('finance/{section?}', FinanceIndex::class)
        ->whereIn('section', ['summary', 'income-categories', 'expense-categories', 'expenses', 'pledges', 'transactions'])
        ->middleware('permission:finance.view|finance.record|finance.summary|finance.income-categories|finance.expense-categories|finance.expenses|finance.pledges|finance.transactions')
        ->name('finance.index');
    Route::get('sms/{section?}', SmsIndex::class)
        ->whereIn('section', ['dashboard', 'buy', 'compose', 'templates', 'scheduled', 'campaigns', 'wallets', 'approvals', 'reports', 'settings'])
        ->middleware('permission:sms.view|sms.buy|sms.compose|sms.templates|sms.scheduled|sms.wallets|sms.approve|sms.reports|sms.settings')
        ->name('sms.index');
    Route::get('calendar/{section?}', CalendarIndex::class)
        ->whereIn('section', ['events', 'create', 'weekly-duties'])
        ->middleware('permission:calendar.manage|calendar.submit|calendar.events|calendar.create|calendar.weekly-duties')
        ->name('calendar.index');
    Route::get('reports', ReportsIndex::class)
        ->middleware('permission:reports.view|reports.submit|reports.approve')
        ->name('reports.index');
    Route::get('leadership/{section?}', LeadershipIndex::class)
        ->whereIn('section', ['titles', 'assign', 'assignments'])
        ->middleware('permission:leadership.manage|leadership.titles|leadership.assign|leadership.assignments')
        ->name('leadership.index');
    Route::get('users/{section?}', UsersIndex::class)
        ->whereIn('section', ['list', 'access', 'role-matrix'])
        ->middleware('permission:users.manage|users.list|users.access|users.role-matrix')
        ->name('users.index');
});

require __DIR__.'/auth.php';
