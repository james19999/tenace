<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RoleRedirectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->default('secret');
            $table->string('user_type');
            $table->string('country_code', 2)->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function test_partner_accessing_admin_is_redirected_to_partner_dashboard_without_loop(): void
    {
        $partner = User::query()->create([
            'name' => 'Partner User',
            'email' => 'partner@test.com',
            'user_type' => User::ROLE_PARTNER,
            'country_code' => 'TG',
        ]);

        $response = $this->actingAs($partner)->get('/Admin');

        // Should redirect to partner dashboard, NOT to /Admin
        $response->assertRedirect(route('partner.dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_sales_user_accessing_admin_is_redirected_to_products_without_loop(): void
    {
        $sales = User::query()->create([
            'name' => 'Sales User',
            'email' => 'sales@test.com',
            'user_type' => User::ROLE_SALES,
        ]);

        $response = $this->actingAs($sales)->get('/Admin');

        $response->assertRedirect(route('product'));
    }

    public function test_authenticated_partner_accessing_login_is_redirected_to_partner_dashboard(): void
    {
        $partner = User::query()->create([
            'name' => 'Partner User 2',
            'email' => 'partner2@test.com',
            'user_type' => User::ROLE_PARTNER,
            'country_code' => 'TG',
        ]);

        $response = $this->actingAs($partner)->get('/login');

        $response->assertRedirect(route('partner.dashboard'));
    }

    public function test_authenticated_user_accessing_root_is_redirected_to_their_home(): void
    {
        $partner = User::query()->create([
            'name' => 'Partner User 3',
            'email' => 'partner3@test.com',
            'user_type' => User::ROLE_PARTNER,
            'country_code' => 'TG',
        ]);

        $response = $this->actingAs($partner)->get('/');

        $response->assertRedirect(route('partner.dashboard'));
    }
}
