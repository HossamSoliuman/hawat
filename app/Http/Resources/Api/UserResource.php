<?php

namespace App\Http\Resources\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * حساب التطبيق كما يراه التطبيق: الهوية والدور ومن يتبعه. لا كلمة مرور ولا
 * رموز؛ ودور الوزارة لا شأن للتطبيق به.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'role' => $this->whenLoaded('appRole', fn () => [
                'key' => $this->appRole->key,
                'name' => $this->appRole->name,
                'name_en' => $this->appRole->name_en,
            ]),
            'owner' => $this->whenLoaded('owner', fn () => $this->owner ? [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'phone' => $this->owner->phone,
            ] : null),
            // الميناء يُعرض في الملف الشخصي (الهفوف — المحافظة): للعدّاد من سجلّ
            // موظف الإحصاء، وللدلال من ملفه — انظر User::getPortAttribute.
            'port' => $this->when(
                $this->relationLoaded('statisticsOfficer') || $this->relationLoaded('dalalProfile'),
                fn () => $this->port ? [
                    'id' => $this->port->id,
                    'name' => $this->port->name,
                    'governorate' => $this->port->governorate?->name,
                ] : null,
            ),
            'active' => $this->active,
            'locale' => $this->locale,
            'avatar_url' => $this->avatar_path ? asset('storage/'.$this->avatar_path) : null,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
