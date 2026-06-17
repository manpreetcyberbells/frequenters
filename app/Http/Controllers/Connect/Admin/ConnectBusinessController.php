<?php

namespace App\Http\Controllers\Connect\Admin;

use App\Http\Controllers\Controller;
use App\Models\Connect\ConnectBusinessSetting;
use App\Models\Connect\ConnectCallLog;
use App\Models\Connect\ConnectMessageTemplate;
use App\Models\Connect\ConnectSmsMessage;
use App\Services\Connect\ConnectCommunicationService;
use App\Services\Connect\ConnectTemplateService;
use App\Support\Connect\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConnectBusinessController extends Controller
{
    public function __construct(
        protected ConnectCommunicationService $comms,
    ) {}

    /** GET /admin/connect/businesses */
    public function index()
    {
        $businesses = ConnectBusinessSetting::orderByDesc('id')->paginate(25);

        return view('connect.admin.index', compact('businesses'));
    }

    /** GET /admin/connect/businesses/create */
    public function create()
    {
        return view('connect.admin.edit', [
            'business'  => new ConnectBusinessSetting(),
            'templates' => collect(),
        ]);
    }

    /** POST /admin/connect/businesses */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $business = ConnectBusinessSetting::create($data);
        $this->seedDefaultTemplates($business);

        return redirect()
            ->route('connect.admin.edit', $business)
            ->with('status', 'Connect settings created.');
    }

    /** GET /admin/connect/businesses/{business}/edit */
    public function edit(ConnectBusinessSetting $business)
    {
        $templates = ConnectMessageTemplate::where('business_id', $business->business_id)->get();

        return view('connect.admin.edit', compact('business', 'templates'));
    }

    /** PUT /admin/connect/businesses/{business} */
    public function update(Request $request, ConnectBusinessSetting $business)
    {
        $business->update($this->validateData($request, $business->id));

        // Persist any edited templates.
        foreach ((array) $request->input('templates', []) as $type => $body) {
            if ($body === null) {
                continue;
            }
            ConnectMessageTemplate::updateOrCreate(
                ['business_id' => $business->business_id, 'template_type' => $type],
                ['body' => $body, 'active' => true]
            );
        }

        return redirect()
            ->route('connect.admin.edit', $business)
            ->with('status', 'Saved.');
    }

    /** GET /admin/connect/businesses/{business}/logs */
    public function logs(ConnectBusinessSetting $business)
    {
        $calls    = ConnectCallLog::where('business_id', $business->business_id)->latest()->limit(100)->get();
        $messages = ConnectSmsMessage::where('business_id', $business->business_id)->latest()->limit(200)->get();

        return view('connect.admin.logs', compact('business', 'calls', 'messages'));
    }

    /** POST /admin/connect/businesses/{business}/test-sms */
    public function testSms(Request $request, ConnectBusinessSetting $business)
    {
        $request->validate(['to' => 'required|string', 'body' => 'required|string|max:1000']);

        $this->comms->sendSms(
            to: $request->input('to'),
            from: $business->assigned_twilio_phone ?: config('connect.twilio.default_from_number'),
            body: $request->input('body'),
            businessId: $business->business_id,
            senderType: 'system'
        );

        return back()->with('status', 'Test SMS queued.');
    }

    /** POST /admin/connect/businesses/{business}/toggle-pause */
    public function togglePause(ConnectBusinessSetting $business)
    {
        $business->update(['pause_mode_enabled' => ! $business->pause_mode_enabled]);

        return back()->with('status', $business->pause_mode_enabled ? 'Paused.' : 'Resumed.');
    }

    // -----------------------------------------------------------------

    protected function validateData(Request $request, ?int $ignoreId = null): array
    {
        // Normalise phone inputs to E.164 BEFORE validation so the unique rule
        // compares against the stored (E.164) values rather than raw formatting
        // (e.g. "(732) 555-0000" and "+17325550000" must collide).
        foreach (['existing_public_phone', 'assigned_twilio_phone', 'owner_cell_phone'] as $field) {
            if (filled($request->input($field))) {
                $request->merge([$field => PhoneNumber::toE164($request->input($field))]);
            }
        }

        $data = $request->validate([
            'business_id'                                => 'required|integer',
            'existing_public_phone'                      => 'nullable|string|max:32',
            'assigned_twilio_phone'                      => [
                'nullable', 'string', 'max:32',
                Rule::unique('connect_business_settings', 'assigned_twilio_phone')->ignore($ignoreId),
            ],
            'owner_cell_phone'                           => 'nullable|string|max:32',
            'owner_email'                                => 'nullable|email|max:255',
            'timezone'                                   => 'nullable|string|max:64',
            'connect_enabled'                            => 'sometimes|boolean',
            'auto_text_enabled'                          => 'sometimes|boolean',
            'owner_sms_relay_enabled'                    => 'sometimes|boolean',
            'duplicate_text_window_hours'                => 'nullable|integer|min:0|max:168',
            'active_conversation_window_minutes'         => 'nullable|integer|min:1|max:1440',
            'require_code_when_multiple_active'          => 'sometimes|boolean',
            'send_owner_notification_on_customer_reply'  => 'sometimes|boolean',
            'pause_mode_enabled'                         => 'sometimes|boolean',
            'status'                                     => 'nullable|in:active,inactive',
        ], [
            'assigned_twilio_phone.unique' => 'That Twilio number is already assigned to another business.',
        ]);

        // Safety net: phones were already normalised above; re-running E.164
        // is idempotent and guards against any field that bypassed the merge.
        foreach (['existing_public_phone', 'assigned_twilio_phone', 'owner_cell_phone'] as $field) {
            if (! empty($data[$field])) {
                $data[$field] = PhoneNumber::toE164($data[$field]);
            }
        }

        // Booleans default to false when checkboxes are absent.
        foreach ([
            'connect_enabled', 'auto_text_enabled', 'owner_sms_relay_enabled',
            'require_code_when_multiple_active', 'send_owner_notification_on_customer_reply',
            'pause_mode_enabled',
        ] as $bool) {
            $data[$bool] = $request->boolean($bool);
        }

        return $data;
    }

    protected function seedDefaultTemplates(ConnectBusinessSetting $business): void
    {
        foreach (ConnectTemplateService::DEFAULTS as $type => $body) {
            ConnectMessageTemplate::firstOrCreate(
                ['business_id' => $business->business_id, 'template_type' => $type],
                ['body' => $body, 'active' => true]
            );
        }
    }
}
