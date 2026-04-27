<?php

namespace Database\Seeders\Emergency;

use App\Models\Emergency\EmergencyContact;
use Illuminate\Database\Seeder;

class EmergencyContactsSeeder extends Seeder
{
    public function run(): void
    {
        $contacts = [
            ['name' => 'Ambulance', 'name_ar' => 'الإسعاف', 'phone' => '110', 'type' => 'ambulance', 'icon_name' => 'medical-cross'],
            ['name' => 'Fire Department', 'name_ar' => 'الإطفاء', 'phone' => '113', 'type' => 'fire', 'icon_name' => 'flame'],
            ['name' => 'Police', 'name_ar' => 'الشرطة', 'phone' => '112', 'type' => 'police', 'icon_name' => 'shield'],
            ['name' => 'Civil Defense', 'name_ar' => 'الدفاع المدني', 'phone' => '102', 'type' => 'civil_defense', 'icon_name' => 'shield-alert'],
            ['name' => 'Daq Ehjizli Support', 'name_ar' => 'دعم دق احجزلي', 'phone' => '+963991234567', 'type' => 'support', 'icon_name' => 'phone'],
            ['name' => 'Daq Ehjizli Emergency', 'name_ar' => 'طوارئ دق احجزلي', 'phone' => '+963991111111', 'type' => 'platform_emergency', 'icon_name' => 'alert-triangle'],
        ];

        foreach ($contacts as $i => $c) {
            EmergencyContact::query()->updateOrCreate(
                ['name_ar' => $c['name_ar'], 'type' => $c['type']],
                array_merge($c, ['display_order' => $i + 1, 'is_active' => true])
            );
        }
    }
}
