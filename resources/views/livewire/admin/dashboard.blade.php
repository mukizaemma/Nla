@section('title', 'School Dashboard')

<div>
    <!-- Top KPI Cards -->
    <div class="row g-4">
        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('admin.programs.index') }}" class="text-decoration-none">
                <div class="bg-light rounded d-flex align-items-center justify-content-between p-4 h-100">
                    <i class="fa fa-sitemap fa-3x text-primary"></i>
                    <div class="ms-3 text-end">
                        <p class="mb-1 text-muted text-uppercase small">Programs / Curriculum</p>
                        <h4 class="mb-0 text-dark">{{ $programCount }}</h4>
                        <small class="text-primary">Manage programs <i class="fa fa-arrow-right ms-1"></i></small>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('admin.registrations.index') }}" class="text-decoration-none">
                <div class="bg-light rounded d-flex align-items-center justify-content-between p-4 h-100">
                    <i class="fa fa-user-graduate fa-3x text-primary"></i>
                    <div class="ms-3 text-end">
                        <p class="mb-1 text-muted text-uppercase small">Registrations</p>
                        <h4 class="mb-0 text-dark">{{ $registrationCount }}</h4>
                        <small class="text-primary">View all <i class="fa fa-arrow-right ms-1"></i></small>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('admin.contact-messages.index') }}" class="text-decoration-none">
                <div class="bg-light rounded d-flex align-items-center justify-content-between p-4 h-100">
                    <i class="fa fa-envelope fa-3x text-primary"></i>
                    <div class="ms-3 text-end">
                        <p class="mb-1 text-muted text-uppercase small">Contact Messages</p>
                        <h4 class="mb-0 text-dark">{{ $contactMessageCount }}</h4>
                        <small class="text-primary">View messages <i class="fa fa-arrow-right ms-1"></i></small>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-xl-3">
            <a href="{{ route('admin.feedback.index') }}" class="text-decoration-none">
                <div class="bg-light rounded d-flex align-items-center justify-content-between p-4 h-100">
                    <i class="fa fa-star fa-3x text-primary"></i>
                    <div class="ms-3 text-end">
                        <p class="mb-1 text-muted text-uppercase small">Feedback</p>
                        <h4 class="mb-0 text-dark">{{ $feedbackCount }}</h4>
                        <small class="text-primary">View feedback <i class="fa fa-arrow-right ms-1"></i></small>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Recent registrations -->
    <div class="row g-4 mt-1">
        <div class="col-12">
            <div class="bg-light rounded p-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <h4 class="mb-0">
                        <i class="fa fa-user-graduate me-2 text-primary"></i>Recent Registrations
                    </h4>
                    <a href="{{ route('admin.registrations.index') }}" class="btn btn-sm btn-outline-primary">
                        Manage &amp; export <i class="fa fa-arrow-right ms-1"></i>
                    </a>
                </div>

                @if($recentRegistrations->isEmpty())
                    <p class="text-muted mb-0">No student registrations yet.</p>
                @else
                    <div class="table-responsive bg-white rounded border">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Level</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th style="width: 90px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentRegistrations as $reg)
                                    <tr>
                                        <td><strong>{{ $reg->student_full_name }}</strong></td>
                                        <td>{{ $reg->academic_level ?: '—' }}</td>
                                        <td class="small">
                                            {{ $reg->primaryContactName() ?: '—' }}
                                            @if($reg->primaryContactPhone())
                                                <br>{{ $reg->primaryContactPhone() }}
                                            @endif
                                        </td>
                                        <td>
                                            @php $status = $reg->status ?? 'pending'; @endphp
                                            @if($status === 'confirmed')
                                                <span class="badge bg-primary">Confirmed</span>
                                            @elseif($status === 'rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">{{ $reg->created_at->format('M d, Y H:i') }}</td>
                                        <td>
                                            <a href="{{ route('admin.registrations.index') }}?open={{ $reg->id }}" class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
