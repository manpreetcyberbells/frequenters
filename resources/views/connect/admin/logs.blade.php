@extends('admin.layouts.master')
@section('title', 'Connect Logs')

@section('content')
<div style="max-width:1040px;margin:24px auto;font-family:system-ui,sans-serif;">
    <h1>Logs — Business #{{ $business->business_id }}</h1>
    <a href="{{ route('connect.admin.edit', $business) }}">&larr; Back to settings</a>

    <h2 style="margin-top:24px;">Calls</h2>
    <table style="width:100%;border-collapse:collapse;font-size:14px;">
        <thead><tr style="text-align:left;border-bottom:2px solid #eee;">
            <th style="padding:6px;">When</th><th style="padding:6px;">From</th><th style="padding:6px;">Auto-text</th><th style="padding:6px;">Skip reason</th><th style="padding:6px;">Status</th>
        </tr></thead>
        <tbody>
        @forelse ($calls as $c)
            <tr style="border-bottom:1px solid #f3f3f3;">
                <td style="padding:6px;">{{ $c->created_at }}</td>
                <td style="padding:6px;">{{ $c->from_number }}</td>
                <td style="padding:6px;">{{ $c->auto_text_sent ? '✅' : '—' }}</td>
                <td style="padding:6px;">{{ $c->auto_text_skipped_reason }}</td>
                <td style="padding:6px;">{{ $c->call_status }}</td>
            </tr>
        @empty
            <tr><td colspan="5" style="padding:12px;color:#888;">No calls.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2 style="margin-top:24px;">Messages</h2>
    <table style="width:100%;border-collapse:collapse;font-size:14px;">
        <thead><tr style="text-align:left;border-bottom:2px solid #eee;">
            <th style="padding:6px;">When</th><th style="padding:6px;">Dir</th><th style="padding:6px;">Sender</th><th style="padding:6px;">From → To</th><th style="padding:6px;">Body</th><th style="padding:6px;">Status</th>
        </tr></thead>
        <tbody>
        @forelse ($messages as $m)
            <tr style="border-bottom:1px solid #f3f3f3;">
                <td style="padding:6px;white-space:nowrap;">{{ $m->created_at }}</td>
                <td style="padding:6px;">{{ $m->direction }}</td>
                <td style="padding:6px;">{{ $m->sender_type }}</td>
                <td style="padding:6px;white-space:nowrap;">{{ $m->from_number }} → {{ $m->to_number }}</td>
                <td style="padding:6px;">{{ \Illuminate\Support\Str::limit($m->body, 80) }}</td>
                <td style="padding:6px;">{{ $m->status }}{{ $m->error_message ? ' ('.\Illuminate\Support\Str::limit($m->error_message, 40).')' : '' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="padding:12px;color:#888;">No messages.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
