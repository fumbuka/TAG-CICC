<?php

use App\Livewire\Departments\Index as DepartmentsIndex;
use App\Livewire\Members\Index as MembersIndex;
use App\Livewire\Zones\Index as ZonesIndex;
use Illuminate\Http\Request;
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

Route::middleware(['auth'])->group(function () {
    Route::get('members', MembersIndex::class)->name('members.index');
    Route::get('departments', DepartmentsIndex::class)->name('departments.index');
    Route::get('zones', ZonesIndex::class)->name('zones.index');
});

require __DIR__.'/auth.php';
