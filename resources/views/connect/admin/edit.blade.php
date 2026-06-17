@extends('admin.layouts.master')
@section('title', 'Connect Settings')

@php
    $isNew = ! $business->exists;
    $tpl = $templates->keyBy('template_type');
@endphp

@section('content')
<div style="max-width:760px;margin:24px auto;font-family:system-ui,sans-serif;">
    <h1>{{ $isNew ? 'New' : 'Edit' }} Connect Settings</h1>

    @if (session('status'))
        <p style="background:#e7f7ed;padding:10px 14px;border-radius:8px;">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <ul style="background:#fdecea;padding:10px 24px;border-radius:8px;">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    @endif

    <form method="POST" action="{{ $isNew ? route('connect.admin.store') : route('connect.admin.update', $business) }}">
        @csrf
        @unless($isNew) @method('PUT') @endunless

        @php
            $field = function ($label, $name, $value, $type = 'text') {
                return "<label style='display:block;margin:12px 0 4px;font-weight:600;'>$label</label>
                        <input type='$type' name='$name' value='" . e($value) . "' style='width:100%;padding:8px;border:1px solid #ccc;border-radius:6px;'>";
            };
        @endphp

        {!! $field('Frequenters Business ID', 'business_id', old('business_id', $business->business_id), 'number') !!}
        {!! $field('Existing public phone', 'existing_public_phone', old('existing_public_phone', $business->existing_public_phone)) !!}
        {!! $field('Assigned Twilio number (E.164)', 'assigned_twilio_phone', old('assigned_twilio_phone', $business->assigned_twilio_phone)) !!}
        {!! $field('Owner cell phone', 'owner_cell_phone', old('owner_cell_phone', $business->owner_cell_phone)) !!}
        {!! $field('Owner email', 'owner_email', old('owner_email', $business->owner_email), 'email') !!}
        {!! $field('Timezone', 'timezone', old('timezone', $business->timezone ?? 'America/New_York')) !!}
        {!! $field('Duplicate text window (hours)', 'duplicate_text_window_hours', old('duplicate_text_window_hours', $business->duplicate_text_window_hours ?? 24), 'number') !!}
        {!! $field('Active conversation window (minutes)', 'active_conversation_window_minutes', old('active_conversation_window_minutes', $business->active_conversation_window_minutes ?? 30), 'number') !!}

        <div style="margin:16px 0;">
            @foreach ([
                'connect_enabled' => 'Connect enabled',
                'auto_text_enabled' => 'Auto-text enabled',
                'owner_sms_relay_enabled' => 'Owner SMS relay enabled',
                'require_code_when_multiple_active' => 'Require lead code when multiple active (V1: always on)',
                'send_owner_notification_on_customer_reply' => 'Notify owner on customer reply',
                'pause_mode_enabled' => 'Pause mode',
            ] as $name => $label)
                <label style="display:block;margin:6px 0;">
                    <input type="hidden" name="{{ $name }}" value="0">
                    <input type="checkbox" name="{{ $name }}" value="1"
                        {{ old($name, $business->{$name} ?? ($name !== 'pause_mode_enabled')) ? 'checked' : '' }}>
                    {{ $label }}
                </label>
            @endforeach
        </div>

        <label style="display:block;margin:12px 0 4px;font-weight:600;">Status</label>
        <select name="status" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:6px;">
            <option value="active" {{ old('status', $business->status ?? 'active') === 'active' ? 'selected' : '' }}>active</option>
            <option value="inactive" {{ old('status', $business->status) === 'inactive' ? 'selected' : '' }}>inactive</option>
        </select>

        @unless($isNew)
            <h2 style="margin-top:28px;">Message Templates</h2>
            @foreach (\App\Services\Connect\ConnectTemplateService::DEFAULTS as $type => $default)
                <label style="display:block;margin:12px 0 4px;font-weight:600;">{{ $type }}</label>
                <textarea name="templates[{{ $type }}]" rows="3"
                    style="width:100%;padding:8px;border:1px solid #ccc;border-radius:6px;font-family:inherit;">{{ old("templates.$type", optional($tpl->get($type))->body ?? $default) }}</textarea>
            @endforeach
        @endunless

        <button type="submit" style="margin-top:20px;padding:10px 18px;background:#111;color:#fff;border:0;border-radius:8px;cursor:pointer;">Save</button>
    </form>

    @unless($isNew)
        <hr style="margin:28px 0;">
        <h2>Test SMS</h2>
        <form method="POST" action="{{ route('connect.admin.test-sms', $business) }}">
            @csrf
            <input type="text" name="to" placeholder="+17325551111" style="width:48%;padding:8px;border:1px solid #ccc;border-radius:6px;">
            <input type="text" name="body" placeholder="Test message" style="width:48%;padding:8px;border:1px solid #ccc;border-radius:6px;">
            <button type="submit" style="margin-top:10px;padding:8px 14px;border:1px solid #111;border-radius:8px;background:#fff;cursor:pointer;">Send test</button>
        </form>

        <form method="POST" action="{{ route('connect.admin.toggle-pause', $business) }}" style="margin-top:14px;">
            @csrf
            <button type="submit" style="padding:8px 14px;border:1px solid #b00;color:#b00;border-radius:8px;background:#fff;cursor:pointer;">
                {{ $business->pause_mode_enabled ? 'Resume service' : 'Pause service' }}
            </button>
        </form>
    @endunless
</div>
@endsection
