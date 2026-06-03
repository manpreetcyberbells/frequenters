<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\{
    AboutUsController, AuthController, BusinessController, ComplaintController, CollaborationController,
    ContestController, CustomerController, CustomerProfileController, EventController, FaqController, 
    InviteController, MessageController, NotificationController, OfferController, MenuController,
    PrivacyPolicyController, ReviewController, RewardController, SearchHistoryController, ServiceController,
    SuggestionController, TermsAndConditionController, TransactionController, UserController, WishlistController, B2BCollabController
};
// use App\Http\Controllers\CityController;
use App\Http\Controllers\CheckInController;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\CategoryController;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use App\Http\Middleware\SetUserTimezone;

Route::post('/test-broadcast', function (\Illuminate\Http\Request $request) {
    $message = (object)[
        'sender_id' => 12,
        'receiver_id' => 3,
        'text' => $request->text,
        'timestamp' => now(),
    ];
    broadcast(new MessageSent($message));
    return response()->json(['status' => 'Message broadcasted!', 'message' => $message]);
});

Route::post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
})->middleware('auth:api');

// Public Routes
Route::middleware(['api', EnsureFrontendRequestsAreStateful::class])->group(function () {
    Route::post('/register/customer', [AuthController::class, 'registerCustomer']);
    Route::post('/register/business', [AuthController::class, 'registerBusiness']);
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    // Social Users
    Route::post('/social-login-register', [AuthController::class, 'socialLoginRegister']);
    // Get all badges
    Route::get('/badges', [AuthController::class, 'fetchBadges']);
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
    // FAQ
    Route::get('/faqs', [FaqController::class, 'index']);
    // About Us
    Route::get('/about-us', [AboutUsController::class, 'show']);
    // Privacy Policy
    Route::get('/privacy-policy', [PrivacyPolicyController::class, 'show']);
    // Term and Condition
    Route::get('/terms-and-conditions', [TermsAndConditionController::class, 'show']);
});

// Customer API's
Route::middleware(['auth:api', SetUserTimezone::class])->prefix('customer')->group(function () {
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
    //API Allow Personal Info
    Route::put('/allow-personal-info', [CustomerProfileController::class, 'allowPersonalInformation']);
    // Create address
    Route::post('/create-address', [CustomerProfileController::class, 'createCustomerAddress']);
    // Edit Address
    Route::put('/update-address/{id}', [CustomerProfileController::class, 'updateCustomerAddress']);
    // Make Current Address
    Route::put('/make-current-address/{id}', [CustomerProfileController::class, 'makeCurrentAddress']);
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
    // Submit Complaints
    Route::post('/post-complaint', [ComplaintController::class, 'createComplaint']);
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
    // Get Single Business Detail
    Route::get('/business-detail/{id?}', [BusinessController::class, 'getBusinessDetails']);
    // Get Offers
    Route::get('/get-offers', [OfferController::class, 'getOffersDetail']);
    // Get Events
    Route::get('/get-events', [EventController::class, 'getEventsDetail']);
    // Join-Cancelled Event
    Route::post('/join-cancel-event', [EventController::class, 'eventReservationStatus']);
    // Get Rewards
    Route::get('/get-rewards', [RewardController::class, 'getBusinessRewards']);
    // Get Reward Detail
    Route::get('/get-reward-detail/{id}', [RewardController::class, 'fetchSingleReward']);
    // Get Rewards list from BusinessID
    Route::get('/get-reward-by-business/{id}', [RewardController::class, 'getRewardByBusiness']);
    // Contests Listing
    Route::get('/contest-listing', [ContestController::class, 'fetchContests']);
    // Submit Answer
    Route::post('/submit-answer', [ContestController::class, 'submitAnswer']);
    // Create Contest Vote
    Route::post('/contest-vote', [ContestController::class, 'vote']);
    // Get Winners
    Route::get('/contest/{contest_id}/winners', [ContestController::class, 'getWinners']);
    // Customer Message List
    Route::get('/customer-messages/{customer_id}', [MessageController::class, 'getCustomerMessages']);
    // Customer respond to message
    Route::post('/respond-message', [MessageController::class, 'respondToMessage']);
    // Submit Suggestion
    Route::post('/submit-suggestion', [SuggestionController::class, 'store']);
    // Menu Listing
    Route::get('/menu-list', [MenuController::class, 'getMenuList']);
    // Service Listing
    Route::get('/service-list', [ServiceController::class, 'getServiceList']);
});

