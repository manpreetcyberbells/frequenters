@extends('admin.layouts.master')
@section('title', 'Connect')

@section('content')
<div style="max-width:980px;margin:24px auto;font-family:system-ui,sans-serif;">
    <div style="display:flex;justify-content:space-between;align-items:center;">
        <h1>Frequenters Connect — Businesses</h1>
        <a href="{{ route('connect.admin.create') }}" style="padding:8px 14px;background:#111;color:#fff;border-radius:8px;text-decoration:none;">+ New</a>
    </div>

    @if (session('status'))
        <p style="background:#e7f7ed;padding:10px 14px;border-radius:8px;">{{ session('status') }}</p>
    @endif

    <table style="width:100%;border-collapse:collapse;margin-top:16px;">
        <thead>
            <tr style="text-align:left;border-bottom:2px solid #eee;">
                <th style="padding:8px;">Business ID</th>
                <th style="padding:8px;">Twilio #</th>
                <th style="padding:8px;">Owner Cell</th>
                <th style="padding:8px;">Enabled</th>
                <th style="padding:8px;">Paused</th>
                <th style="padding:8px;">Status</th>
                <th style="padding:8px;"></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($businesses as $b)
            <tr style="border-bottom:1px solid #f0f0f0;">
                <td style="padding:8px;">{{ $b->business_id }}</td>
                <td style="padding:8px;">{{ $b->assigned_twilio_phone ?? '—' }}</td>
                <td style="padding:8px;">{{ $b->owner_cell_phone ?? '—' }}</td>
                <td style="padding:8px;">{{ $b->connect_enabled ? 'Yes' : 'No' }}</td>
                <td style="padding:8px;">{{ $b->pause_mode_enabled ? 'Yes' : 'No' }}</td>
                <td style="padding:8px;">{{ $b->status }}</td>
                <td style="padding:8px;">
                    <a href="{{ route('connect.admin.edit', $b) }}">Edit</a> ·
                    <a href="{{ route('connect.admin.logs', $b) }}">Logs</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" style="padding:16px;color:#888;">No businesses configured yet.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div style="margin-top:16px;">{{ $businesses->links() }}</div>
</div>
@endsection
