<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    public const ROLES = ['admin', 'eic', 'section_editor', 'production', 'reviewer', 'author'];

    protected $fillable = [
        'name', 'email', 'password', 'orcid', 'institution', 'expertise',
        'address', 'phone', 'website', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function roles(): HasMany
    {
        // pivot-style: tabel user_roles (user_id, role) — satu user bisa multi-role
        return $this->hasMany(Role::class, 'user_id');
    }

    public function hasRole(string ...$roles): bool
    {
        return $this->roles->whereIn('role', $roles)->isNotEmpty();
    }

    public function isAuthor(): bool
    {
        return $this->hasRole('admin', 'eic', 'section_editor', 'production', 'reviewer', 'author')
            || $this->submissions()->exists();
    }

    public function isEditorial(): bool
    {
        return $this->hasRole('admin', 'eic', 'section_editor');
    }

    public function isProduction(): bool
    {
        return $this->hasRole('admin', 'eic', 'production');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'author_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Kolom hitung riwayat proses dalam jurnal (nama kolom => label).
     * Kolom *_count di-load via withCount/addSelect di index agar tidak N+1;
     * bila belum ada, dihitung segar di deleteBlockers().
     */
    public const HISTORY_COUNTS = [
        'submissions_count' => 'Naskah (penulis)',
        'coauthor_count' => 'Keterlibatan penulis',
        'reviews_count' => 'Review dikerjakan',
        'assignments_count' => 'Penugasan reviewer',
        'decisions_count' => 'Keputusan editorial',
        'messages_sent_count' => 'Pesan dikirim',
        'announcements_count' => 'Pengumuman dibuat',
        'file_uploads_count' => 'File diunggah',
        'payments_verified_count' => 'Pembayaran diverifikasi',
        'audit_entries_count' => 'Aktivitas (audit log)',
    ];

    /**
     * Daftar alasan user tidak dapat dihapus (label + jumlah).
     * Kosong = user belum punya riwayat proses dan boleh dihapus.
     */
    public function deleteBlockers(): array
    {
        $attrs = $this->getAttributes();
        $counts = [];
        $missing = false;
        foreach (array_keys(self::HISTORY_COUNTS) as $col) {
            if (array_key_exists($col, $attrs)) {
                $counts[$col] = (int) $attrs[$col];
            } else {
                $missing = true;
                break;
            }
        }
        if ($missing) {
            $counts = $this->freshHistoryCounts();
        }

        return collect(self::HISTORY_COUNTS)
            ->filter(fn ($label, $col) => $counts[$col] > 0)
            ->map(fn ($label, $col) => $label.' ('.$counts[$col].')')
            ->values()
            ->all();
    }

    public function hasJournalHistory(): bool
    {
        return $this->deleteBlockers() !== [];
    }

    /** Hitung segar seluruh riwayat (dipakai saat proses hapus). */
    public function freshHistoryCounts(): array
    {
        $id = $this->id;

        return [
            'submissions_count' => $this->submissions()->count(),
            'coauthor_count' => SubmissionAuthor::where('user_id', $id)->count(),
            'reviews_count' => $this->reviews()->count(),
            'assignments_count' => Assignment::where('user_id', $id)->count(),
            'decisions_count' => Decision::where('editor_id', $id)->count(),
            'messages_sent_count' => Message::where('sender_id', $id)->count(),
            'announcements_count' => Announcement::where('by', $id)->count(),
            'file_uploads_count' => SubmissionFile::where('uploaded_by', $id)->count(),
            'payments_verified_count' => Payment::where('verified_by', $id)->count(),
            'audit_entries_count' => AuditLog::where('user_id', $id)->count(),
        ];
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->whereNull('read_at')->count();
    }

    /**
     * Kirim email verifikasi dengan tautan publik (bukan route ber-middleware auth)
     * agar user yang belum login bisa memverifikasi dan otomatis login.
     */
    public function sendEmailVerificationNotification(): void
    {
        if (! $this->hasVerifiedEmail()) {
            Mail::to($this->email)->queue(new \App\Mail\VerifyEmail($this));
        }
    }
}
