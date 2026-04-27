<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'provider_key' => 'syriatel_cash',
                'flow_type' => 'otp',
                'name' => ['ar' => 'سيرياتيل كاش', 'en' => 'Syriatel Cash'],
                'order_column' => 10,
            ],
            [
                'provider_key' => 'mtn_cash',
                'flow_type' => 'otp',
                'name' => ['ar' => 'إم تي إن كاش', 'en' => 'MTN Cash'],
                'order_column' => 20,
            ],
            [
                'provider_key' => 'fatora',
                'flow_type' => 'webview',
                'name' => ['ar' => 'بطاقة بنكية (فاتورة)', 'en' => 'Bank Card (Fatora)'],
                'order_column' => 30,
            ],
            [
                'provider_key' => 'sama_pay',
                'flow_type' => 'webview',
                'name' => ['ar' => 'سما باي', 'en' => 'Sama Pay'],
                'order_column' => 40,
            ],
            [
                'provider_key' => 'cash',
                'flow_type' => 'internal',
                'name' => ['ar' => 'الدفع النقدي في الملعب', 'en' => 'Cash at Venue'],
                'order_column' => 50,
            ],
        ];

        foreach ($methods as $attrs) {
            PaymentMethod::updateOrCreate(
                ['provider_key' => $attrs['provider_key']],
                [
                    'flow_type' => $attrs['flow_type'],
                    'name' => $attrs['name'],
                    'is_active' => true,
                    'order_column' => $attrs['order_column'],
                ],
            );
        }

        $this->command->info('PaymentMethodSeeder: '.count($methods).' methods upserted.');
    }
}
