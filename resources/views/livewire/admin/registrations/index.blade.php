<div>
    <div class="bg-light rounded p-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h4 class="mb-0">
                <i class="fa fa-user-graduate me-2 text-primary"></i>Student Registrations
            </h4>
            <div class="d-flex flex-wrap gap-2">
                <select class="form-select form-select-sm" wire:model.live="statusFilter" style="min-width: 140px;">
                    <option value="all">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="rejected">Rejected</option>
                </select>
                <input
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="Search student, parent, email, phone..."
                    wire:model.live.debounce.300ms="search"
                    style="min-width: 260px;"
                >
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @if($registrations->isEmpty())
                    <div class="p-4 text-center text-muted">
                        @if($search || $statusFilter !== 'all')
                            No registrations match your filters.
                        @else
                            No student registrations yet.
                        @endif
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Level</th>
                                    <th>Contact</th>
                                    <th>Channel</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th style="width: 90px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($registrations as $reg)
                                    <tr>
                                        <td>
                                            <strong>{{ $reg->student_full_name }}</strong>
                                            @if($reg->date_of_birth)
                                                <br><span class="small text-muted">DOB: {{ $reg->date_of_birth->format('M j, Y') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $reg->academic_level ?: '—' }}</td>
                                        <td class="small">
                                            <span class="text-capitalize">{{ $reg->primary_contact ?: '—' }}</span>
                                            @if($reg->primaryContactName())
                                                <br>{{ $reg->primaryContactName() }}
                                            @endif
                                            @if($reg->primaryContactPhone())
                                                <br>{{ $reg->primaryContactPhone() }}
                                            @endif
                                            @if($reg->primaryContactEmail())
                                                <br><a href="mailto:{{ $reg->primaryContactEmail() }}">{{ $reg->primaryContactEmail() }}</a>
                                            @endif
                                        </td>
                                        <td>
                                            @if(($reg->submission_channel ?? '') === 'whatsapp')
                                                <span class="badge bg-success">WhatsApp</span>
                                            @elseif(($reg->submission_channel ?? '') === 'email')
                                                <span class="badge bg-dark">Email</span>
                                            @else
                                                <span class="badge bg-secondary">—</span>
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
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                wire:click="openRegistration({{ $reg->id }})"
                                            >
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-2">
                        {{ $registrations->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($selected)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.45);" wire:keydown.escape.window="closeRegistration">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Registration: {{ $selected->student_full_name }}</h5>
                        <button type="button" class="btn-close" wire:click="closeRegistration"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-2">Student</h6>
                                <p class="mb-1"><strong>Name:</strong> {{ $selected->student_full_name }}</p>
                                <p class="mb-1"><strong>Level:</strong> {{ $selected->academic_level ?: '—' }}</p>
                                @if($selected->date_of_birth)
                                    <p class="mb-1"><strong>Date of birth:</strong> {{ $selected->date_of_birth->format('F j, Y') }}</p>
                                @endif
                                <p class="mb-1"><strong>Primary contact:</strong> {{ ucfirst($selected->primary_contact ?? '—') }}</p>
                                @if($selected->previous_school_name)
                                    <p class="mb-1"><strong>Previous school:</strong> {{ $selected->previous_school_name }}</p>
                                @endif
                                @if($selected->previous_school_report_path)
                                    <p class="mb-0">
                                        <a href="{{ asset($selected->previous_school_report_path) }}" target="_blank" rel="noopener">View school report</a>
                                    </p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary mb-2">Contacts on file</h6>
                                @foreach([
                                    'Mother' => ['name' => $selected->mother_full_name, 'email' => $selected->mother_email, 'phone' => $selected->mother_phone],
                                    'Father' => ['name' => $selected->father_full_name, 'email' => $selected->father_email, 'phone' => $selected->father_phone],
                                    'Guardian' => ['name' => $selected->guardian_full_name, 'email' => $selected->guardian_email, 'phone' => $selected->guardian_phone],
                                ] as $label => $c)
                                    @if($c['name'] || $c['email'] || $c['phone'])
                                        <p class="mb-2 small">
                                            <strong>{{ $label }}:</strong> {{ $c['name'] ?: '—' }}<br>
                                            @if($c['email'])<a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a><br>@endif
                                            @if($c['phone']){{ $c['phone'] }}@endif
                                        </p>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <hr>

                        <div class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Preferred follow-up channel</label>
                                <select class="form-select @error('editChannel') is-invalid @enderror" wire:model="editChannel">
                                    <option value="email">Email</option>
                                    <option value="whatsapp">WhatsApp</option>
                                </select>
                                @error('editChannel')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    Applicant chose <strong class="text-capitalize">{{ $selected->submission_channel ?: '—' }}</strong>.
                                    You can change this before responding.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="updateChannel">
                                    Save channel only
                                </button>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-semibold">Message to parent <span class="text-danger">*</span></label>
                            <textarea
                                class="form-control @error('responseMessage') is-invalid @enderror"
                                rows="4"
                                wire:model="responseMessage"
                                placeholder="Write the confirmation or rejection message the parent will receive..."
                                @disabled(! $selected->isPending())
                            ></textarea>
                            @error('responseMessage')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div class="form-text">
                                @if($editChannel === 'email')
                                    Confirm/Reject will email this message automatically via Resend.
                                @else
                                    Confirm/Reject will open WhatsApp with this message pre-filled for the parent’s number.
                                @endif
                            </div>
                        </div>

                        @if(! $selected->isPending())
                            <div class="alert alert-light border mt-3 mb-0">
                                <strong class="text-capitalize">{{ $selected->status }}</strong>
                                @if($selected->responded_at)
                                    on {{ $selected->responded_at->format('M j, Y g:i A') }}
                                @endif
                                @if($selected->admin_response_message)
                                    <div class="mt-2 small">{{ $selected->admin_response_message }}</div>
                                @endif
                            </div>
                        @endif

                        <p class="small text-muted mt-3 mb-0">Submitted {{ $selected->created_at->format('F j, Y \a\t g:i A') }}</p>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-light" wire:click="closeRegistration">Close</button>
                        @if($selected->isPending())
                            <div class="d-flex gap-2">
                                <button
                                    type="button"
                                    class="btn btn-outline-danger"
                                    wire:click="rejectApplication"
                                    wire:loading.attr="disabled"
                                >
                                    Reject
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    wire:click="confirmApplication"
                                    wire:loading.attr="disabled"
                                >
                                    Confirm
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@script
<script>
    $wire.on('open-url', (payload) => {
        const url = payload?.url || payload?.[0]?.url || payload;
        if (typeof url === 'string' && url.length) {
            window.open(url, '_blank', 'noopener');
        }
    });
</script>
@endscript
