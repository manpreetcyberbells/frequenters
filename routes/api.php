<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\CustomerController;
use App\Http\Controllers\API\BusinessController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\RewardController;
use App\Http\Controllers\API\CustomerProfileController;
use App\Http\Controllers\API\WishlistController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\SearchHistoryController;
// use App\Http\Controllers\CityController;
use App\Http\Controllers\CheckInController;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\CategoryController;


// Public Routes
Route::middleware(['api', EnsureFrontendRequestsAreStateful::class])->group(function () {
    Route::post('/register/customer', [AuthController::class, 'registerCustomer']);
    Route::post('/register/business', [AuthController::class, 'registerBusiness']);
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');

    // Get all cities
    Route::get('/cities', [CityController::class, 'getCityDetails']);

    // Get all Zip_code
    Route::get('/zipcodes', [CityController::class, 'getZipCodes']);

    // Get unique states
    Route::get('/states', [CityController::class, 'getStates']);

    // Get unique countries
    Route::get('/countries', [CityController::class, 'getCountries']);

    // Get cities by state or country (Optional for dependent dropdowns)
    Route::get('/cities/filter', [CityController::class, 'getCitiesByStateOrCountry']);

    // API's for getting all categories
    Route::get('/categories-list', [CategoryController::class, 'getCategories']);

    // API's for getting Parent categories and Child categories
    Route::get('/categories/parents', [CategoryController::class, 'getParentCategories']);
    Route::get('/categories/{parentId}/children', [CategoryController::class, 'getChildCategories']);

    // Get Cities by ZipCode
    Route::get('/zip/{zip_code}/cities', [CityController::class, 'getCitiesByZip']);
    // Get country, state, latitude, and longitude by city_id
    Route::get('/city/{city_id}/details', [CityController::class, 'getStateAndCoordinates']);
});
// Route::post('/reset-password', [ResetPasswordController::class, 'reset']);

// Route::middleware(['auth:api'])->group(function () {
//     Route::put('/customer/profile', [CustomerProfileController::class, 'updateProfile']);
// });

// Customer API's
Route::middleware(['auth:api'])->prefix('customer')->group(function () {
    //API User profile Update
    Route::post('/profile', [CustomerProfileController::class, 'updateProfile']);
    //API Change User Status
    Route::put('/update-status/{user_id}', [CustomerProfileController::class, 'updateStatus']);
    //API Change Password
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    //API Update Push Notifications
    Route::put('/push-notifications', [CustomerProfileController::class, 'pushNotificationUpdate']);
    //API Update Location
    Route::put('/location', [CustomerProfileController::class, 'updateLocation']);
    //API Update Language
    Route::put('/language-update', [CustomerProfileController::class, 'updateCustomerLanguage']);
    // Create address
    Route::post('/create-address', [CustomerProfileController::class, 'createCustomerAddress']);
    // Edit Address
    Route::put('/update-address/{id}', [CustomerProfileController::class, 'updateCustomerAddress']);
    // Delete Address
    Route::delete('/delete-address/{id}', [CustomerProfileController::class, 'deleteCustomerAddress']);
    // Lists address
    Route::get('/address', [CustomerProfileController::class, 'getCustomerAddress']);
    // Home API (Include Frequently Visited // Highest Reward Points // Near You)
    Route::get('/home-list', [CustomerController::class, 'getHomeBusinesses']);
    // Get wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    // Add to wishlist
    Route::post('/wishlist', [WishlistController::class, 'store']);
    // Remove from wishlist
    Route::delete('/wishlist/{business_id}', [WishlistController::class, 'destroy']);
    // Submit or update review
    Route::post('/post-review', [ReviewController::class, 'store']);
    // Get reviews of a business
    Route::get('/reviews/business/{business_id}', [ReviewController::class, 'getBusinessReviews']);
    // Delete review
    Route::delete('/review/{id}', [ReviewController::class, 'destroy']);
    // Get customer's reviews
    Route::get('/reviews', [ReviewController::class, 'getCustomerReviews']);
    // Post Seach History
    Route::post('/search', [SearchHistoryController::class, 'store']);
    // Get Seach History
    Route::get('/search-history', [SearchHistoryController::class, 'getUserSearchHistory']);
    // Delete Seach History
    Route::delete('/search-history/{id}', [SearchHistoryController::class, 'deleteSingleSearchHistory']);
    // Search API
    Route::get('/search', [SearchHistoryController::class, 'searchLocal']);
});


// Protected Routes (Require Authentication)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/customer/dashboard', [CustomerController::class, 'dashboard']);
    Route::get('/protected', function (Request $request) {
        return response()->json(['message' => 'You are authenticated!', 'user' => $request->user()]);
    });
});

// Resource Routes (Public/General APIs)
Route::apiResource('customers', CustomerController::class);
Route::apiResource('users', UserController::class);
Route::apiResource('businesses', BusinessController::class);
Route::apiResource('transactions', TransactionController::class);

// Additional Utility Routes
// Route::get('/cities-by-zip/{zip}', [CityController::class, 'getCitiesByZip']);

// QR code generation
Route::post('/checkin/customer', [CheckInController::class, 'customerCheckIn']);
Route::post('/checkin/business', [CheckInController::class, 'businessCheckIn']);
Route::post('/checkin/text-code', [CheckInController::class, 'checkInWithTextCode']);

// Generate Customer QR Code
Route::get('/customer/qr-code', [CustomerController::class, 'generateEncryptedQrCode'])
    ->middleware('auth'); // Only logged-in customers can access

// Generate Business QR Code
Route::get('/business/qr-code', [BusinessController::class, 'generateBusinessQrCode'])
    ->middleware('auth'); // Only logged-in business admins can access

// Group QR code generation endpoints under /api/generate-qr
Route::prefix('generate-qr')->middleware('auth:sanctum')->group(function () {
    Route::get('/customer', [CustomerController::class, 'generateEncryptedQrCodeApi'])
        ->name('api.qr.customer');
    Route::get('/business', [BusinessController::class, 'generateBusinessQrCodeApi'])
        ->name('api.qr.business');
});

// Grant reward points to businesses manually or automatically
Route::post('/business/{businessId}/grant-checkin-points', [BusinessController::class, 'grantCheckinPoints'])
    ->middleware('auth:sanctum')
    ->name('api.business.grantCheckinPoints');

// for businesses to add rewards
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/business/rewards', [BusinessController::class, 'storeRewardApi'])->name('api.business.storeReward');
});

// for businesses to view reward point summary
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/business/points-summary', [BusinessController::class, 'pointsSummaryApi'])->name('api.business.pointsSummary');
});

// for business to modify points per check-in
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/business/checkin-points', [BusinessController::class, 'updateCheckinPointsApi'])->name('api.business.updateCheckinPoints');
    Route::get('/business/checkin-points', [BusinessController::class, 'getCheckinPointsApi'])->name('api.business.getCheckinPoints');
});

// for check-in
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/business/checkin', [CheckinController::class, 'checkIn'])->name('api.business.checkin');
});

// Redeem reward
Route::middleware('auth:sanctum')->get('/rewards/{reward_id}/redeem-qr', [RewardController::class, 'generateRedemptionQrCode'])->name('api.rewards.generateQr');

// Generate QR code for redeeming a reward
Route::middleware('auth:sanctum')->post('/rewards/generate-qr', [RewardController::class, 'generateRedemptionQrCodeApi'])->name('api.rewards.generateQr');

// Get information for Customer Dashboard on customer_app
Route::middleware(['auth:sanctum'])->get('/customer/api-dashboard', [CustomerController::class, 'apiDashboard']);
