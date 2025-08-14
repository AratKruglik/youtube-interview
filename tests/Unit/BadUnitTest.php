<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Todo;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase as LaravelTestCase;

// ANTI-PATTERN #5: BAD UNIT TESTS that violate unit testing principles
class BadUnitTest extends LaravelTestCase // Unit test extending Laravel TestCase (wrong!)
{
    use RefreshDatabase; // Unit tests shouldn't need database refresh

    // ANTI-PATTERN: Static properties in unit tests
    public static $sharedData = [];

    // ANTI-PATTERN: Bad test name with no clear indication of what it tests
    public function test_it_works(): void
    {
        // ANTI-PATTERN: Unit test accessing database
        $user = User::create([
            'name' => 'Unit Test User',
            'email' => 'unit@test.com'
        ]);

        // ANTI-PATTERN: Testing multiple unrelated things
        $this->assertNotNull($user);
        $this->assertEquals('Unit Test User', $user->name);

        // ANTI-PATTERN: HTTP request in unit test
        $response = $this->get('/todos');
        $this->assertEquals(200, $response->getStatusCode());

        // Storing state for other tests (bad!)
        static::$sharedData['user_id'] = $user->id;
    }

    // ANTI-PATTERN: Unit test that depends on external state
    public function test_todo_stuff(): void
    {
        // ANTI-PATTERN: Depending on previous test
        if (empty(static::$sharedData)) {
            $this->fail('Previous test must run first');
        }

        // ANTI-PATTERN: Database operations in unit test
        $category = Category::create([
            'name' => 'Unit Category',
            'color' => '#000000'
        ]);

        $todo = Todo::create([
            'title' => 'Unit Todo',
            'description' => 'Unit Description',
            'user_id' => static::$sharedData['user_id'],
            'category_id' => $category->id,
            'priority' => 'high',
            'completed' => false
        ]);

        // ANTI-PATTERN: Testing business logic with database
        $this->assertFalse($todo->completed);

        // ANTI-PATTERN: Making HTTP request in unit test
        $response = $this->get("/todos/{$todo->id}");
        $this->assertEquals(200, $response->getStatusCode());

        // ANTI-PATTERN: Complex assertions testing multiple things
        $this->assertTrue($todo->priority === 'high' && $todo->completed === false);
    }

    // ANTI-PATTERN: Testing Laravel framework functionality
    public function test_laravel_works(): void
    {
        // ANTI-PATTERN: Testing that Laravel's built-in functionality works
        $this->assertTrue(class_exists('Illuminate\Database\Eloquent\Model'));
        $this->assertTrue(function_exists('now'));

        // ANTI-PATTERN: Testing configuration
        $this->assertEquals('local', config('app.env'));
    }

    // ANTI-PATTERN: Unit test with database transactions
    public function test_with_transactions(): void
    {
        // ANTI-PATTERN: Manual database transaction management in unit test
        DB::beginTransaction();

        try {
            $user = User::create(['name' => 'Transaction User', 'email' => 'trans@test.com']);
            $category = Category::create(['name' => 'Transaction Category', 'color' => '#123456']);

            // ANTI-PATTERN: Testing with real database
            $todo = Todo::create([
                'title' => 'Transaction Todo',
                'user_id' => $user->id,
                'category_id' => $category->id,
                'priority' => 'low'
            ]);

            $this->assertDatabaseHas('todos', ['title' => 'Transaction Todo']);

            DB::rollback();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        // This assertion might fail depending on transaction behavior
        $this->assertDatabaseMissing('todos', ['title' => 'Transaction Todo']);
    }

    // ANTI-PATTERN: Unit test testing multiple models at once
    public function test_all_models(): void
    {
        // ANTI-PATTERN: Testing multiple models in single test
        $user = new User(['name' => 'Test User', 'email' => 'test@example.com']);
        $category = new Category(['name' => 'Test Category', 'color' => '#ffffff']);
        $todo = new Todo([
            'title' => 'Test Todo',
            'description' => 'Test Description',
            'priority' => 'medium',
            'completed' => false
        ]);

        // ANTI-PATTERN: Testing model properties without clear purpose
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('Test Category', $category->name);
        $this->assertEquals('Test Todo', $todo->title);

        // ANTI-PATTERN: Suddenly saving to database in unit test
        $user->save();
        $category->save();
        $todo->user_id = $user->id;
        $todo->category_id = $category->id;
        $todo->save();

        // ANTI-PATTERN: Testing relationships with database
        $this->assertEquals($user->id, $todo->user_id);
        $this->assertEquals($category->id, $todo->category_id);
    }

    // ANTI-PATTERN: Unit test with external dependencies
    public function test_with_external_calls(): void
    {
        // ANTI-PATTERN: Making HTTP calls to external services in unit test
        // (Simulated with internal call)
        $response = $this->get('/');

        // ANTI-PATTERN: Testing implementation details
        $this->assertStringContains('Laravel', $response->getContent());

        // ANTI-PATTERN: File system operations in unit test
        $testFile = storage_path('test_file.txt');
        file_put_contents($testFile, 'Unit test data');

        $this->assertFileExists($testFile);
        $this->assertEquals('Unit test data', file_get_contents($testFile));

        // ANTI-PATTERN: No cleanup
        // File will remain on filesystem
    }

    // ANTI-PATTERN: Unit test that sleeps
    public function test_with_timing(): void
    {
        $start = microtime(true);

        // ANTI-PATTERN: Sleep in unit test
        usleep(100000); // 0.1 seconds

        $end = microtime(true);
        $duration = $end - $start;

        // ANTI-PATTERN: Testing timing in unit test (flaky)
        $this->assertGreaterThan(0.05, $duration);
        $this->assertLessThan(0.2, $duration);
    }

    // ANTI-PATTERN: Unit test with random behavior
    public function test_random_behavior(): void
    {
        $randomValue = rand(1, 100);

        // ANTI-PATTERN: Non-deterministic testing
        if ($randomValue > 50) {
            $this->assertGreaterThan(50, $randomValue);
        } else {
            $this->assertLessThanOrEqual(50, $randomValue);
        }

        // ANTI-PATTERN: Creating database records based on random values
        if ($randomValue % 2 == 0) {
            User::create([
                'name' => 'Random User',
                'email' => 'random@test.com'
            ]);
        }

        // This assertion may or may not pass
        $userCount = User::where('email', 'random@test.com')->count();
        $this->assertTrue($userCount >= 0); // Meaningless assertion
    }

    // ANTI-PATTERN: Unit test that tests private methods
    public function test_private_methods(): void
    {
        // ANTI-PATTERN: Creating instance to test private methods
        $controller = new \App\Http\Controllers\TodoController();

        // ANTI-PATTERN: Using reflection to test private methods
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('calculatePriorityScore');
        $method->setAccessible(true);

        $score = $method->invokeArgs($controller, ['high']);
        $this->assertEquals(3, $score);

        // ANTI-PATTERN: Testing implementation details instead of behavior
    }
}
