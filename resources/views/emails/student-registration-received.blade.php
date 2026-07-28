<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration received</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #2c3540; max-width: 600px; margin: 0 auto; padding: 20px; }
        h1 { color: #1A2E4E; font-size: 1.4rem; margin-bottom: 16px; }
        .box { background: #F5F2E8; padding: 14px 16px; border-radius: 8px; border-left: 4px solid #F8C818; margin: 16px 0; }
        .footer { margin-top: 24px; font-size: 0.85rem; color: #5c6570; }
    </style>
</head>
<body>
    <h1>We received your registration</h1>
    <p>Dear {{ $registration->primaryContactName() ?: 'Parent/Guardian' }},</p>
    <p>
        Thank you for registering <strong>{{ $registration->student_full_name }}</strong>
        @if($registration->academic_level)
            for <strong>{{ $registration->academic_level }}</strong>
        @endif
        at {{ $schoolName }}.
    </p>
    <div class="box">
        <p style="margin:0;">
            Your application has been submitted successfully. Our admissions team will review it shortly.
            <strong>A confirmation (or further instructions) will be shared with you soon</strong> using your preferred contact method.
        </p>
    </div>
    <p><strong>Reference summary</strong></p>
    <ul>
        <li>Student: {{ $registration->student_full_name }}</li>
        <li>Level: {{ $registration->academic_level ?: '—' }}</li>
        <li>Preferred follow-up: {{ ucfirst($registration->submission_channel ?? 'email') }}</li>
    </ul>
    <div class="footer">
        <p>Best regards,<br>{{ $schoolName }}</p>
    </div>
</body>
</html>
