<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Todo;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

// ANTI-PATTERN #5: BAD STRUCTURED TESTS
class TodoTest extends TestCase
{
    use RefreshDatabase; // Sometimes used, sometimes not - inconsistent

    // ANTI-PATTERN: Global state shared between tests
    public static $globalUser;
    public static $globalCategory;

    // ANTI-PATTERN: Bad test names that don't describe what they test
    public function test_stuff(): void
    {
        // ANTI-PATTERN: No arrange-act-assert structure, everything mixed together
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        static::$globalUser = $user;

        $category = Category::create(['name' => 'Test Category', 'color' => '#ff0000']);
        static::$globalCategory = $category;

        // Testing multiple unrelated things in one test
        $response = $this->get('/todos');
        $response->assertStatus(200);

        // Suddenly testing database without isolation
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);

        // Testing model creation in feature test
        $todo = Todo::create([
            'title' => 'Test Todo',
            'description' => 'Test Description',
            'user_id' => $user->id,
            'category_id' => $category->id,
            'priority' => 'high',
            'completed' => false
        ]);

        // Multiple assertions testing different things
        $this->assertNotNull($todo);
        $this->assertEquals('Test Todo', $todo->title);
        $this->assertFalse($todo->completed);

        // Testing HTTP endpoints in the same test
        $response = $this->get("/todos/{$todo->id}");
        $response->assertStatus(200);
        $response->assertSee('Test Todo');

