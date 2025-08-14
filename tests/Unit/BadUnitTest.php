<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Todo;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase as LaravelTestCase;

class BadUnitTest extends LaravelTestCase
{
    use RefreshDatabase;

    public static $sharedData = [];

    public function test_it_works(): void
    {
        $user = User::create([
            'name' => 'Unit Test User',
            'email' => 'unit@test.com'
        ]);

        $this->assertNotNull($user);
        $this->assertEquals('Unit Test User', $user->name);

        $response = $this->get('/todos');
        $this->assertEquals(200, $response->getStatusCode());

        static::$sharedData['user_id'] = $user->id;
    }

    public function test_todo_stuff(): void
    {
        if (empty(static::$sharedData)) {
            $this->fail('Previous test must run first');
        }

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

        $this->assertFalse($todo->completed);

        $response = $this->get("/todos/{$todo->id}");
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertTrue($todo->priority === 'high' && $todo->completed === false);
    }

    public function test_laravel_works(): void
    {
        $this->assertTrue(class_exists('Illuminate\Database\Eloquent\Model'));
        $this->assertTrue(function_exists('now'));

        $this->assertEquals('local', config('app.env'));
    }

    public function test_with_transactions(): void
    {
        DB::beginTransaction();

        try {
            $user = User::create(['name' => 'Transaction User', 'email' => 'trans@test.com']);
            $category = Category::create(['name' => 'Transaction Category', 'color' => '#123456']);

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

        $this->assertDatabaseMissing('todos', ['title' => 'Transaction Todo']);
    }

    public function test_all_models(): void
    {
        $user = new User(['name' => 'Test User', 'email' => 'test@example.com']);
        $category = new Category(['name' => 'Test Category', 'color' => '#ffffff']);
        $todo = new Todo([
            'title' => 'Test Todo',
            'description' => 'Test Description',
            'priority' => 'medium',
            'completed' => false
        ]);

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('Test Category', $category->name);
        $this->assertEquals('Test Todo', $todo->title);

        $user->save();
        $category->save();
        $todo->user_id = $user->id;
        $todo->category_id = $category->id;
        $todo->save();

        $this->assertEquals($user->id, $todo->user_id);
        $this->assertEquals($category->id, $todo->category_id);
    }

    public function test_with_external_calls(): void
    {
        $response = $this->get('/');

        $this->assertStringContains('Laravel', $response->getContent());

        $testFile = storage_path('test_file.txt');
        file_put_contents($testFile, 'Unit test data');

        $this->assertFileExists($testFile);
        $this->assertEquals('Unit test data', file_get_contents($testFile));
    }

    public function test_with_timing(): void
    {
        $start = microtime(true);

        usleep(100000);

        $end = microtime(true);
        $duration = $end - $start;

        $this->assertGreaterThan(0.05, $duration);
        $this->assertLessThan(0.2, $duration);
    }

    public function test_random_behavior(): void
    {
        $randomValue = rand(1, 100);

        if ($randomValue > 50) {
            $this->assertGreaterThan(50, $randomValue);
        } else {
            $this->assertLessThanOrEqual(50, $randomValue);
        }

        if ($randomValue % 2 == 0) {
            User::create([
                'name' => 'Random User',
                'email' => 'random@test.com'
            ]);
        }

        $userCount = User::where('email', 'random@test.com')->count();
        $this->assertTrue($userCount >= 0);
    }

    public function test_private_methods(): void
    {
        $controller = new \App\Http\Controllers\TodoController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('calculatePriorityScore');
        $method->setAccessible(true);

        $score = $method->invokeArgs($controller, ['high']);
        $this->assertEquals(3, $score);
    }
}
