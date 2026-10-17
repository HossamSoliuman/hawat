<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role_id',
        'owner_id',
        'operating_company_id',
        'active',
        'locale',
        'avatar_path',
        'fcm_token',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'fcm_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    /**
     * الدور والنطاق مُعرّفان في جدول الصلاحيات لا في جدول المستخدمين: هو مرجع
     * الصلاحيات الذي تحرّره بوابة المعلومات نفسها، والربط بالبريد لأنه مفتاحه الفريد.
     */
    public function permission(): HasOne
    {
        return $this->hasOne(UserPermission::class, 'user_email', 'email');
    }

    /**
     * دور التطبيق (مالك، عدّاد، دلال…) — غير دور الوزارة أعلاه: هذا لمن
     * يعمل في البحر والسوق ويدخل من /admin أو من التطبيق، وذاك لموظفي الوزارة.
     */
    public function appRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * المالك الذي يتبعه هذا الحساب (الأدوار في Role::OWNER_MANAGED).
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * الحسابات التابعة لهذا المالك.
     */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class, 'owner_id');
    }

    /**
     * ما يملكه المالك ويديره من بوابته.
     */
    public function boats(): HasMany
    {
        return $this->hasMany(Boat::class, 'owner_id');
    }

    public function ownedTrips(): HasMany
    {
        return $this->hasMany(Trip::class, 'owner_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'account_user_id');
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class, 'owner_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(OwnerEmployee::class, 'owner_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'seller_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'holder_id');
    }

    /**
     * كباتن المالك وطاقمه — سجلات صيادين بلا حساب دخول.
     */
    public function fishers(): HasMany
    {
        return $this->hasMany(Fisher::class, 'owner_id');
    }

    /**
     * إشعارات التطبيق — لا notifications() التي يحجزها Notifiable لقناة قاعدة البيانات.
     */
    public function appNotifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    /**
     * سجلّ موظف الإحصاء لحساب العدّاد — منه ميناؤه ووردّيته.
     */
    public function statisticsOfficer(): HasOne
    {
        return $this->hasOne(StatisticsOfficer::class);
    }

    /**
     * شركة التشغيل لحساب موظفها (دور company). شركة العدّاد في سجلّ موظف
     * الإحصاء لا هنا.
     */
    public function operatingCompany(): BelongsTo
    {
        return $this->belongsTo(OperatingCompany::class);
    }

    /**
     * الرحلات التي عدّها هذا الحساب.
     */
    public function countedTrips(): HasMany
    {
        return $this->hasMany(Trip::class, 'counter_id');
    }

    /**
     * ملف الدلال التجاري (الدكة والسجل التجاري والشعار) — انظر DalalProfile::forUser.
     */
    public function dalalProfile(): HasOne
    {
        return $this->hasOne(DalalProfile::class);
    }

    public function dalalWorkers(): HasMany
    {
        return $this->hasMany(DalalWorker::class, 'dalal_id');
    }

    /**
     * أعداد العمالة القديمة (النوع × الجنسية × العدد) قبل تسجيل العمال أفرادًا.
     */
    public function dalalWorkerCounts(): HasMany
    {
        return $this->hasMany(DalalWorkerCount::class, 'dalal_id');
    }

    /**
     * طلبات التعامل: ما أرسله المالك إلى الدلالين، وما وصل الدلال من الملاك.
     */
    public function ownerPartnerships(): HasMany
    {
        return $this->hasMany(DalalPartnership::class, 'owner_id');
    }

    public function dalalPartnerships(): HasMany
    {
        return $this->hasMany(DalalPartnership::class, 'dalal_id');
    }

    /**
     * ميناء الحساب: من سجلّ موظف الإحصاء للعدّاد، ومن ملف الدلال للدلال.
     * يُعرض في الملف الشخصي ويُحسب عليه طابور العدّاد.
     */
    public function getPortAttribute(): ?Port
    {
        return $this->statisticsOfficer?->port ?? $this->dalalProfile?->port;
    }

    /**
     * موانئ العدّاد التي يعدّ فيها — طابوره يُحسب عليها. سجلّ واحد اليوم،
     * والدالة ترجع قائمة حتى يُسند إليه أكثر من ميناء دون تغيير المستدعين.
     *
     * @return array<int, int>
     */
    public function counterPortIds(): array
    {
        return array_values(array_filter([$this->statisticsOfficer?->port_id]));
    }

    /**
     * مفتاح دور التطبيق أو null لمن لا دور له (موظفو الوزارة).
     */
    public function getAppRoleKeyAttribute(): ?string
    {
        return $this->appRole?->key;
    }

    public function hasAppRole(string ...$keys): bool
    {
        return $this->app_role_key !== null && in_array($this->app_role_key, $keys, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasAppRole(Role::SUPER_ADMIN);
    }

    /**
     * الاسم المعروض في ذيل القائمة الجانبية: دور التطبيق إن وُجد وإلا دور الوزارة.
     */
    public function getDisplayRoleAttribute(): string
    {
        return $this->appRole?->name ?? $this->role_label;
    }

    /**
     * أول حرف من الاسم — رمز الصورة الشخصية حين لا صورة.
     */
    public function getInitialAttribute(): string
    {
        return mb_substr(trim((string) $this->name), 0, 1) ?: 'م';
    }

    /**
     * دور المستخدم — يقرؤه سجل العمليات ليُنسب إليه ما يكتبه. من لا صلاحية
     * مسجّلة له فهو مستخدم عادي، لا مدير.
     */
    public function getRoleAttribute(): string
    {
        return $this->permission?->role ?? 'user';
    }

    /**
     * تسمية الدور بالعربية — تُعرض في ذيل القائمة الجانبية.
     */
    public function getRoleLabelAttribute(): string
    {
        return [
            'admin' => 'مدير النظام',
            'top_management' => 'الإدارة العليا',
            'fisheries_admin' => 'إدارة المصايد',
            'researcher' => 'باحث',
            'supervision' => 'الرقابة',
            'region_manager' => 'مدير منطقة',
            'governorate_manager' => 'مدير محافظة',
            'port_manager' => 'مدير ميناء',
        ][$this->role] ?? 'مستخدم';
    }

    /**
     * يطبّع رقم الجوال إلى صيغة واحدة (05XXXXXXXX): يقبل +966 و966 والمسافات
     * والأرقام العربية، فلا يُسجَّل الجوال نفسه مرتين بصيغتين.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $western = str_replace(
            ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $phone,
        );

        $digits = preg_replace('/\D+/u', '', $western);

        if ($digits === '' || $digits === null) {
            return null;
        }

        if (str_starts_with($digits, '00966')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '966')) {
            $digits = substr($digits, 3);
        }

        if (strlen($digits) === 9 && str_starts_with($digits, '5')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }
}
