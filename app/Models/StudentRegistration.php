<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentRegistration extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'student_first_name',
        'student_last_name',
        'academic_level',
        'date_of_birth',
        'primary_contact',
        'submission_channel',
        'status',
        'admin_response_message',
        'responded_at',
        'responded_by',
        'father_full_name',
        'father_email',
        'father_phone',
        'mother_full_name',
        'mother_email',
        'mother_phone',
        'guardian_full_name',
        'guardian_email',
        'guardian_phone',
        'previous_school_name',
        'previous_school_report_path',
        'deleted_by',
        'deletion_reason',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'responded_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function deletedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function getStudentFullNameAttribute(): string
    {
        return trim($this->student_first_name . ' ' . $this->student_last_name);
    }

    public function contactPrefix(): string
    {
        return match ($this->primary_contact) {
            'father' => 'father',
            'mother' => 'mother',
            'guardian' => 'guardian',
            default => 'guardian',
        };
    }

    public function primaryContactName(): ?string
    {
        $prefix = $this->contactPrefix();

        return $this->{$prefix . '_full_name'} ?: null;
    }

    public function primaryContactEmail(): ?string
    {
        $prefix = $this->contactPrefix();
        $email = $this->{$prefix . '_email'} ?: null;

        if ($email) {
            return $email;
        }

        foreach (['mother_email', 'father_email', 'guardian_email'] as $field) {
            if ($this->{$field}) {
                return $this->{$field};
            }
        }

        return null;
    }

    public function primaryContactPhone(): ?string
    {
        $prefix = $this->contactPrefix();
        $phone = $this->{$prefix . '_phone'} ?: null;

        if ($phone) {
            return $phone;
        }

        foreach (['mother_phone', 'father_phone', 'guardian_phone'] as $field) {
            if ($this->{$field}) {
                return $this->{$field};
            }
        }

        return null;
    }

    public function isPending(): bool
    {
        return ($this->status ?? self::STATUS_PENDING) === self::STATUS_PENDING;
    }

    public function prefersWhatsApp(): bool
    {
        return ($this->submission_channel ?? 'email') === 'whatsapp';
    }

    public function prefersEmail(): bool
    {
        return ! $this->prefersWhatsApp();
    }

    /** Digits-only phone for wa.me links. */
    public function whatsappDigits(): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $this->primaryContactPhone());

        return strlen($digits) >= 9 ? $digits : null;
    }

    public function decisionWhatsAppUrl(string $schoolName): ?string
    {
        $digits = $this->whatsappDigits();
        if (! $digits) {
            return null;
        }

        $statusLabel = $this->status === self::STATUS_CONFIRMED ? 'confirmed' : 'not approved';
        $message = "Hello {$this->primaryContactName()},\n\n"
            . "Regarding the registration of {$this->student_full_name} at {$schoolName}:\n\n"
            . "Decision: {$statusLabel}\n\n"
            . trim((string) $this->admin_response_message) . "\n\n"
            . "Thank you.\n{$schoolName}";

        return 'https://wa.me/' . $digits . '?text=' . rawurlencode($message);
    }
}
