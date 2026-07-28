<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Student Registrations</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { color: #64748b; margin-bottom: 14px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #e2e8f0; font-size: 10px; text-transform: uppercase; }
        .empty { text-align: center; color: #64748b; padding: 24px; }
    </style>
</head>
<body>
    <h1>{{ $schoolName }} — Student Registrations</h1>
    <div class="meta">
        @if($from || $to)
            Date range:
            {{ $from ? \Carbon\Carbon::parse($from)->format('M j, Y') : '…' }}
            –
            {{ $to ? \Carbon\Carbon::parse($to)->format('M j, Y') : '…' }}
            ·
        @endif
        Generated {{ $generatedAt->format('M j, Y g:i A') }}
        · {{ $registrations->count() }} record(s)
    </div>

    @if($registrations->isEmpty())
        <p class="empty">No registrations in this date range.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Submitted</th>
                    <th>Student</th>
                    <th>Level</th>
                    <th>Status</th>
                    <th>Channel</th>
                    <th>Contact</th>
                    <th>Phone / Email</th>
                </tr>
            </thead>
            <tbody>
                @foreach($registrations as $reg)
                    <tr>
                        <td>{{ $reg->created_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $reg->student_full_name }}</td>
                        <td>{{ $reg->academic_level ?: '—' }}</td>
                        <td>{{ ucfirst($reg->status ?? 'pending') }}</td>
                        <td>{{ ucfirst($reg->submission_channel ?? '—') }}</td>
                        <td>
                            {{ ucfirst($reg->primary_contact ?? '—') }}
                            @if($reg->primaryContactName())
                                <br>{{ $reg->primaryContactName() }}
                            @endif
                        </td>
                        <td>
                            {{ $reg->primaryContactPhone() ?: '—' }}
                            @if($reg->primaryContactEmail())
                                <br>{{ $reg->primaryContactEmail() }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