        // Testing form submission without CSRF (which will fail)
        $response = $this->post('/todos', [
            'title' => 'Another Todo',
            'description' => 'Another Description',
            'user_id' => $user->id,
            'priority' => 'medium'
        ]);
        // This should fail due to CSRF, but we're not handling it properly
    }

    // ANTI-PATTERN: Test depends on previous test's global state
    public function test_more_stuff(): void
    {
        // Using global state from previous test (bad!)
        if (!static::$globalUser) {
            $this->markTestSkipped('Previous test must run first');
        }

        // No proper setup, assuming data exists
        $todos = Todo::where('user_id', static::$globalUser->id)->get();

        // Vague assertion
        $this->assertTrue($todos->count() > 0, 'Should have some todos');

        // Testing business logic in feature test
        foreach ($todos as $todo) {
            // This should be in unit test
            $this->assertContains($todo->priority, ['low', 'medium', 'high', 'urgent']);
        }

        // Suddenly testing HTTP again
        $response = $this->delete("/todos/{$todos->first()->id}");
        // This will also fail due to CSRF but we're not handling it
    }

    // ANTI-PATTERN: Test that doesn't use RefreshDatabase but should
    public function test_without_refresh(): void
    {
        // Creating data without cleaning up
        $user = User::create(['name' => 'Dirty User', 'email' => 'dirty@example.com']);
        $category = Category::create(['name' => 'Dirty Category', 'color' => '#000000']);

        // This data will persist and affect other tests
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

        // Testing count without isolation
        $count = Todo::count();
        $this->assertGreaterThan(5, $count); // Vague assertion
    }

    // ANTI-PATTERN: Testing multiple HTTP methods in one test
    public function test_crud_all_at_once(): void
    {
        // CREATE
        $user = User::create(['name' => 'CRUD User', 'email' => 'crud@example.com']);
        $category = Category::create(['name' => 'CRUD Category', 'color' => '#123456']);

        // READ (index)
        $response = $this->get('/todos');
        $response->assertStatus(200);

        // CREATE (store) - will fail due to CSRF
        $createResponse = $this->post('/todos', [
            'title' => 'CRUD Todo',
            'description' => 'CRUD Description',
            'user_id' => $user->id,
            'category_id' => $category->id,
            'priority' => 'urgent'
        ]);

        // CREATE manually since HTTP create fails
        $todo = Todo::create([
            'title' => 'CRUD Todo',
            'description' => 'CRUD Description',
            'user_id' => $user->id,
            'category_id' => $category->id,
            'priority' => 'urgent',
            'completed' => false
        ]);

        // READ (show)
        $showResponse = $this->get("/todos/{$todo->id}");
        $showResponse->assertStatus(200);

        // UPDATE - will fail due to CSRF
        $updateResponse = $this->patch("/todos/{$todo->id}", [
            'title' => 'Updated CRUD Todo',
            'description' => 'Updated Description',
            'completed' => true,
            'priority' => 'high',
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        // DELETE - will fail due to CSRF
        $deleteResponse = $this->delete("/todos/{$todo->id}");

        // Mixed assertions for different operations
        $this->assertNotNull($todo);
        $this->assertEquals(200, $showResponse->getStatusCode());

        // This will fail because we can't actually update via HTTP
        $this->assertTrue(true); // Meaningless assertion
    }

    // ANTI-PATTERN: Test with unclear purpose and poor naming
    public function test_check(): void
    {
        // What are we checking? Unclear!

        // No setup explanation
        $users = [];
        for ($i = 0; $i < 3; $i++) {
            $users[] = User::create([
                'name' => "User $i",
                'email' => "user$i@example.com"
            ]);
        }

        $category = Category::create(['name' => 'Check Category', 'color' => '#abcdef']);

        // Creating different todos with different priorities
        $priorities = ['low', 'medium', 'high', 'urgent'];
        $todos = [];

        foreach ($users as $index => $user) {
            foreach ($priorities as $priority) {
                $todos[] = Todo::create([
                    'title' => "Todo for {$user->name} - $priority",
                    'description' => "Description $index $priority",
                    'user_id' => $user->id,
                    'category_id' => $category->id,
                    'priority' => $priority,
                    'completed' => rand(0, 1) == 1
                ]);
            }
        }

        // What exactly are we testing here?
        $this->assertCount(12, $todos); // 3 users × 4 priorities = 12

        // Testing business logic in feature test (should be unit test)
        $highPriorityTodos = collect($todos)->where('priority', 'high');
        $this->assertGreaterThan(0, $highPriorityTodos->count());

        // Testing HTTP response with all this data
        $response = $this->get('/todos');
        $response->assertStatus(200);

        // Vague assertion
        $this->assertTrue(strlen($response->getContent()) > 1000);
    }

    // ANTI-PATTERN: Test that modifies global state without cleanup
    public function test_database_manipulation(): void
    {
        // Directly manipulating database tables
        DB::table('users')->insert([
            'name' => 'Direct DB User',
            'email' => 'direct@example.com',
            'password' => 'plaintext-password', // Security issue!
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('categories')->insert([
            'name' => 'Direct DB Category',
            'description' => 'Created directly in database',
            'color' => '#ffffff',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Raw SQL queries in tests
        $userId = DB::table('users')->where('email', 'direct@example.com')->value('id');
        $categoryId = DB::table('categories')->where('name', 'Direct DB Category')->value('id');

        DB::table('todos')->insert([
            'title' => 'Direct DB Todo',
            'description' => 'Created with raw SQL',
            'user_id' => $userId,
            'category_id' => $categoryId,
            'priority' => 'medium',
            'completed' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Testing with raw queries
        $todoCount = DB::table('todos')->where('user_id', $userId)->count();
        $this->assertEquals(1, $todoCount);

        // This data will persist and affect other tests
        // No cleanup performed
    }

    // ANTI-PATTERN: Test with no clear expectations
    public function test_random_stuff(): void
    {
        // Random operations with no clear purpose
        $response1 = $this->get('/');
        $response2 = $this->get('/todos');

        // Meaningless assertions
        $this->assertTrue(true);
        $this->assertNotNull($response1);
        $this->assertNotNull($response2);

        // Creating random data
        if (rand(0, 1)) {
            User::create(['name' => 'Random User', 'email' => 'random@example.com']);
        }

        // Conditional testing (anti-pattern)
        if (User::count() > 0) {
            $this->assertTrue(User::count() > 0);
        } else {
            $this->assertEquals(0, User::count());
        }
    }
}
