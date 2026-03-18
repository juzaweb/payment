<?php

namespace Juzaweb\Modules\Payment\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Juzaweb\Modules\Core\Models\User;
use Juzaweb\Modules\Payment\Models\PaymentMethod;
use Juzaweb\Modules\Payment\Tests\TestCase;

class MethodControllerTest extends TestCase
{
    protected $user;

    protected $baseUrl = 'admin/payment-methods';

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(function ($user, $ability) {
            return $user->isSuperAdmin() ? true : null;
        });

        // Ensure language exists
        DB::table('languages')->insertOrIgnore([
            'code' => 'en',
            'name' => 'English',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app()->setLocale('en');

        $this->user = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'is_super_admin' => 1,
        ]);
        $this->user->email_verified_at = now();
        $this->user->save();

        $this->actingAs($this->user);
    }

    public function test_index()
    {
        $response = $this->get($this->baseUrl);

        $response->assertStatus(200);
        $response->assertSee('Payment Methods');
    }

    public function test_create()
    {
        $response = $this->get($this->baseUrl.'/create?locale=en&current_locale=en');

        $response->assertStatus(200);
        $response->assertSee('Create Payment Method');
    }

    public function test_store()
    {
        $response = $this->post($this->baseUrl, [
            'name' => 'Test Method',
            'driver' => 'Custom',
            'locale' => 'en',
            'config' => ['key' => 'value'],
            'active' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payment_method_translations', ['name' => 'Test Method']);
    }

    public function test_edit()
    {
        $method = new PaymentMethod([
            'driver' => 'PayPal',
            'config' => ['client_id' => 'test'],
            'active' => 1,
        ]);
        $method->setAttribute('name', 'Edit Method');
        $method->save();

        $response = $this->get($this->baseUrl."/{$method->id}/edit?locale=en&current_locale=en");

        $response->assertStatus(200);
        $response->assertSee('Edit Payment Method');
    }

    public function test_update()
    {
        $method = new PaymentMethod([
            'driver' => 'PayPal',
            'config' => ['client_id' => 'test'],
            'active' => 1,
        ]);
        $method->setAttribute('name', 'Update Method');
        $method->save();

        $response = $this->put($this->baseUrl."/{$method->id}", [
            'name' => 'Updated Method',
            'driver' => 'PayPal',
            'locale' => 'en',
            'config' => ['client_id' => 'new_value'],
            'active' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payment_method_translations', ['name' => 'Updated Method']);
    }

    public function test_get_data()
    {
        $response = $this->get($this->baseUrl.'/PayPal/get-data');

        $response->assertStatus(200);
    }
}
