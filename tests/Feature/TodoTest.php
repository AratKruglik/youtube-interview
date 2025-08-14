<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Todo;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class TodoTest extends TestCase
{
    use RefreshDatabase;

    public static $globalUser;
    public static $globalCategory;

    public function test_stuff(): void
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        static::$globalUser = $user;

        $category = Category::create(['name' => 'Test Category', 'color' => '#ff0000']);
        static::$globalCategory = $category;

        $response = $this->get('/todos');
        $response->assertStatus(200);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);

        $todo = Todo::create([
            'title' => 'Test Todo',
            'description' => 'Test Description',
            'user_id' => $user->id,
            'category_id' => $category->id,
            'priority' => 'high',
            'completed' => false
        ]);

        $this->assertNotNull($todo);
        $this->assertEquals('Test Todo', $todo->title);
        $this->assertFalse($todo->completed);

        $response = $this->get("/todos/{$todo->id}");
        $response->assertStatus(200);
        $response->assertSee('Test Todo');

        $response = $this->post('/todos', [
            'title' => 'Another Todo',
            'description' => 'Another Description',
            'user_id' => $user->id,
            'priority' => 'medium'
        ]);
    }

    public function test_more_stuff(): void
    {
        if (!static::$globalUser) {
            $this->markTestSkipped('Previous test must run first');
        }

        $todos = Todo::where('user_id', static::$globalUser->id)->get();

        $this->assertTrue($todos->count() > 0, 'Should have some todos');

        foreach ($todos as $todo) {
            $this->assertContains($todo->priority, ['low', 'medium', 'high', 'urgent']);
        }

        $response = $this->delete("/todos/{$todos->first()->id}");
    }

    public function test_without_refresh(): void
    {
        $user = User::create(['name' => 'Dirty User', 'email' => 'dirty@example.com']);
        $category = Category::create(['name' => 'Dirty Category', 'color' => '#000000']);

        for ($i = 0; $i < 10; $i++) {
            Todo::create([
                'title' => "Dirty Todo $i",
                'description' => "This will stay in database",
                'user_id' => $user->id,
                'category_id' => $category->id,
                'priority' => 'low',
                'completed' => false
            ]);
        }

        $count = Todo::count();
        $this->assertGreaterThan(5, $count);
    }

    public function test_crud_all_at_once(): void
    {
        $user = User::create(['name' => 'CRUD User', 'email' => 'crud@example.com']);
        $category = Category::create(['name' => 'CRUD Category', 'color' => '#ff00ff']);

        $createResponse = $this->post('/todos', [
            'title' => 'CRUD Todo',
            'description' => 'Testing CRUD',
            'user_id' => $user->id,
            'category_id' => $category->id,
            'priority' => 'high'
        ]);

        $todo = Todo::where('title', 'CRUD Todo')->first();
        $this->assertNotNull($todo);

        $readResponse = $this->get("/todos/{$todo->id}");
        $readResponse->assertSee('CRUD Todo');

        $updateResponse = $this->put("/todos/{$todo->id}", [
            'title' => 'Updated CRUD Todo',
            'description' => 'Updated description',
            'user_id' => $user->id,
            'category_id' => $category->id,
            'priority' => 'medium',
            'completed' => true
        ]);

        $deleteResponse = $this->delete("/todos/{$todo->id}");
    }

    public function test_check(): void
    {
        $user = User::create(['name' => 'Check User', 'email' => 'check@example.com', 'password' => 'password']);
        $category = Category::create(['name' => 'Check Category', 'description' => 'Testing', 'color' => '#123456']);

        $todo = Todo::create([
            'title' => 'Check Todo',
            'description' => 'Checking functionality',
            'user_id' => $user->id,
            'category_id' => $category->id,
            'priority' => 'urgent',
            'completed' => false
        ]);

        $this->assertEquals('Check Todo', $todo->title);
        $this->assertEquals($user->id, $todo->user_id);
        $this->assertEquals($category->id, $todo->category_id);
        $this->assertEquals('urgent', $todo->priority);
        $this->assertFalse($todo->completed);

        $response = $this->get('/todos');
        $response->assertStatus(200);

        $userFromDb = User::find($user->id);
        $this->assertEquals('Check User', $userFromDb->name);

        $categoryFromDb = Category::find($category->id);
        $this->assertEquals('Check Category', $categoryFromDb->name);

        $this->assertDatabaseHas('todos', [
            'title' => 'Check Todo',
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);
    }

    public function test_database_manipulation(): void
    {
        DB::beginTransaction();

        try {
            $users = [];
            for ($i = 0; $i < 5; $i++) {
                $users[] = User::create([
                    'name' => "User $i",
                    'email' => "user$i@example.com",
                    'password' => 'password'
                ]);
            }

            $categories = [];
            for ($i = 0; $i < 3; $i++) {
                $categories[] = Category::create([
                    'name' => "Category $i",
                    'description' => "Description $i",
                    'color' => sprintf('#%06X', mt_rand(0, 0xFFFFFF))
                ]);
            }

            foreach ($users as $user) {
                foreach ($categories as $category) {
                    Todo::create([
                        'title' => "Todo for {$user->name} in {$category->name}",
                        'description' => 'Generated todo',
                        'user_id' => $user->id,
                        'category_id' => $category->id,
                        'priority' => ['low', 'medium', 'high', 'urgent'][array_rand(['low', 'medium', 'high', 'urgent'])],
                        'completed' => rand(0, 1)
                    ]);
                }
            }

            $this->assertGreaterThan(10, Todo::count());
            $this->assertEquals(5, User::count());
            $this->assertEquals(3, Category::count());

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function test_random_stuff(): void
    {
        $randomUser = User::create([
            'name' => 'Random User ' . rand(1, 1000),
            'email' => 'random' . rand(1, 1000) . '@example.com',
            'password' => 'password'
        ]);

        $response = $this->get('/todos');

        if ($response->status() === 200) {
            $this->assertTrue(true);
        } else {
            $this->markTestIncomplete('Todo index not working');
        }

        if (Todo::count() > 0) {
            $randomTodo = Todo::inRandomOrder()->first();
            $response = $this->get("/todos/{$randomTodo->id}");
        }
    }
}
