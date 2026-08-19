<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use Laravel\Sanctum\Sanctum;

class AuthRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_get_cliente_role()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin' // should be ignored
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'role' => User::ROLE_CLIENTE]);
    }

    public function test_user_can_login_and_get_token()
    {
        $user = User::create([
            'name'=>'Login User',
            'email'=>'login@example.com',
            'password'=>bcrypt('password123'),
            'role' => User::ROLE_CLIENTE
        ]);

        $response = $this->postJson('/api/auth/login', ['email'=>'login@example.com','password'=>'password123']);
        $response->assertStatus(200);
        $response->assertJsonStructure(['message','user'=>['id','name','email','role'],'token']);
    }

    public function test_me_requires_auth()
    {
        $user = User::create([
            'name'=>'Me User',
            'email'=>'me@example.com',
            'password'=>bcrypt('password123'),
            'role' => User::ROLE_CLIENTE
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(200);
        $response->assertJsonFragment(['email'=>'me@example.com']);
    }

    public function test_logout_revokes_token()
    {
        $user = User::create([
            'name'=>'Out User',
            'email'=>'out@example.com',
            'password'=>bcrypt('password123'),
            'role' => User::ROLE_CLIENTE
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/logout');
        $response->assertStatus(200);
    }

    public function test_client_can_view_products_but_cannot_create()
    {
        // create a product
        $product = Product::create(['name'=>'P1','description'=>'x','price'=>10,'stock'=>5,'stock_minimo'=>1,'category_id'=>null,'active'=>true]);

        $client = User::create(['name'=>'Client','email'=>'c@example.com','password'=>bcrypt('password123'),'role'=>User::ROLE_CLIENTE]);
        Sanctum::actingAs($client);

        $this->getJson('/api/products')->assertStatus(200);

        $create = $this->postJson('/api/products', ['name'=>'New','price'=>5,'stock'=>1,'stock_minimo'=>1,'category_id'=>null,'active'=>true]);
        $create->assertStatus(403);
    }

    public function test_vendedor_can_create_and_update_product()
    {
        $vendedor = User::create(['name'=>'Seller','email'=>'s@example.com','password'=>bcrypt('password123'),'role'=>User::ROLE_VENDEDOR]);
        Sanctum::actingAs($vendedor);

        $create = $this->postJson('/api/products', ['name'=>'New','price'=>5,'stock'=>1,'stock_minimo'=>1,'category_id'=>null,'active'=>true]);
        $create->assertStatus(201);

        $productId = $create->json('product.id');

        $update = $this->putJson("/api/products/{$productId}", ['name'=>'Updated','price'=>6,'stock'=>2,'stock_minimo'=>1,'category_id'=>null,'active'=>true]);
        $update->assertStatus(200);
    }

    public function test_vendedor_cannot_manage_users()
    {
        $vendedor = User::create(['name'=>'Seller','email'=>'s2@example.com','password'=>bcrypt('password123'),'role'=>User::ROLE_VENDEDOR]);
        Sanctum::actingAs($vendedor);

        $this->getJson('/api/users')->assertStatus(403);
    }

    public function test_admin_can_manage_users_and_change_roles()
    {
        $admin = User::create(['name'=>'Admin','email'=>'admin@example.com','password'=>bcrypt('password123'),'role'=>User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/users')->assertStatus(200);

        $create = $this->postJson('/api/users', ['name'=>'NewUser','email'=>'nu@example.com','password'=>'password123','password_confirmation'=>'password123','role'=>User::ROLE_VENDEDOR]);
        $create->assertStatus(201);

        $this->assertDatabaseHas('users', ['email'=>'nu@example.com','role'=>User::ROLE_VENDEDOR]);
    }

    public function test_unauthenticated_to_protected_route_returns_401()
    {
        $this->postJson('/api/products', [])->assertStatus(401);
    }

    public function test_authenticated_without_permission_returns_403()
    {
        $client = User::create(['name'=>'Client2','email'=>'c2@example.com','password'=>bcrypt('password123'),'role'=>User::ROLE_CLIENTE]);
        Sanctum::actingAs($client);

        $this->postJson('/api/categories', ['name'=>'x'])->assertStatus(403);
    }
}
