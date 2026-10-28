<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'Alice Johnson', 'email' => 'alice.johnson@example.com'],
            ['name' => 'Bob Smith', 'email' => 'bob.smith@example.com'],
            ['name' => 'Priya Patel', 'email' => 'priya.patel@example.com'],
            ['name' => 'Mateo Garcia', 'email' => 'mateo.garcia@example.com'],
            ['name' => 'Sofia Muller', 'email' => 'sofia.muller@example.com'],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(['email' => $customer['email']], $customer);
        }
    }
}
