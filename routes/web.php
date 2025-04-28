<?php
Route::get('/test-log', function () {
    \Log::error('Test log entry.');
    return 'Log test completed.';
});

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\CustomerController;
use App\Http\Controllers\API\BusinessController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\Admin\AdminCheckInController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\API\RewardController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminBusinessController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\API\InviteController;
use Illuminate\Support\Facades\Log;
use App\Events\MessageSent;
// use Illuminate\Support\Facades\Route;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;


// Route::view('/reverb-test', 'reverb-test');
Route::get('/reverb-test', function () {
    return view('reverb-test');
});

Route::get('/test-broadcast', function () {
    $fakeMessage = (object)[
        'id' => rand(1, 999),
        'message_text' => 'Test message from Laravel',
        'sender_id' => 2,
        'receiver_id' => 3,
        'created_at' => now()
    ];
    Log::info('Broadcasting MessageSent event', array($fakeMessage));
    broadcast(new MessageSent($fakeMessage));
    Log::info('Broadcast queued.');
    return 'Broadcast sent!';
});

// Show Login Form
Route::get('/login', [CustomerController::class, 'showLoginForm'])->name('customer.login');

// Handle Login Form Submission
Route::post('/login', [CustomerController::class, 'handleLogin'])->name('login.post');

Route::get('/reset-password', function () {
    return view('auth.reset-password');
})->name('password.reset');

Route::post('/reset-password', [ResetPasswordController::class, 'reset']);
// Accept Invite
Route::get('/accept-invite/{token}', [InviteController::class, 'acceptInvite']);
// Check Invite Status
Route::post('/check-invite', [InviteController::class, 'checkInvite']);

// Dashboard (Protected Route)
Route::middleware(['auth'])->group(function () {
    Route::get('/customer/dashboard', [CustomerController::class, 'dashboard'])->name('customer.dashboard');
    Route::post('/logout', [CustomerController::class, 'logout'])->name('customer.logout');
    Route::post('/logout', [CustomerController::class, 'logout'])->name('logout');
});

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/business/dashboard', [BusinessController::class, 'dashboard'])->name('business.dashboard');
});

Route::get('/register', [CustomerController::class, 'showRegistrationForm'])->name('customer.register'); // GET request for the registration form
Route::post('/register', [CustomerController::class, 'register'])->name('customer.register.submit'); // POST request for form submission

Route::get('/business/register', [BusinessController::class, 'showRegistrationForm'])->name('business.register');
Route::post('/business/register', [BusinessController::class, 'register'])->name('business.register.submit');

// Group routes with middleware for admin authentication
// Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
//     Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
// });

// Route::middleware(['auth', 'role:admin'])->group(function () {
//     Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard')->name('admin.dashboard');
//     // Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
//     // Route::get('/admin/users/{id}/edit', [AdminController::class, 'edit'])->name('admin.edit');
//     // Route::put('/admin/users/{id}', [AdminController::class, 'update'])->name('admin.update');
//     // Route::delete('/admin/users/{id}', [AdminController::class, 'destroy'])->name('admin.destroy');
// });

Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});


// Route::middleware(['auth', RoleMiddleware::class . ':admin'])->group(function () {
//     Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
// });

// Route::middleware(['auth', App\Http\Middleware\RoleMiddleware::class . ':admin'])
//     ->group(function () {
//         Route::get('/admin/users/{id}/edit', [AdminController::class, 'edit'])->name('admin.edit');
//         Route::put('/admin/users/{id}', [AdminController::class, 'update'])->name('admin.update');
//     });

Route::prefix('admin')->middleware(['auth', RoleMiddleware::class . ':admin'])->group(function () {
    // Admin Profile Route
    Route::get('/profile', [AdminController::class, 'edit'])->name('admin.profile.edit');
    Route::post('/profile', [AdminController::class, 'update'])->name('admin.profile.update');

    // Chnage password Route
    Route::put('/password/update', [AdminController::class, 'updatePassword'])->name('admin.password.update');


    // Dashboard
    Route::get('dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    Route::get('/manual-checkin', [AdminCheckInController::class, 'showManualCheckInForm'])->name('admin.manual-checkin');
    Route::post('/manual-checkin', [AdminCheckInController::class, 'manualCheckIn'])->name('admin.manual-checkin.process');

    // Customers
    Route::resource('customers', AdminCustomerController::class);
    // Businesses
    Route::resource('businesses', AdminBusinessController::class);
    // Categories
    Route::resource('categories', CategoryController::class);
});

// Customer and Business QR Codes
Route::get('/customer/qr-code', [CustomerController::class, 'generateEncryptedQrCode'])->middleware('auth');
Route::get('/business/qr-code', [BusinessController::class, 'generateEncryptedQrCode'])->middleware('auth');

// Rewards QR on customer dashboard
Route::get('/customer/reward-qr', [CustomerDashboardController::class, 'redemptionQrCode'])->middleware('auth');


Route::prefix('admin')->group(function () {
    Route::get('/manual-checkin', [AdminCheckInController::class, 'showManualCheckInForm'])->name('admin.manual-checkin');
    Route::post('/manual-checkin', [AdminCheckInController::class, 'manualCheckIn'])->name('admin.manual-checkin.process');
    Route::get('/get-businesses', [AdminCheckInController::class, 'getBusinesses'])->name('admin.get-businesses');
});

// Grant reward points to businesses manually or automatically
Route::post('/admin/business/{businessId}/grant-checkin-points', [BusinessController::class, 'grantCheckinPoints'])
    ->middleware('auth')
    ->name('admin.business.grantCheckinPoints');

Route::post('/admin/businesses/add-points', [AdminController::class, 'grantPointsToAllActiveBusinesses'])
    ->middleware('auth')
    ->name('admin.addPointsToAll');

Route::get('/admin/businesspoints', [AdminController::class, 'businesspoints'])->name('admin.businesspoints');

Route::get('/business/rewards/create', [BusinessController::class, 'createRewardForm'])->name('business.createReward');
Route::post('/business/rewards/create', [BusinessController::class, 'storeReward'])->name('business.storeReward');
// for business to update check-in points
Route::middleware(['auth'])->group(function () {
    Route::get('/business/manage-checkin-points', [BusinessController::class, 'manageCheckinPoints'])->name('business.manageCheckinPoints');
    Route::post('/business/manage-checkin-points', [BusinessController::class, 'updateCheckinPoints'])->name('business.updateCheckinPoints');
});

// this is for reward redemption but not working yet. 
Route::get('/customer/rewards/{reward_id}/qr', [RewardController::class, 'generateRedemptionQrCode'])->name('customer.rewards.qr');

// customer search page
Route::get('/customer/search', [CustomerController::class, 'searchBusinesses'])->name('customer.search');

// business profile page
Route::get('/customer/business/{business_id}', [CustomerController::class, 'viewBusinessProfile'])->name('customer.business.profile');
