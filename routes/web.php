<?php

use App\Http\Controllers\TodoController;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('todos.index');
});

// BAD PRACTICE: No proper authentication middleware
Route::resource('todos', TodoController::class);

// BAD PRACTICE: Additional routes with terrible N+1 problems
Route::get('/dashboard', function () {
    // BAD PRACTICE: Direct queries in routes
    $users = DB::select('SELECT * FROM users');

    // BAD PRACTICE: N+1 problem - querying todos for each user
    foreach ($users as $user) {
        $todos = DB::select('SELECT * FROM todos WHERE user_id = ?', [$user->id]);
        $user->todos_count = count($todos);

        // BAD PRACTICE: More queries in loops
        foreach ($todos as $todo) {
            $categories = DB::select('SELECT DISTINCT category FROM todos WHERE user_id = ?', [$user->id]);
            $todo->user_categories = $categories;
        }
    }

    return view('dashboard', compact('users'));
})->name('dashboard');

// BAD PRACTICE: Route with business logic and no CSRF protection
Route::get('/stats/{user_id}', function ($userId) {
    // BAD PRACTICE: No authorization check
    $user = DB::select('SELECT * FROM users WHERE id = ?', [$userId]);

    if (empty($user)) {
        abort(404);
    }

    $user = $user[0];

    // BAD PRACTICE: Multiple separate queries instead of joins
    $allTodos = DB::select('SELECT * FROM todos WHERE user_id = ?', [$userId]);

    $stats = [];
    foreach ($allTodos as $todo) {
        // BAD PRACTICE: Query in loop for each todo
        $sameCategoryCount = DB::select('SELECT COUNT(*) as count FROM todos WHERE user_id = ? AND category = ?',
            [$userId, $todo->category]);
        $todo->category_count = $sameCategoryCount[0]->count;

        // BAD PRACTICE: Another query in loop
        $samePriorityCount = DB::select('SELECT COUNT(*) as count FROM todos WHERE user_id = ? AND priority = ?',
            [$userId, $todo->priority]);
        $todo->priority_count = $samePriorityCount[0]->count;
    }

    return response()->json([
        'user' => $user,
        'todos' => $allTodos,
        'total_queries_executed' => 'way too many!',
    ]);
})->name('user.stats');

// BAD PRACTICE: Bulk operations with terrible performance
Route::post('/bulk-update', function () {
    $todoIds = request('todo_ids', []);

    // BAD PRACTICE: Individual queries for each todo instead of batch operations
    foreach ($todoIds as $todoId) {
        $todo = DB::select('SELECT * FROM todos WHERE id = ?', [$todoId]);

        if (! empty($todo)) {
            // BAD PRACTICE: Separate update for each todo
            DB::update('UPDATE todos SET completed = ? WHERE id = ?', [1, $todoId]);

            // BAD PRACTICE: Log each individual update
            $user = DB::select('SELECT * FROM users WHERE id = ?', [$todo[0]->user_id]);
            logger()->info('Bulk updated todo', [
                'todo_id' => $todoId,
                'user_name' => $user[0]->name ?? 'Unknown',
            ]);
        }
    }

    return back()->with('success', 'Updated '.count($todoIds).' todos with maximum inefficiency!');
})->name('todos.bulk-update');

// BAD PRACTICE: Route that demonstrates poor data fetching
Route::get('/reports', function () {
    // BAD PRACTICE: Fetch all users then query todos for each
    $users = User::all();

    foreach ($users as $user) {
        // BAD PRACTICE: N+1 query problem
        $user->total_todos = Todo::where('user_id', $user->id)->count();
        $user->completed_todos = Todo::where('user_id', $user->id)->where('completed', true)->count();
        $user->overdue_todos = Todo::where('user_id', $user->id)
            ->where('completed', false)
            ->where('due_date', '<', now())
            ->count();

        // BAD PRACTICE: Even more queries in the loop
        $categories = Todo::where('user_id', $user->id)
            ->distinct('category')
            ->pluck('category');

        foreach ($categories as $category) {
            // BAD PRACTICE: Query for each category for each user
            $user->category_counts[$category] = Todo::where('user_id', $user->id)
                ->where('category', $category)
                ->count();
        }
    }

    return view('reports', compact('users'));
})->name('reports');
