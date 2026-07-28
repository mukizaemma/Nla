<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration decision</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #2c3540; max-width: 600px; margin: 0 auto; padding: 20px; }
        h1 { color: #1A2E4E; font-size: 1.4rem; margin-bottom: 16px; }
        .badge { display: inline-block; padding: 6px 12px; border-radius: 999px; font-size: 0.8rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
        .badge--confirmed { background: #F8C818; color: #1A2E4E; }
        .badge--rejected { background: #1A2E4E; color: #fff; }
        .box { background: #F5F2E8; padding: 14px 16px; border-radius: 8px; border-left: 4px solid #1A2E4E; margin: 16px 0; }
        .footer { margin-top: 24px; font-size: 0.85rem; color: #5c6570; }
    </style>
</head>
<body>
    @php
        $confirmed = $registration->status === \App\Models\StudentRegistration::STATUS_CONFIRMED;
    @endphp
    <h1>Registration {{ $confirmed ? 'confirmed' : 'update' }}</h1>
    <p>Dear {{ $registration->primaryContactName() ?: 'Parent/Guardian' }},</p>
    <p>
        Regarding the registration of <strong>{{ $registration->student_full_name }}</strong>
        @if($registration->academic_level)
            ({{ $registration->academic_level }})
        @endif
        at {{ $schoolName }}:
    </p>
    <p>
        <span class="badge {{ $confirmed ? 'badge--confirmed' : 'badge--rejected' }}">
            {{ $confirmed ? 'Confirmed' : 'Not approved' }}
        </span>
    </p>
    @if($registration->admin_response_message)
        <div class="box">
            {!! nl2br(e($registration->admin_response_message)) !!}
        </div>
    @endif
    <div class="footer">
        <p>Best regards,<br>{{ $schoolName }}</p>
    </div>
</body>
</html>