// Business routes — STAFF + OWNER access
Route::middleware(['auth:api', SetUserTimezone::class, 'business.access'])->prefix('business')->group(function () {
    Route::get('/dashboard', [BusinessController::class, 'dashboardBusinessData']);
    Route::get('/offer-listing', [OfferController::class, 'offerListing']);
});


// Business routes — OWNER ONLY
Route::middleware(['auth:api', SetUserTimezone::class, 'business.access:owner_only'])->prefix('business')->group(function () {
    // Offers / Events / Contests / Complaints / Messages
    Route::post('/offers', [OfferController::class, 'businessCreateOffer']);
    Route::get('/get-recommended-offers', [OfferController::class, 'getRecommendedOffers']);
    Route::post('/events', [EventController::class, 'createBusinessEvent']);
    Route::get('/get-complaints', [ComplaintController::class, 'getComplaintsByBusiness']);
    Route::post('/contests', [ContestController::class, 'create']);
    Route::post('/delete-answer/{id}', [ContestController::class, 'deleteAnswerByBusiness']);
    Route::post('/send-message', [MessageController::class, 'sendBusinessMessage']);

    // Profile / Hours / Owner / Password / Language / Notifications
    Route::post('/update-profile', [BusinessController::class, 'updateBusinessProfile']);
    Route::post('/hours-update', [BusinessController::class, 'updateBusinessHours']);
    Route::post('/change-password', [BusinessController::class, 'changePassword']);
    Route::post('/update-owner', [BusinessController::class, 'updateOwnerDetail']);
    Route::put('/update-language', [BusinessController::class, 'updateBusinessLanguage']);
    Route::put('/push-notifications-update', [BusinessController::class, 'pushNotificationUpdate']);
    
    // Staff management
    Route::post('/staff-create', [BusinessController::class, 'createStaff']);
    Route::get('/staff-listing', [BusinessController::class, 'listStaff']);
    Route::delete('/staff/{id}', [BusinessController::class, 'deactivateStaff']);

    // Collaboration / Service / Menu
    Route::post('/collaboration-post', [CollaborationController::class, 'bTobCollaboration']);
    Route::get('/collaboration/{businessId}', [CollaborationController::class, 'getbTobListing']);
    Route::post('/service-post', [ServiceController::class, 'createServiceMenu']);
    Route::post('/create-menu', [MenuController::class, 'createMenu']);

    // Listings
    Route::get('/event-listing', [EventController::class, 'eventListing']);
    Route::get('/reward-listing', [RewardController::class, 'rewardListing']);
    Route::get('/contest-listing', [ContestController::class, 'contestListing']);

    Route::prefix('b2b')->group(function () {
        // Posts
        Route::post('/posts',             [B2BCollabController::class, 'createPost']);
        Route::get('/posts',              [B2BCollabController::class, 'listNearbyPosts']);
        Route::get('/posts/mine',         [B2BCollabController::class, 'myPosts']);
        Route::get('/posts/{id}',         [B2BCollabController::class, 'showPost']);
        Route::delete('/posts/{id}',      [B2BCollabController::class, 'deletePost']);
        Route::post('/posts/{id}/respond',[B2BCollabController::class, 'respondToPost']);

        // Messaging
        Route::get('/conversations',              [B2BCollabController::class, 'conversationList']);
        Route::get('/conversations/{id}',         [B2BCollabController::class, 'getConversation']);
        Route::post('/conversations/{id}/messages',[B2BCollabController::class, 'sendMessage']);
    });
});

// Protected Routes (Require Authentication)
Route::middleware(['auth:sanctum', SetUserTimezone::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/customer/dashboard', [CustomerController::class, 'dashboard']);
    Route::get('/protected', function (Request $request) { return response()->json(['message' => 'You are authenticated!', 'user' => $request->user()]);});

    // Get information for Customer Dashboard on customer_app
    Route::get('/customer/api-dashboard', [CustomerController::class, 'apiDashboard']);

    // Resource Routes
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('businesses', BusinessController::class);
    Route::apiResource('transactions', TransactionController::class);
});

