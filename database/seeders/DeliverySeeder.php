<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;

/**
 * Starting delivery zones for Kolkata. Charges, cut-off times and PIN codes
 * are examples — the owner edits them in Admin → Delivery (Phase 4).
 */
class DeliverySeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            [
                'zone' => [
                    'name' => 'Central & South Kolkata',
                    'description' => 'Park Street, Ballygunge, Gariahat, Bhowanipore, Alipore, Tollygunge, Jadavpur.',
                    'delivery_charge' => 60,
                    'free_delivery_threshold' => 1500,
                    'same_day_available' => true,
                    'same_day_cutoff' => '14:00:00',
                    'lead_days' => 1,
                    'estimated_text' => 'Same day if ordered before 2 pm, otherwise next day.',
                ],
                'pincodes' => [
                    '700016' => 'Park Street', '700017' => 'Park Circus', '700019' => 'Ballygunge',
                    '700020' => 'Bhowanipore', '700025' => 'Kalighat', '700026' => 'Lake Market',
                    '700027' => 'Alipore', '700029' => 'Gariahat', '700032' => 'Jadavpur',
                    '700033' => 'Tollygunge', '700068' => 'Golf Green', '700071' => 'Camac Street',
                ],
            ],
            [
                'zone' => [
                    'name' => 'Salt Lake & New Town',
                    'description' => 'Bidhannagar sectors, New Town action areas, Kestopur, Baguiati.',
                    'delivery_charge' => 80,
                    'free_delivery_threshold' => 1800,
                    'same_day_available' => true,
                    'same_day_cutoff' => '13:00:00',
                    'lead_days' => 1,
                    'estimated_text' => 'Same day if ordered before 1 pm, otherwise next day.',
                ],
                'pincodes' => [
                    '700064' => 'Salt Lake Sector I', '700091' => 'Salt Lake Sector V', '700097' => 'Salt Lake Sector III',
                    '700098' => 'Salt Lake Sector II', '700101' => 'Kestopur', '700102' => 'Kaikhali',
                    '700156' => 'New Town', '700157' => 'New Town Action Area III', '700135' => 'Rajarhat',
                    '700159' => 'Baguiati',
                ],
            ],
            [
                'zone' => [
                    'name' => 'North Kolkata',
                    'description' => 'Shyambazar, Baranagar, Dum Dum, Lake Town, Ultadanga.',
                    'delivery_charge' => 80,
                    'free_delivery_threshold' => 1800,
                    'same_day_available' => false,
                    'same_day_cutoff' => null,
                    'lead_days' => 1,
                    'estimated_text' => 'Delivered the next day.',
                ],
                'pincodes' => [
                    '700004' => 'Shyambazar', '700006' => 'Hatibagan', '700028' => 'Dum Dum',
                    '700036' => 'Baranagar', '700048' => 'Lake Town', '700054' => 'Ultadanga',
                    '700089' => 'Lake Town', '700074' => 'Dum Dum Park',
                ],
            ],
        ];

        foreach ($zones as $data) {
            $zone = DeliveryZone::query()->updateOrCreate(['name' => $data['zone']['name']], $data['zone'] + ['is_active' => true]);

            foreach ($data['pincodes'] as $pincode => $area) {
                $zone->pincodes()->updateOrCreate(['pincode' => (string) $pincode], ['area_name' => $area, 'is_active' => true]);
            }
        }
    }
}
