@extends('admin.layouts.master')
@section('title', 'Business')

@section('content')
<div style="max-width:680px;margin:24px auto;font-family:system-ui,sans-serif;">
    <h1>Frequenters Connect</h1>

    @if (! $business)
        <p style="background:#fff7e6;padding:14px;border-radius:8px;">
            Frequenters Connect isn’t set up for your business yet. Please contact support to get started.
        </p>
    @else
        <p style="font-size:18px;">
            Status:
            <strong style="color:{{ $business->connect_enabled && ! $business->pause_mode_enabled ? '#0a7d33' : '#b00' }};">
                {{ $business->pause_mode_enabled ? 'Paused' : ($business->connect_enabled ? 'Active' : 'Inactive') }}
            </strong>
        </p>

        <div style="background:#f7f7f8;border-radius:12px;padding:18px;margin-top:12px;">
            <p><strong>Your forwarding number:</strong><br>{{ $business->assigned_twilio_phone ?? 'Not assigned yet' }}</p>
            <p><strong>Owner cell on file:</strong><br>{{ $business->owner_cell_phone ?? '—' }}</p>
        </div>

        <h2 style="margin-top:24px;">How to set up call forwarding</h2>
        <ol style="line-height:1.7;">
            <li>Keep advertising your existing public phone number.</li>
            <li>Turn on <em>conditional</em> call forwarding so unanswered, busy, or unavailable calls forward to <strong>{{ $business->assigned_twilio_phone ?? 'your assigned number' }}</strong>.</li>
            <li>On most US carriers: dial <code>*61*{{ $business->assigned_twilio_phone ?? 'NUMBER' }}#</code> (no answer) and <code>*67*{{ $business->assigned_twilio_phone ?? 'NUMBER' }}#</code> (busy). Codes vary by carrier — check yours.</li>
            <li>Test by calling your own number and not answering. You should receive a text.</li>
        </ol>

        <h2 style="margin-top:24px;">Sample missed-call message</h2>
        <blockquote style="background:#eef4ff;border-left:4px solid #3b6cff;padding:12px 16px;border-radius:6px;">
            {{ $sampleTemplate ?? 'Hi, this is your business. Sorry we missed your call. How can we help?' }}
        </blockquote>

        <p style="margin-top:24px;color:#666;">
            To reply to customers, just text back from your cell phone. Commands: <code>PAUSE</code>, <code>RESUME</code>, <code>STATUS</code>, <code>HELP</code>.
        </p>
        <p style="color:#666;">Need help? Contact Frequenters support.</p>
    @endif
</div>
@endsection
