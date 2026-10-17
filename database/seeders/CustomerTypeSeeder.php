<?php

namespace Database\Seeders;

use App\Models\CustomerType;
use Illuminate\Database\Seeder;

class CustomerTypeSeeder extends Seeder
{
    public const ROWS = [
        ['منتظم', 'Regular'],
        ['دلال', 'Broker'],
        ['سوق سمك', 'Fish market'],
        ['محل سمك', 'Fish shop'],
    ];

    public function run(): void
    {
        foreach (self::ROWS as $order => [$name, $nameEn]) {
            CustomerType::updateOrCreate(['name' => $name], ['name_en' => $nameEn, 'display_order' => $order]);
        }
    }
}
