<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        // now re-register all the roles and permissions (clears cache and reloads relations)
        $this->seed();
    }

    /**
     * @return void
     */
    public function testCreateUser(): void
    {
        //Role Admin
        $name = $this->faker->name;
        $data = [
            'name' => $name,
            'email' => $this->faker->email,
            'password' => 'password',
            'confirm_password' => 'password',
        ];
        $user = User::firstWhere('email', 'admin@example.com');
        $response = $this->actingAs($user, 'web')->postJson('/api/users',$data);
        $response->assertStatus(200);
        $response->assertJson(['message' => 'User created successfully.']);
        $json = $response->decodeResponseJson();
        $this->assertEquals($json['data']['name'], $name);

        //Role Manager
        $this->app->get('auth')->forgetGuards();
        $name = $this->faker->name;
        $data = [
            'name' => $name,
            'email' => $this->faker->email,
            'password' => 'password',
            'confirm_password' => 'password',
        ];
        $user = User::firstWhere('email', 'manager@example.com');
        $response = $this->actingAs($user, 'web')->postJson('/api/users',$data);
        $response->assertStatus(403);
        $response->assertJson(['message' => 'User does not have the right roles.']);


        //Role User
        $this->app->get('auth')->forgetGuards();
        $name = $this->faker->name;
        $data = [
            'name' => $name,
            'email' => $this->faker->email,
            'password' => 'password',
            'confirm_password' => 'password',
        ];
        $user = User::firstWhere('email', 'user1@example.com');
        $response = $this->actingAs($user, 'web')->postJson('/api/users',$data);
        $response->assertStatus(403);

    }

    /**
     * @return void
     */
    public function testCreateUserRole(): void
    {
        //UserRole
        $name = $this->faker->name;
        $data = [
            'name' => $name,
            'email' => $this->faker->email,
            'password' => 'password',
            'confirm_password' => 'password',
        ];
        $user = User::firstWhere('email', 'admin@example.com');
        $response = $this->actingAs($user, 'web')->postJson('/api/users', $data);
        $response->assertStatus(200);
        $response->assertJson(['message' => 'User created successfully.']);
        $json = $response->decodeResponseJson();
        $this->assertEquals($json['data']['role'], 'user');
        $this->app->get('auth')->forgetGuards();
        //Manager Roles
        $data = [
            'name' =>  $this->faker->name,
            'email' => $this->faker->email,
            'password' => 'password',
            'confirm_password' => 'password',
            'role' => 'manager'
        ];
        $user = User::firstWhere('email', 'admin@example.com');
        $response = $this->actingAs($user, 'web')->postJson('/api/users', $data);
        $response->assertStatus(200);
        $response->assertJson(['message' => 'User created successfully.']);
        $json = $response->decodeResponseJson();
        $this->assertEquals($json['data']['role'], 'manager');
    }

    /**
     * @return void
     */
    public function testGetUsers(): void
    {
        // Role Admin
        $user = User::firstWhere('email', 'admin@example.com');
        $response = $this->actingAs($user, 'web')->getJson('/api/users/',);
        $response->assertStatus(200);

        //Role Manager
        $this->app->get('auth')->forgetGuards();
        $user = User::firstWhere('email', 'manager@example.com');
        $response = $this->actingAs($user, 'web')->getJson('/api/users/',);
        $response->assertStatus(403);
        $response->assertJson(['message' => 'User does not have the right roles.']);

        //Role User
        $this->app->get('auth')->forgetGuards();
        $user = User::firstWhere('email', 'manager@example.com');
        $response = $this->actingAs($user, 'web')->getJson('/api/users/',);
        $response->assertStatus(403);
        $response->assertJson(['message' => 'User does not have the right roles.']);


    }



    /**
     * @return void
     */
    public function testViewUser(): void
    {
        // Role Admin
        $user = User::firstWhere('email', 'admin@example.com');
        $response = $this->actingAs($user, 'web')->getJson('/api/users/'.$user->id);
        $response->assertStatus(200);

        //Role Manager
        $this->app->get('auth')->forgetGuards();
        $user = User::firstWhere('email', 'manager@example.com');
        $response = $this->actingAs($user, 'web')->getJson('/api/users/'.$user->id);
        $response->assertStatus(403);
        $response->assertJson(['message' => 'User does not have the right roles.']);

        //Role User
        $this->app->get('auth')->forgetGuards();
        $user = User::firstWhere('email', 'manager@example.com');
        $response = $this->actingAs($user, 'web')->getJson('/api/users/'.$user->id);
        $response->assertStatus(403);
        $response->assertJson(['message' => 'User does not have the right roles.']);

    }

    /**
     * @return void
     */
    public function testUpdateUser(): void
    {
        $name = $this->faker->name;
        $email = $this->faker->email;
        $data = [
            'name' => $name,
            'email' => $email,
        ];
        $user = User::firstWhere('email', 'admin@example.com');
        $response = $this->actingAs($user, 'web')->putJson('/api/users/'.$user->id,$data);
        $response->assertStatus(200);
        $json = $response->decodeResponseJson();
        $this->assertEquals($json['data']['name'], $name);


        // Test Updated role
        $data = [
            'role' => 'manager',
        ];
        $response = $this->actingAs($user, 'web')->putJson('/api/users/'.$user->id,$data);
        $response->assertStatus(200);
        $json = $response->decodeResponseJson();
        $this->assertContains('manager', $json['data']['role']);

    }

    /**
     * @return void
     */
    public function testDeleteUser(): void
    {
        $user = User::firstWhere('email', 'user1@example.com');

        //Role Manager
        $admin = User::firstWhere('email', 'manager@example.com');
        $response = $this->actingAs($admin, 'web')->deleteJson('/api/users/'.$user->id);
        $response->assertStatus(403);
        $response->assertJson(['message' => 'User does not have the right roles.']);

        //Role User
        $this->app->get('auth')->forgetGuards();
        $user = User::firstWhere('email', 'user1@example.com');
        $admin = User::firstWhere('email', 'user2@example.com');
        $response = $this->actingAs($admin, 'web')->deleteJson('/api/users/'.$user->id);
        $response->assertStatus(403);
        $response->assertJson(['message' => 'User does not have the right roles.']);

        // Role Admin
        $this->app->get('auth')->forgetGuards();
        $admin = User::firstWhere('email', 'admin@example.com');
        $response = $this->actingAs($admin, 'web')->deleteJson('/api/users/'.$user->id);
        $response->assertStatus(200);

    }
}
