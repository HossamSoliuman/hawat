<?php

namespace App\Http\Resources\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * الكابتن = حساب دخول + سجلّ صياد. `version` نسخة سجلّ الصياد، وهي ما يُرسل
 * في If-Match عند التعديل. `invitation` حالة دعوته: مدعوّ، أو انتهت، أو مفعّل.
 */
class CaptainResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $fisher = $this->relationLoaded('fisher') ? $this->fisher : null;
        $fisher?->setRelation('user', $this->resource);
        $updatedAt = collect([$this->updated_at, $fisher?->updated_at])->filter()->max();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'avatar_url' => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null,
            'active' => (bool) $this->active,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'invitation' => [
                'status' => $this->invitation_status,
                'invited_at' => $this->invited_at?->toIso8601String(),
                'expires_at' => $this->invitation_status === User::INVITATION_ACTIVATED ? null : $this->invitation_expires_at?->toIso8601String(),
                'activated_at' => $this->activated_at?->toIso8601String(),
            ],
            'version' => $fisher ? (int) $fisher->version : null,
            'updated_at' => $updatedAt?->toIso8601String(),
            'fisher' => $this->whenLoaded('fisher', fn () => $this->fisher ? new FisherResource($this->fisher) : null),
        ];
    }
}
