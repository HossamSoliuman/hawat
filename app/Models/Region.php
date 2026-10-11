<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Region extends BaseModel
{
    use HasFactory;

    public function governorates()
    {
        return $this->hasMany(Governorate::class);
    }

    public function ports()
    {
        return $this->hasManyThrough(Port::class, Governorate::class);
    }

    /**
     * السجلات التشغيلية المعلّقة بموانئ المنطقة، بعددها، لما وُجد منها.
     *
     * حذف المنطقة يتسلسل إلى محافظاتها وموانئها، والقوارب والصيادون والعدّادون
     * والرحلات تُحذف بحذف الميناء (cascade) وسجلات شركات التشغيل تمنعه (restrict)؛
     * فلا تُحذف منطقة ما دام شيء من هذه قائمًا.
     *
     * @return array<string, int>
     */
    public function usage(): array
    {
        $ports = $this->ports()->pluck('ports.id');

        $counts = [
            'قوارب' => DB::table('boats')->whereIn('port_id', $ports)->count(),
            'صيادون' => DB::table('fishers')->whereIn('port_id', $ports)->count(),
            'عدّادون' => DB::table('statistics_officers')->whereIn('port_id', $ports)->count(),
            'رحلات' => DB::table('trips')->whereIn('departure_port_id', $ports)->orWhereIn('return_port_id', $ports)->count(),
            'موانئ شركات التشغيل' => DB::table('operating_company_ports')->whereIn('port_id', $ports)->count(),
            'جولات توظيف' => DB::table('hiring_rounds')->whereIn('port_id', $ports)->count(),
            'طلبات توظيف' => DB::table('counter_applications')->whereIn('port_id', $ports)->count(),
            'نقل عدّادين' => DB::table('counter_transfers')->whereIn('from_port_id', $ports)->orWhereIn('to_port_id', $ports)->count(),
        ];

        return array_filter($counts);
    }
}