Route::middleware(['auth', SetUserTimezone::class])->group(function () {
    Route::get('/customer/qr-code', [CustomerController::class, 'generateEncryptedQrCode'])->name('api.customer.qrCode');

    // Business QR staff+owner
    Route::get('/business/qr-code', [BusinessController::class, 'generateEncryptedQrCode'])->middleware('business.access')->name('api.business.qrCode');
});

Route::prefix('generate-qr')->middleware('auth:sanctum')->group(function () {
    Route::get('/customer', [CustomerController::class, 'generateEncryptedQrCodeApi'])->name('api.qr.customer');

    // Business QR API staff+owner
    Route::get('/business', [BusinessController::class, 'generateEncryptedQrCodeApi'])->middleware('business.access')->name('api.qr.business');
});

// Grant reward points to businesses manually or automatically
Route::middleware(['auth:sanctum', SetUserTimezone::class])->group(function () {
    // Customer-side
    Route::post('/checkin/scan-business-qr', [CheckInController::class, 'businessCheckIn']);
    Route::post('/checkin/business',         [CheckInController::class, 'businessCheckIn']); // legacy alias
    Route::get('/notifications',               [NotificationController::class, 'getNotifications'])->name('api.notifications');
    Route::get('/chat/messages/{receiver_id}', [MessageController::class, 'getChatMessages'])->name('api.message');
    Route::get('/customer-business/chat/list', [MessageController::class, 'getChatList']);
    Route::post('/customer/invite',            [InviteController::class, 'invite'])->name('api.invite');

    // Reward redemption QR generation for customer
    Route::get('/rewards/{reward_id}/redeem-qr', [RewardController::class, 'generateRedemptionQrCode'])->name('api.rewards.generateQr');
    Route::post('/checkin/by-uid',   [CheckInController::class, 'checkInWithUID']);

    // STAFF + OWNER access
    Route::middleware('business.access')->group(function () {
        // Check-in scans
        Route::post('/checkin/scan-customer-qr', [CheckInController::class, 'customerCheckIn']);
        Route::post('/checkin/customer',         [CheckInController::class, 'customerCheckIn']); // legacy alias
        Route::post('/business/{businessId}/grant-checkin-points', [BusinessController::class, 'grantCheckinPoints'])->middleware('auth:sanctum')->name('api.business.grantCheckinPoints');

        // Points view (read-only)
        Route::get('/business/points-summary', [BusinessController::class, 'pointsSummaryApi'])->name('api.business.pointsSummary');
        Route::get('/business/checkin-points', [BusinessController::class, 'getCheckinPointsApi'])->name('api.business.getCheckinPoints');
        Route::get('/business/customer-tier-thresholds', [BusinessController::class, 'getCustomerTierThresholdsApi'])->name('api.business.getTierThresholds');
        Route::get('/business/customer-listing', [BusinessController::class, 'customerListing']);
    });

    // OWNER ONLY
    Route::middleware('business.access:owner_only')->group(function () {
        Route::post('/business/post-reward', [RewardController::class, 'storeRewardApi'])->name('api.business.storeReward');
        Route::post('/business/checkin-points',[BusinessController::class, 'updateCheckinPointsApi'])->name('api.business.updateCheckinPoints');
        Route::post('/business/customer-tier-thresholds', [BusinessController::class, 'updateCustomerTierThresholdsApi'])->name('api.business.updateTierThresholds');
        Route::post('/business/checkin',[CheckinController::class, 'checkIn'])->name('api.business.checkin');
        Route::post('/rewards/generate-qr',[RewardController::class, 'generateRedemptionQrCodeApi'])->name('api.rewards.generateQrApi');
        Route::post('/reward/redemptions',[RewardController::class, 'rewardRedemptions']);
        Route::post('/business/chat/send',[MessageController::class, 'sendOneToOneMessage'])->name('api.chat');
        Route::post('/business/create-grand-child-category', [BusinessController::class, 'createGrandChildCategory']);
    });
});