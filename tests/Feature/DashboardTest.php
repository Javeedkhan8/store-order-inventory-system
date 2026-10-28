<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_the_counter_dashboard_with_products(): void
    {
        Product::factory()->create(['name' => 'Test Coffee']);

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Store Manager')
            ->assertSee('Test Coffee');
    }
}
