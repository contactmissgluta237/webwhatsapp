<?php

namespace Database\Seeders;

use App\Models\Geography\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            // Pays prioritaires (Afrique francophone)
            ['name' => 'Cameroun', 'code' => 'CM', 'phone_code' => '+237', 'flag_emoji' => '🇨🇲', 'sort_order' => 1],
            ['name' => 'Sénégal', 'code' => 'SN', 'phone_code' => '+221', 'flag_emoji' => '🇸🇳', 'sort_order' => 2],
            ['name' => 'Côte d\'Ivoire', 'code' => 'CI', 'phone_code' => '+225', 'flag_emoji' => '🇨🇮', 'sort_order' => 3],
            ['name' => 'Mali', 'code' => 'ML', 'phone_code' => '+223', 'flag_emoji' => '🇲🇱', 'sort_order' => 4],
            ['name' => 'Burkina Faso', 'code' => 'BF', 'phone_code' => '+226', 'flag_emoji' => '🇧🇫', 'sort_order' => 5],

            // Pays populaires
            ['name' => 'France', 'code' => 'FR', 'phone_code' => '+33', 'flag_emoji' => '🇫🇷', 'sort_order' => 10],
            ['name' => 'Canada', 'code' => 'CA', 'phone_code' => '+1', 'flag_emoji' => '🇨🇦', 'sort_order' => 11],
            ['name' => 'États-Unis', 'code' => 'US', 'phone_code' => '+1', 'flag_emoji' => '🇺🇸', 'sort_order' => 12],

            // Autres pays africains
            ['name' => 'Nigeria', 'code' => 'NG', 'phone_code' => '+234', 'flag_emoji' => '🇳🇬', 'sort_order' => 20],
            ['name' => 'Ghana', 'code' => 'GH', 'phone_code' => '+233', 'flag_emoji' => '🇬🇭', 'sort_order' => 21],
            ['name' => 'Kenya', 'code' => 'KE', 'phone_code' => '+254', 'flag_emoji' => '🇰🇪', 'sort_order' => 22],
        ];

        foreach ($countries as $country) {
            Country::create($country);
        }
    }
}
