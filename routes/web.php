<?php

use App\Http\Controllers\BulkImportTemplateController;
use App\Livewire\Departments\Index as DepartmentsIndex;
use App\Livewire\Finance\Index as FinanceIndex;
use App\Livewire\Leadership\Index as LeadershipIndex;
use App\Livewire\Members\Index as MembersIndex;
use App\Livewire\Services\Index as ServicesIndex;
use App\Livewire\Users\Index as UsersIndex;
use App\Livewire\Zones\Index as ZonesIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
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
        ->name('bulk-import-templates.download');

    Route::get('members', MembersIndex::class)->name('members.index');
    Route::get('departments', DepartmentsIndex::class)->name('departments.index');
    Route::get('zones', ZonesIndex::class)->name('zones.index');
    Route::get('services', ServicesIndex::class)->name('services.index');
    Route::get('finance', FinanceIndex::class)->name('finance.index');
    Route::get('leadership', LeadershipIndex::class)
        ->middleware('permission:leadership.manage')
        ->name('leadership.index');
    Route::get('users', UsersIndex::class)
        ->middleware('permission:users.manage')
        ->name('users.index');
});

require __DIR__.'/auth.php';
