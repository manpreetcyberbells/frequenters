<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOfferRequest;
use App\Models\Offer;
use App\Services\FCMService;
use App\Services\NearbyCustomerService;
use Illuminate\Support\Facades\DB;

class AdminOfferController extends Controller
{
    public function __construct(
        private readonly FCMService $fcmService,
        private readonly NearbyCustomerService $nearbyCustomerService,
    ) {}

    public function index()
    {
        return view('admin.businesses.offer.index', [
            'offers' => Offer::with('business')->orderByDesc('id')->get(),
        ]);
    }

    public function create() { return view('admin.businesses.offer.create'); }

    public function store(StoreOfferRequest $request)
    {
        try {
            $businessId = $request->business_id;
            $bannerPath = $request->hasFile('banner') ? url('storage/' . $request->file('banner')->store('offers/banners', 'public')) : null;
            Offer::create(array_merge($request->validated(), ['banner_image' => $bannerPath]));

            $address = DB::table('business_addresses')->where('business_id', $businessId)->first();
            if ($address?->latitude && $address?->longitude) {
                $nearbyIds = $this->nearbyCustomerService->getNearbyCustomerIds($address->latitude, $address->longitude, 10);
                if (!empty($nearbyIds)) {
                    $tokens = DB::table('customer_profiles')->whereIn('customer_id', $nearbyIds)->whereNotNull('fcm_token')->pluck('fcm_token', 'customer_id');
                    $title = 'New Offer Near You!';
                    $body  = 'Offer available for you';
                    $image = url('storage/notifications/notification.png');
                    foreach ($tokens as $customerId => $token) {
                        try {
                            $this->fcmService->sendToCustomerApp($token, $title, $body, $image);
                        } catch (\Throwable $e) {
                            \Log::error("Offer FCM failed for customer {$customerId}: " . $e->getMessage());
                        }
                        customerNotification($customerId, $body, 'push', null, true);
                    }
                }
            }

            return redirect()->route('admin.businesses.offer.index')->with('success', 'Offer created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?? 'Error creating offer');
        }
    }
}
