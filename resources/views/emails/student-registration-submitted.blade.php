<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New student registration</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #2c3540; max-width: 640px; margin: 0 auto; padding: 20px; }
        h1 { color: #1A2E4E; font-size: 1.35rem; margin-bottom: 12px; }
        .meta { background: #F5F2E8; padding: 12px 14px; border-radius: 8px; border-left: 4px solid #F8C818; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px 0; border-bottom: 1px solid #eee; vertical-align: top; }
        th { width: 38%; color: #5c6570; font-weight: 600; }
    </style>
</head>
<body>
    <h1>New student registration</h1>
    <div class="meta">
        <strong>Preferred follow-up:</strong> {{ ucfirst($registration->submission_channel ?? 'email') }}
        &nbsp;·&nbsp;
        <strong>Status:</strong> {{ ucfirst($registration->status ?? 'pending') }}
    </div>
    <table>
        <tr><th>Student</th><td>{{ $registration->student_full_name }}</td></tr>
        <tr><th>Level</th><td>{{ $registration->academic_level ?: '—' }}</td></tr>
        @if($registration->date_of_birth)
            <tr><th>Date of birth</th><td>{{ $registration->date_of_birth->format('j F Y') }}</td></tr>
        @endif
        <tr><th>Primary contact</th><td>{{ ucfirst($registration->primary_contact ?? '—') }}</td></tr>
        @if($registration->mother_full_name)
            <tr><th>Mother</th><td>{{ $registration->mother_full_name }} — {{ $registration->mother_phone }} — {{ $registration->mother_email }}</td></tr>
        @endif
        @if($registration->father_full_name)
            <tr><th>Father</th><td>{{ $registration->father_full_name }} — {{ $registration->father_phone }} — {{ $registration->father_email }}</td></tr>
        @endif
        @if($registration->guardian_full_name)
            <tr><th>Guardian</th><td>{{ $registration->guardian_full_name }} — {{ $registration->guardian_phone }} — {{ $registration->guardian_email }}</td></tr>
        @endif
        @if($registration->previous_school_name)
            <tr><th>Previous school</th><td>{{ $registration->previous_school_name }}</td></tr>
        @endif
    </table>
    <p style="margin-top:18px;color:#5c6570;font-size:0.9rem;">Open the admin Registrations page to confirm or reject this application.</p>
</body>
</html>
