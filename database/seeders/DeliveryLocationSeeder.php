<?php

namespace Database\Seeders;

use App\Models\DeliveryLocation;
use Illuminate\Database\Seeder;

class DeliveryLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $locations = [
            ['city' => 'Abayapura', 'shipping_charge' => 200],
            ['city' => 'Andamkulam', 'shipping_charge' => 300],
            ['city' => 'Anpuvalipuram', 'shipping_charge' => 200],
            ['city' => 'Chinabay', 'shipping_charge' => 500],
            ['city' => 'Illuppaikulam', 'shipping_charge' => 200],
            ['city' => 'Jinnanagar', 'shipping_charge' => 200],
            ['city' => 'Kanniya', 'shipping_charge' => 300],
            ['city' => 'Kappalthurai', 'shipping_charge' => 500],
            ['city' => 'Linganagar', 'shipping_charge' => 200],
            ['city' => 'Manayaveli', 'shipping_charge' => 250],
            ['city' => 'Orr\'s Hill', 'shipping_charge' => 200],
            ['city' => 'Palaiyuthu', 'shipping_charge' => 200],
            ['city' => 'Salli', 'shipping_charge' => 250],
            ['city' => 'Thirukkadaloor', 'shipping_charge' => 200],
            ['city' => 'Sonakavadi', 'shipping_charge' => 200],
            ['city' => 'Sivapuri', 'shipping_charge' => 200],
            ['city' => 'Varothayanagar', 'shipping_charge' => 200],
            ['city' => 'Thillainagar', 'shipping_charge' => 200],
        ];

        foreach ($locations as $locationData) {
            DeliveryLocation::create([
                'city' => $locationData['city'],
                'shipping_charge' => $locationData['shipping_charge'],
                'is_active' => true,
            ]);
        }
    }
}