<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TodoController extends Controller
{
    /**
     * Display a listing of the resource.
     * BAD PRACTICE: Fat controller with multiple responsibilities
     */
    public function index()
    {
        // BAD PRACTICE: Direct database queries instead of Eloquent
        $todos = DB::select('SELECT * FROM todos WHERE user_id = ?', [auth()->id()]);

        // BAD PRACTICE: N+1 problem - querying users in a loop
        foreach ($todos as $todo) {
            $user = DB::select('SELECT * FROM users WHERE id = ?', [$todo->user_id]);
            $todo->user_name = $user[0]->name ?? 'Unknown';

            // BAD PRACTICE: More queries in loops
            $completedCount = DB::select('SELECT COUNT(*) as count FROM todos WHERE user_id = ? AND completed = 1', [$todo->user_id]);
            $todo->user_completed_count = $completedCount[0]->count;
        }

        // BAD PRACTICE: Business logic in controller
        $stats = [];
        $stats['total'] = count($todos);
        $stats['completed'] = 0;
        $stats['pending'] = 0;
        $stats['overdue'] = 0;

        foreach ($todos as $todo) {
            if ($todo->completed) {
                $stats['completed']++;
            } else {
                $stats['pending']++;
            }

            if (! $todo->completed && $todo->due_date && strtotime($todo->due_date) < time()) {
                $stats['overdue']++;
            }
        }

        // BAD PRACTICE: Caching logic in controller
        Cache::put('user_todo_stats_'.auth()->id(), $stats, 3600);

        // BAD PRACTICE: Logging in controller
        Log::info('User '.auth()->id().' viewed todos', [
            'user_id' => auth()->id(),
            'total_todos' => $stats['total'],
            'timestamp' => now(),
        ]);

        return view('todos.index', compact('todos', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     * BAD PRACTICE: Unnecessary complexity for a simple form
     */
    public function create()
    {
        // BAD PRACTICE: Complex logic for simple form
        $categories = ['Work', 'Personal', 'Shopping', 'Health', 'Education', 'Home'];
        $priorities = ['low', 'medium', 'high'];

        // BAD PRACTICE: Direct queries
        $userTodoCount = DB::select('SELECT COUNT(*) as count FROM todos WHERE user_id = ?', [auth()->id()]);
        $canCreateMore = $userTodoCount[0]->count < 100; // Arbitrary limit

        // BAD PRACTICE: Business logic in controller
        if (! $canCreateMore) {
            return redirect()->back()->with('error', 'You have reached the maximum number of todos (100)');
        }

        return view('todos.create', compact('categories', 'priorities'));
    }

    /**
     * Store a newly created resource in storage.
     * BAD PRACTICE: Fat method with multiple responsibilities
     */
    public function store(Request $request)
    {
        // BAD PRACTICE: Manual validation instead of Form Request
        if (! $request->title) {
            return back()->withErrors(['title' => 'Title is required']);
        }

        if (strlen($request->title) < 3) {
            return back()->withErrors(['title' => 'Title must be at least 3 characters']);
        }

        if (strlen($request->title) > 255) {
            return back()->withErrors(['title' => 'Title cannot exceed 255 characters']);
        }

        if ($request->priority && ! in_array($request->priority, ['low', 'medium', 'high'])) {
            return back()->withErrors(['priority' => 'Invalid priority selected']);
        }

        // BAD PRACTICE: Direct database insert instead of Eloquent
        $todoId = DB::table('todos')->insertGetId([
            'title' => $request->title,
            'description' => $request->description,
            'user_id' => auth()->id(),
            'priority' => $request->priority ?? 'medium',
            'due_date' => $request->due_date,
            'category' => $request->category,
            'completed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // BAD PRACTICE: Business logic in controller
        $user = User::find(auth()->id());
        $todoCount = DB::select('SELECT COUNT(*) as count FROM todos WHERE user_id = ?', [auth()->id()]);

        // BAD PRACTICE: Email logic in controller
        if ($todoCount[0]->count == 1) {
            // Send welcome email for first todo
            Log::info('Sending welcome email to user '.$user->email);
        }

        if ($todoCount[0]->count % 10 == 0) {
            // Send milestone email every 10 todos
            Log::info('User reached '.$todoCount[0]->count.' todos milestone');
        }

        // BAD PRACTICE: Cache invalidation in controller
        Cache::forget('user_todo_stats_'.auth()->id());

        // BAD PRACTICE: Complex logging
        Log::info('Todo created', [
            'todo_id' => $todoId,
            'user_id' => auth()->id(),
            'title' => $request->title,
            'priority' => $request->priority ?? 'medium',
            'has_due_date' => ! empty($request->due_date),
            'category' => $request->category ?? 'uncategorized',
        ]);

        return redirect()->route('todos.index')->with('success', 'Todo created successfully!');
    }

    /**
     * Display the specified resource.
     * BAD PRACTICE: Unnecessary complexity for showing a single todo
     */
    public function show(string $id)
    {
        // BAD PRACTICE: Direct query instead of model
        $todo = DB::select('SELECT * FROM todos WHERE id = ? AND user_id = ?', [$id, auth()->id()]);

        if (empty($todo)) {
            abort(404);
        }

        $todo = $todo[0];

        // BAD PRACTICE: Additional queries that could be eager loaded
        $user = DB::select('SELECT * FROM users WHERE id = ?', [$todo->user_id]);
        $todo->user = $user[0];

        // BAD PRACTICE: Business logic in controller
        $todo->is_overdue = ! $todo->completed && $todo->due_date && strtotime($todo->due_date) < time();
        $todo->days_until_due = $todo->due_date ? ceil((strtotime($todo->due_date) - time()) / 86400) : null;

        // BAD PRACTICE: Related data fetching in controller
        $relatedTodos = DB::select('SELECT * FROM todos WHERE user_id = ? AND category = ? AND id != ? LIMIT 5', [
            $todo->user_id, $todo->category, $id,
        ]);

        return view('todos.show', compact('todo', 'relatedTodos'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // BAD PRACTICE: Direct query
        $todo = DB::select('SELECT * FROM todos WHERE id = ? AND user_id = ?', [$id, auth()->id()]);

        if (empty($todo)) {
            abort(404);
        }

        $todo = $todo[0];
        $categories = ['Work', 'Personal', 'Shopping', 'Health', 'Education', 'Home'];
        $priorities = ['low', 'medium', 'high'];

        return view('todos.edit', compact('todo', 'categories', 'priorities'));
    }

    /**
     * Update the specified resource in storage.
     * BAD PRACTICE: Another fat method with multiple responsibilities
     */
    public function update(Request $request, string $id)
    {
        // BAD PRACTICE: Manual validation again
        if (! $request->title) {
            return back()->withErrors(['title' => 'Title is required']);
        }

        if (strlen($request->title) < 3) {
            return back()->withErrors(['title' => 'Title must be at least 3 characters']);
        }

        // BAD PRACTICE: Direct query to check ownership
        $existingTodo = DB::select('SELECT * FROM todos WHERE id = ? AND user_id = ?', [$id, auth()->id()]);

        if (empty($existingTodo)) {
            abort(404);
        }

        $oldTodo = $existingTodo[0];

        // BAD PRACTICE: Direct update instead of Eloquent
        DB::update('UPDATE todos SET title = ?, description = ?, priority = ?, due_date = ?, category = ?, completed = ?, updated_at = ? WHERE id = ?', [
            $request->title,
            $request->description,
            $request->priority ?? 'medium',
            $request->due_date,
            $request->category,
            $request->has('completed') ? 1 : 0,
            now(),
            $id,
        ]);

        // BAD PRACTICE: Business logic for completion tracking
        if (! $oldTodo->completed && $request->has('completed')) {
            // Task was just completed
            $completedCount = DB::select('SELECT COUNT(*) as count FROM todos WHERE user_id = ? AND completed = 1', [auth()->id()]);

            // BAD PRACTICE: Achievement system in controller
            if ($completedCount[0]->count % 5 == 0) {
                Log::info('User completed '.$completedCount[0]->count.' todos!');
            }
        }

        // BAD PRACTICE: Cache invalidation
        Cache::forget('user_todo_stats_'.auth()->id());

        Log::info('Todo updated', [
            'todo_id' => $id,
            'user_id' => auth()->id(),
            'was_completed' => $oldTodo->completed,
            'now_completed' => $request->has('completed'),
        ]);

        return redirect()->route('todos.index')->with('success', 'Todo updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     * BAD PRACTICE: Complex deletion logic
     */
    public function destroy(string $id)
    {
        // BAD PRACTICE: Direct queries
        $todo = DB::select('SELECT * FROM todos WHERE id = ? AND user_id = ?', [$id, auth()->id()]);

        if (empty($todo)) {
            abort(404);
        }

        $todo = $todo[0];

        // BAD PRACTICE: Business logic in controller
        $userTotalTodos = DB::select('SELECT COUNT(*) as count FROM todos WHERE user_id = ?', [auth()->id()]);
        $userCompletedTodos = DB::select('SELECT COUNT(*) as count FROM todos WHERE user_id = ? AND completed = 1', [auth()->id()]);

        // BAD PRACTICE: Complex logging before deletion
        Log::info('Todo deletion', [
            'todo_id' => $id,
            'todo_title' => $todo->title,
            'was_completed' => $todo->completed,
            'user_id' => auth()->id(),
            'user_total_todos' => $userTotalTodos[0]->count,
            'user_completed_todos' => $userCompletedTodos[0]->count,
        ]);

        // BAD PRACTICE: Direct deletion
        DB::delete('DELETE FROM todos WHERE id = ? AND user_id = ?', [$id, auth()->id()]);

        // BAD PRACTICE: Cache invalidation
        Cache::forget('user_todo_stats_'.auth()->id());

        return redirect()->route('todos.index')->with('success', 'Todo deleted successfully!');
    }
}
