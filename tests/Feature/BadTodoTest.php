<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class BadTodoTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    // BAD PRACTICE: Global test data that affects other tests
    protected static $globalUser;

    protected static $globalTodo;

    /**
     * BAD PRACTICE: Poorly named test that does multiple things
     */
    public function test_everything_works()
    {
        // BAD PRACTICE: Creating test data manually instead of using factories
        $user = new User;
        $user->name = 'Test User';
        $user->email = 'test@example.com';
        $user->password = bcrypt('password123');
        $user->save();

        // BAD PRACTICE: Direct database insertion instead of using models
        DB::table('todos')->insert([
            'title' => 'Test Todo',
            'description' => 'This is a test',
            'user_id' => $user->id,
            'priority' => 'high',
            'completed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // BAD PRACTICE: Testing multiple unrelated things in one test
        $response = $this->get('/');
        $response->assertStatus(302); // Redirects to todos

        $response = $this->get('/todos');
        $response->assertStatus(200);

        // BAD PRACTICE: Testing implementation details instead of behavior
        $todoCount = DB::table('todos')->count();
        $this->assertEquals(1, $todoCount);

        // BAD PRACTICE: Hard-coded assertions
        $todo = DB::table('todos')->first();
        $this->assertEquals('Test Todo', $todo->title);
        $this->assertEquals($user->id, $todo->user_id);
    }

    /**
     * BAD PRACTICE: Test method that doesn't actually test anything useful
     */
    public function test_stuff()
    {
        $this->assertTrue(true);
        $this->assertFalse(false);
        $this->assertEquals(1, 1);
    }

    /**
     * BAD PRACTICE: Testing with hard-coded IDs that may not exist
     */
    public function test_todo_exists()
    {
        // BAD PRACTICE: Assuming data exists without creating it
        $response = $this->get('/todos/1');
        // This will likely fail because todo with ID 1 may not exist
    }

    /**
     * BAD PRACTICE: Mixing unit and integration testing inappropriately
     */
    public function test_database_and_http_and_models_together()
    {
        // BAD PRACTICE: Testing database directly
        DB::table('users')->insert([
            'name' => 'Another User',
            'email' => 'another@test.com',
            'password' => 'plaintext_password', // BAD PRACTICE: Plain text password
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // BAD PRACTICE: Testing model directly in feature test
        $user = User::where('email', 'another@test.com')->first();
        $this->assertNotNull($user);

        // BAD PRACTICE: Testing HTTP in same test
        $response = $this->post('/todos', [
            'title' => 'New Todo',
            'description' => 'Description here',
            'user_id' => $user->id,
        ]);

        // BAD PRACTICE: Not checking the actual result properly
        $response->assertStatus(302);
    }

    /**
     * BAD PRACTICE: Test with side effects that affect other tests
     */
    public function test_creates_global_data()
    {
        // BAD PRACTICE: Creating global state
        self::$globalUser = User::create([
            'name' => 'Global User',
            'email' => 'global@test.com',
            'password' => bcrypt('password'),
        ]);

        self::$globalTodo = Todo::create([
            'title' => 'Global Todo',
            'description' => 'This affects other tests',
            'user_id' => self::$globalUser->id,
            'priority' => 'medium',
            'completed' => false,
        ]);

        $this->assertNotNull(self::$globalUser);
    }

    /**
     * BAD PRACTICE: Test that depends on global state from another test
     */
    public function test_uses_global_data()
    {
        // BAD PRACTICE: Depending on data from another test
        if (self::$globalTodo) {
            $todo = Todo::find(self::$globalTodo->id);
            $this->assertNotNull($todo);
        } else {
            $this->markTestSkipped('Global todo not created');
        }
    }

    /**
     * BAD PRACTICE: Testing private/internal implementation
     */
    public function test_internal_database_structure()
    {
        // BAD PRACTICE: Testing database schema instead of functionality
        $columns = DB::select('PRAGMA table_info(todos)');
        $columnNames = array_column($columns, 'name');

        $this->assertContains('id', $columnNames);
        $this->assertContains('title', $columnNames);
        $this->assertContains('user_id', $columnNames);
    }

    /**
     * BAD PRACTICE: Test with no meaningful assertions
     */
    public function test_random_things()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        // BAD PRACTICE: Creating data but not testing anything meaningful
        Log::info('Created user: '.$user->id);
        Log::info('Created todo: '.$todo->id);

        // BAD PRACTICE: Weak assertion that doesn't test behavior
        $this->assertInstanceOf(User::class, $user);
        $this->assertInstanceOf(Todo::class, $todo);
    }

    /**
     * BAD PRACTICE: Testing with sleep/timing dependencies
     */
    public function test_with_timing_issues()
    {
        $start = microtime(true);

        // BAD PRACTICE: Adding arbitrary delays in tests
        sleep(1);

        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $end = microtime(true);

        // BAD PRACTICE: Testing timing instead of functionality
        $this->assertGreaterThan(1, $end - $start);
    }

    /**
     * BAD PRACTICE: Test that makes external HTTP calls
     */
    public function test_external_dependencies()
    {
        // BAD PRACTICE: Making real HTTP requests in tests
        $response = file_get_contents('https://httpbin.org/json');
        $data = json_decode($response, true);

        // BAD PRACTICE: Depending on external services
        $this->assertArrayHasKey('slideshow', $data);
    }

    /**
     * BAD PRACTICE: Test that modifies global configuration
     */
    public function test_changes_config()
    {
        // BAD PRACTICE: Modifying global config that affects other tests
        config(['app.name' => 'Modified App Name']);
        config(['app.debug' => false]);

        $this->assertEquals('Modified App Name', config('app.name'));
    }

    /**
     * BAD PRACTICE: Testing with hardcoded file paths and system dependencies
     */
    public function test_file_system_dependencies()
    {
        // BAD PRACTICE: Creating actual files during tests
        $filePath = '/tmp/test_todo_file.txt';
        file_put_contents($filePath, 'Test content');

        $this->assertFileExists($filePath);

        // BAD PRACTICE: Not cleaning up after test
        // unlink($filePath); // Commented out to demonstrate bad practice
    }

    /**
     * BAD PRACTICE: Test with no clear purpose or assertion
     */
    public function test_unclear_purpose()
    {
        $users = User::factory(5)->create();
        $todos = [];

        foreach ($users as $user) {
            $todos[] = Todo::factory()->create(['user_id' => $user->id]);
        }

        // BAD PRACTICE: Complex setup with no clear test purpose
        $totalTodos = count($todos);
        $this->assertTrue($totalTodos > 0); // Meaningless assertion
    }

    /**
     * BAD PRACTICE: Test method with multiple responsibilities
     */
    public function test_everything_at_once()
    {
        // Testing creation
        $user = User::factory()->create(['email' => 'test@multi.com']);
        $this->assertNotNull($user);

        // Testing validation
        $response = $this->post('/todos', []);
        $response->assertSessionHasErrors();

        // Testing database queries
        $count = DB::table('todos')->count();
        $this->assertIsInt($count);

        // Testing HTTP responses
        $response = $this->get('/todos');
        $response->assertStatus(200);

        // Testing models
        $todo = new Todo(['title' => 'Test']);
        $this->assertEquals('Test', $todo->title);

        // BAD PRACTICE: All unrelated testing concerns in one method
    }

    /**
     * BAD PRACTICE: Testing implementation details of fat controller
     */
    public function test_controller_implementation_details()
    {
        $user = User::factory()->create();

        // BAD PRACTICE: Testing that the controller makes specific database queries
        // instead of testing the actual functionality

        DB::enableQueryLog();
        $this->actingAs($user)->get('/todos');
        $queries = DB::getQueryLog();

        // BAD PRACTICE: Asserting on number of queries (brittle test)
        $this->assertGreaterThan(2, count($queries));

        // BAD PRACTICE: Testing specific SQL patterns
        $foundSelectQuery = false;
        foreach ($queries as $query) {
            if (str_contains($query['query'], 'SELECT * FROM todos')) {
                $foundSelectQuery = true;
            }
        }
        $this->assertTrue($foundSelectQuery);
    }
}
