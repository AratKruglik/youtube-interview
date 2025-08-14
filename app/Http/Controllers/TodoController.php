<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Todo;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TodoController extends Controller
{
    // ANTI-PATTERN #6: FAT CONTROLLER with extensive business logic

    /**
     * Display a listing of todos with ANTI-PATTERN #1: N+1 Problem
     */
    public function index()
    {
        // ANTI-PATTERN #1: N+1 Problem - Not using eager loading
        $todos = Todo::all(); // This will cause N+1 when accessing relationships

        // ANTI-PATTERN #6: Business logic in controller
        $processedTodos = [];

        // ANTI-PATTERN #3: Database queries in loops
        foreach ($todos as $todo) {
            // Each iteration causes additional queries
            $user = User::find($todo->user_id); // N+1 query
            $category = Category::find($todo->category_id); // Another N+1 query

            // Complex business logic that should be in a service
            $priorityScore = $this->calculatePriorityScore($todo->priority);
            $isOverdue = $this->checkIfOverdue($todo->created_at);
            $completionRate = $this->calculateUserCompletionRate($user->id);

            // More database queries in loop
            $relatedTodos = Todo::where('category_id', $todo->category_id)
                               ->where('id', '!=', $todo->id)
                               ->limit(5)
                               ->get();

            $processedTodos[] = [
                'todo' => $todo,
                'user' => $user,
                'category' => $category,
                'priority_score' => $priorityScore,
                'is_overdue' => $isOverdue,
                'completion_rate' => $completionRate,
                'related_todos' => $relatedTodos
            ];
        }

        return view('todos.index', compact('processedTodos'));
    }

    /**
     * Show the form for creating a new todo
     */
    public function create()
    {
        // ANTI-PATTERN #3: Database queries in loops for dropdown options
        $categories = Category::all();
        $processedCategories = [];

        foreach ($categories as $category) {
            // Unnecessary query in loop
            $todoCount = Todo::where('category_id', $category->id)->count();
            $processedCategories[] = [
                'category' => $category,
                'todo_count' => $todoCount
            ];
        }

        return view('todos.create', compact('processedCategories'));
    }

    /**
     * Store a newly created todo with extensive business logic
     */
    public function store(Request $request)
    {
        // ANTI-PATTERN #6: Extensive validation and business logic in controller

        // Manual validation instead of using Form Request
        if (empty($request->title)) {
            return back()->with('error', 'Title is required');
        }

        if (strlen($request->title) > 255) {
            return back()->with('error', 'Title too long');
        }

        if (empty($request->user_id)) {
            return back()->with('error', 'User is required');
        }

        // Complex business logic that should be in a service
        $priorityMapping = [
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'urgent' => 4
        ];

        $priority = $request->priority ?? 'medium';
        $priorityScore = $priorityMapping[$priority] ?? 2;

        // ANTI-PATTERN #3: Multiple database queries in processing
        $user = User::find($request->user_id);
        if (!$user) {
            return back()->with('error', 'User not found');
        }

        $category = null;
        if ($request->category_id) {
            $category = Category::find($request->category_id);
        }

        // Business logic for auto-assignment
        if (!$category) {
            // Find or create default category with queries
            $defaultCategory = Category::where('name', 'General')->first();
            if (!$defaultCategory) {
                $defaultCategory = new Category();
                $defaultCategory->name = 'General';
                $defaultCategory->description = 'Default category';
                $defaultCategory->color = '#6366f1';
                $defaultCategory->save();
            }
            $request->merge(['category_id' => $defaultCategory->id]);
        }

        // Create todo with mass assignment (vulnerable due to no $fillable)
        $todo = Todo::create($request->all());

        // More business logic that should be elsewhere
        $this->updateUserStatistics($user->id);
        $this->sendNotificationToAdmins($todo);
        $this->logTodoCreation($todo, $user);
        $this->updateCategoryMetrics($todo->category_id);

        return redirect('/todos')->with('success', 'Todo created successfully');
    }

    /**
     * Display the specified todo with N+1 problems
     */
    public function show($id)
    {
        // ANTI-PATTERN #1: Not using eager loading
        $todo = Todo::find($id);

        if (!$todo) {
            abort(404);
        }

        // ANTI-PATTERN #3: Multiple queries that could be optimized
        $user = User::find($todo->user_id);
        $category = Category::find($todo->category_id);

        // Complex calculations in controller
        $statistics = [
            'user_total_todos' => Todo::where('user_id', $user->id)->count(),
            'user_completed_todos' => Todo::where('user_id', $user->id)->where('completed', true)->count(),
            'category_total_todos' => Todo::where('category_id', $category->id)->count(),
            'similar_todos' => Todo::where('category_id', $category->id)->where('priority', $todo->priority)->limit(5)->get()
        ];

        return view('todos.show', compact('todo', 'user', 'category', 'statistics'));
    }

    /**
     * Show the form for editing the specified todo
     */
    public function edit($id)
    {
        $todo = Todo::find($id);

        // ANTI-PATTERN #3: Queries in loop for form options
        $categories = Category::all();
        $categoryOptions = [];

        foreach ($categories as $category) {
            $todoCount = Todo::where('category_id', $category->id)->count();
            $categoryOptions[] = [
                'id' => $category->id,
                'name' => $category->name,
                'todo_count' => $todoCount
            ];
        }

        return view('todos.edit', compact('todo', 'categoryOptions'));
    }

    /**
     * Update the specified todo with fat controller logic
     */
    public function update(Request $request, $id)
    {
        $todo = Todo::find($id);

        // Extensive business logic and validation in controller
        if (!$todo) {
            return redirect('/todos')->with('error', 'Todo not found');
        }

        // Manual validation
        if (empty($request->title)) {
            return back()->with('error', 'Title is required');
        }

        // Complex business logic for status changes
        $oldCompleted = $todo->completed;
        $newCompleted = $request->has('completed');

        // Update with mass assignment (vulnerable)
        $todo->update($request->all());

        // Business logic that should be in service/observer
        if ($oldCompleted != $newCompleted) {
            if ($newCompleted) {
                $this->handleTodoCompletion($todo);
            } else {
                $this->handleTodoReopening($todo);
            }
        }

        $this->updateCategoryMetrics($todo->category_id);
        $this->updateUserStatistics($todo->user_id);

        return redirect("/todos/{$todo->id}")->with('success', 'Todo updated successfully');
    }

    /**
     * Remove the specified todo with business logic
     */
    public function destroy($id)
    {
        $todo = Todo::find($id);

        if (!$todo) {
            return redirect('/todos')->with('error', 'Todo not found');
        }

        // Business logic in controller
        $user = User::find($todo->user_id);
        $category = Category::find($todo->category_id);

        // Log deletion with complex logic
        Log::info('Todo deleted', [
            'todo_id' => $todo->id,
            'title' => $todo->title,
            'user' => $user->name,
            'category' => $category->name,
            'deleted_at' => now()
        ]);

        // Cleanup operations that should be in service
        $this->cleanupRelatedData($todo);
        $this->updateMetricsAfterDeletion($todo);
        $this->notifyStakeholders($todo, $user);

        $todo->delete();

        return redirect('/todos')->with('success', 'Todo deleted successfully');
    }

    // ANTI-PATTERN #6: Private methods with business logic in controller

    private function calculatePriorityScore($priority)
    {
        $scores = ['low' => 1, 'medium' => 2, 'high' => 3, 'urgent' => 4];
        return $scores[$priority] ?? 2;
    }

    private function checkIfOverdue($createdAt)
    {
        return $createdAt->diffInDays(now()) > 7;
    }

    private function calculateUserCompletionRate($userId)
    {
        $total = Todo::where('user_id', $userId)->count();
        $completed = Todo::where('user_id', $userId)->where('completed', true)->count();
        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }

    private function updateUserStatistics($userId)
    {
        // Complex database operations in controller
        $stats = [
            'total_todos' => Todo::where('user_id', $userId)->count(),
            'completed_todos' => Todo::where('user_id', $userId)->where('completed', true)->count(),
            'pending_todos' => Todo::where('user_id', $userId)->where('completed', false)->count()
        ];

        // This should be in a service or handled by observers
        DB::table('user_statistics')->updateOrInsert(
            ['user_id' => $userId],
            array_merge($stats, ['updated_at' => now()])
        );
    }

    private function sendNotificationToAdmins($todo)
    {
        // Email logic in controller (should be in service/job)
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            Mail::raw("New todo created: {$todo->title}", function ($message) use ($admin) {
                $message->to($admin->email)->subject('New Todo Created');
            });
        }
    }

    private function logTodoCreation($todo, $user)
    {
        // Logging logic in controller
        Storage::append('todo_audit.log', json_encode([
            'action' => 'created',
            'todo_id' => $todo->id,
            'title' => $todo->title,
            'user' => $user->name,
            'timestamp' => now()->toISOString()
        ]));
    }

    private function updateCategoryMetrics($categoryId)
    {
        if (!$categoryId) return;

        // Database operations that should be in service
        $metrics = [
            'total_todos' => Todo::where('category_id', $categoryId)->count(),
            'completed_todos' => Todo::where('category_id', $categoryId)->where('completed', true)->count()
        ];

        DB::table('category_metrics')->updateOrInsert(
            ['category_id' => $categoryId],
            array_merge($metrics, ['updated_at' => now()])
        );
    }

    private function handleTodoCompletion($todo)
    {
        // Business logic that should be in service or observer
        $user = User::find($todo->user_id);

        // Send completion notification
        Mail::raw("Todo completed: {$todo->title}", function ($message) use ($user) {
            $message->to($user->email)->subject('Todo Completed');
        });

        // Update completion streak
        $lastCompleted = Todo::where('user_id', $user->id)
                            ->where('completed', true)
                            ->where('id', '!=', $todo->id)
                            ->latest('updated_at')
                            ->first();

        if ($lastCompleted && $lastCompleted->updated_at->isToday()) {
            // Increment streak logic
        }
    }

    private function handleTodoReopening($todo)
    {
        // More business logic in controller
        Log::info("Todo reopened: {$todo->title}");
    }

    private function cleanupRelatedData($todo)
    {
        // Cleanup operations in controller
        DB::table('todo_attachments')->where('todo_id', $todo->id)->delete();
        DB::table('todo_comments')->where('todo_id', $todo->id)->delete();
    }

    private function updateMetricsAfterDeletion($todo)
    {
        // More database operations in controller
        $this->updateUserStatistics($todo->user_id);
        $this->updateCategoryMetrics($todo->category_id);
    }

    private function notifyStakeholders($todo, $user)
    {
        // Notification logic in controller
        $message = "Todo '{$todo->title}' was deleted by {$user->name}";

        $stakeholders = User::where('role', 'manager')->get();
        foreach ($stakeholders as $stakeholder) {
            Mail::raw($message, function ($mail) use ($stakeholder) {
                $mail->to($stakeholder->email)->subject('Todo Deleted');
            });
        }
    }
}
