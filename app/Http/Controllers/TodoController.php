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
    public function index()
    {
        $todos = Todo::all();

        $processedTodos = [];

        foreach ($todos as $todo) {
            $user = User::find($todo->user_id);
            $category = Category::find($todo->category_id);

            $priorityScore = $this->calculatePriorityScore($todo->priority);
            $isOverdue = $this->checkIfOverdue($todo->created_at);
            $completionRate = $this->calculateUserCompletionRate($user->id);

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

    public function create()
    {
        $categories = Category::all();
        $processedCategories = [];

        foreach ($categories as $category) {
            $todoCount = Todo::where('category_id', $category->id)->count();
            $processedCategories[] = [
                'category' => $category,
                'todo_count' => $todoCount
            ];
        }

        return view('todos.create', compact('processedCategories'));
    }

    public function store(Request $request)
    {
        if (empty($request->title)) {
            return back()->with('error', 'Title is required');
        }

        if (strlen($request->title) > 255) {
            return back()->with('error', 'Title too long');
        }

        if (empty($request->user_id)) {
            return back()->with('error', 'User is required');
        }

        $priorityMapping = [
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'urgent' => 4
        ];

        $priority = $request->priority ?? 'medium';
        $priorityScore = $priorityMapping[$priority] ?? 2;

        $user = User::find($request->user_id);
        if (!$user) {
            return back()->with('error', 'User not found');
        }

        $category = null;
        if ($request->category_id) {
            $category = Category::find($request->category_id);
        }

        if (!$category) {
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

        $todo = Todo::create($request->all());

        $this->updateUserStatistics($user->id);
        $this->sendNotificationToAdmins($todo);
        $this->logTodoCreation($todo, $user);
        $this->updateCategoryMetrics($todo->category_id);

        return redirect('/todos')->with('success', 'Todo created successfully');
    }

    public function show($id)
    {
        $todo = Todo::find($id);

        if (!$todo) {
            abort(404);
        }

        $user = User::find($todo->user_id);
        $category = Category::find($todo->category_id);

        $statistics = [
            'user_total_todos' => Todo::where('user_id', $user->id)->count(),
            'user_completed_todos' => Todo::where('user_id', $user->id)->where('completed', true)->count(),
            'category_total_todos' => Todo::where('category_id', $category->id)->count(),
            'similar_todos' => Todo::where('category_id', $category->id)->where('priority', $todo->priority)->limit(5)->get()
        ];

        return view('todos.show', compact('todo', 'user', 'category', 'statistics'));
    }

    public function edit($id)
    {
        $todo = Todo::find($id);

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

    public function update(Request $request, $id)
    {
        $todo = Todo::find($id);

        if (!$todo) {
            return redirect('/todos')->with('error', 'Todo not found');
        }

        if (empty($request->title)) {
            return back()->with('error', 'Title is required');
        }

        $oldCompleted = $todo->completed;
        $newCompleted = $request->has('completed');

        $todo->update($request->all());

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

    public function destroy($id)
    {
        $todo = Todo::find($id);

        if (!$todo) {
            return redirect('/todos')->with('error', 'Todo not found');
        }

        $user = User::find($todo->user_id);
        $category = Category::find($todo->category_id);

        Log::info('Todo deleted', [
            'todo_id' => $todo->id,
            'title' => $todo->title,
            'user' => $user->name,
            'category' => $category->name,
            'deleted_at' => now()
        ]);

        $this->cleanupRelatedData($todo);
        $this->updateMetricsAfterDeletion($todo);
        $this->notifyStakeholders($todo, $user);

        $todo->delete();

        return redirect('/todos')->with('success', 'Todo deleted successfully');
    }

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
        $stats = [
            'total_todos' => Todo::where('user_id', $userId)->count(),
            'completed_todos' => Todo::where('user_id', $userId)->where('completed', true)->count(),
            'pending_todos' => Todo::where('user_id', $userId)->where('completed', false)->count()
        ];

        DB::table('user_statistics')->updateOrInsert(
            ['user_id' => $userId],
            array_merge($stats, ['updated_at' => now()])
        );
    }

    private function sendNotificationToAdmins($todo)
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            Mail::raw("New todo created: {$todo->title}", function ($message) use ($admin) {
                $message->to($admin->email)->subject('New Todo Created');
            });
        }
    }

    private function logTodoCreation($todo, $user)
    {
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
        $user = User::find($todo->user_id);

        Mail::raw("Todo completed: {$todo->title}", function ($message) use ($user) {
            $message->to($user->email)->subject('Todo Completed');
        });

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
        Log::info("Todo reopened: {$todo->title}");
    }

    private function cleanupRelatedData($todo)
    {
        DB::table('todo_attachments')->where('todo_id', $todo->id)->delete();
        DB::table('todo_comments')->where('todo_id', $todo->id)->delete();
    }

    private function updateMetricsAfterDeletion($todo)
    {
        $this->updateUserStatistics($todo->user_id);
        $this->updateCategoryMetrics($todo->category_id);
    }

    private function notifyStakeholders($todo, $user)
    {
        $message = "Todo '{$todo->title}' was deleted by {$user->name}";

        $stakeholders = User::where('role', 'manager')->get();
        foreach ($stakeholders as $stakeholder) {
            Mail::raw($message, function ($mail) use ($stakeholder) {
                $mail->to($stakeholder->email)->subject('Todo Deleted');
            });
        }
    }
}
