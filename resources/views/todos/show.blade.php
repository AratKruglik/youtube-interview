<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo Details - Anti-Pattern Demo</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Todo Details</h1>
                <div class="space-x-2">
                    <a href="/todos/{{ $todo->id }}/edit"
                       class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                        Edit
                    </a>
                    <a href="/todos"
                       class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Back to List
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Main Todo Details -->
            <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h2 class="text-2xl font-semibold mb-4">{{ $todo->title }}</h2>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <div class="p-3 bg-gray-50 rounded-md">
                                {{ $todo->description ?? 'No description provided' }}
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    @if($todo->priority == 'urgent') bg-red-100 text-red-800
                                    @elseif($todo->priority == 'high') bg-yellow-100 text-yellow-800
                                    @elseif($todo->priority == 'medium') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($todo->priority) }}
                                </span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                @if($todo->completed)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        ✓ Completed
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                        ⏳ Pending
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div>
                        <!-- Data from N+1 queries in controller -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
                            <div class="p-3 bg-gray-50 rounded-md">
                                <div class="font-semibold">{{ $user->name }}</div>
                                <div class="text-sm text-gray-600">{{ $user->email }}</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <div class="p-3 bg-gray-50 rounded-md">
                                @if($category)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                          style="background-color: {{ $category->color }}20; color: {{ $category->color }}">
                                        {{ $category->name }}
                                    </span>
                                    <div class="text-sm text-gray-600 mt-1">{{ $category->description }}</div>
                                @else
                                    <span class="text-gray-400">No Category</span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Created</label>
                                <div class="text-sm text-gray-600">{{ $todo->created_at->format('M j, Y g:i A') }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Updated</label>
                                <div class="text-sm text-gray-600">{{ $todo->updated_at->format('M j, Y g:i A') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics from Fat Controller Logic -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="text-2xl font-bold text-blue-600">{{ $statistics['user_total_todos'] }}</div>
                    <div class="text-sm text-gray-600">User's Total Todos</div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="text-2xl font-bold text-green-600">{{ $statistics['user_completed_todos'] }}</div>
                    <div class="text-sm text-gray-600">User's Completed</div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="text-2xl font-bold text-purple-600">{{ $statistics['category_total_todos'] }}</div>
                    <div class="text-sm text-gray-600">Category Total</div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="text-2xl font-bold text-orange-600">
                        {{ $statistics['user_total_todos'] > 0 ? round(($statistics['user_completed_todos'] / $statistics['user_total_todos']) * 100) : 0 }}%
                    </div>
                    <div class="text-sm text-gray-600">Completion Rate</div>
                </div>
            </div>

            <!-- Similar Todos (from additional N+1 queries) -->
            @if($statistics['similar_todos']->count() > 0)
            <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Similar Todos (Same Category & Priority)</h3>
                <div class="space-y-3">
                    @foreach($statistics['similar_todos'] as $similarTodo)
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-md">
                            <div>
                                <div class="font-medium">{{ $similarTodo->title }}</div>
                                <div class="text-sm text-gray-600">{{ Str::limit($similarTodo->description, 60) }}</div>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($similarTodo->completed)
                                    <span class="text-green-600 text-sm">✓</span>
                                @else
                                    <span class="text-gray-400 text-sm">⏳</span>
                                @endif
                                <a href="/todos/{{ $similarTodo->id }}"
                                   class="text-blue-600 hover:text-blue-800 text-sm">View</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Quick Actions with CSRF Vulnerabilities -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                <div class="flex space-x-4">

                    <!-- ANTI-PATTERN #4: Form without CSRF protection -->
                    @if(!$todo->completed)
                        <form method="POST" action="/todos/{{ $todo->id }}" class="inline">
                            @method('PATCH')
                            <!-- Missing @csrf token -->
                            <input type="hidden" name="completed" value="1">
                            <input type="hidden" name="title" value="{{ $todo->title }}">
                            <input type="hidden" name="description" value="{{ $todo->description }}">
                            <input type="hidden" name="priority" value="{{ $todo->priority }}">
                            <input type="hidden" name="category_id" value="{{ $todo->category_id }}">
                            <input type="hidden" name="user_id" value="{{ $todo->user_id }}">
                            <button type="submit"
                                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Mark Completed
                            </button>
                        </form>
                    @else
                        <form method="POST" action="/todos/{{ $todo->id }}" class="inline">
                            @method('PATCH')
                            <!-- Missing @csrf token -->
                            <input type="hidden" name="completed" value="0">
                            <input type="hidden" name="title" value="{{ $todo->title }}">
                            <input type="hidden" name="description" value="{{ $todo->description }}">
                            <input type="hidden" name="priority" value="{{ $todo->priority }}">
                            <input type="hidden" name="category_id" value="{{ $todo->category_id }}">
                            <input type="hidden" name="user_id" value="{{ $todo->user_id }}">
                            <button type="submit"
                                    class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                                Mark Pending
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="/todos/{{ $todo->id }}" class="inline">
                        @method('DELETE')
                        <!-- Missing @csrf token -->
                        <button type="submit"
                                class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                                onclick="return confirm('Are you sure you want to delete this todo?')">
                            Delete Todo
                        </button>
                    </form>
                </div>
            </div>

            <div class="mt-6 text-sm text-gray-600">
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <strong>Anti-patterns demonstrated on this page:</strong><br>
                                • <strong>N+1 Problem:</strong> User and category data loaded with separate queries<br>
                                • <strong>Fat Controller:</strong> Statistics calculated in controller with multiple queries<br>
                                • <strong>No CSRF Protection:</strong> Quick action forms missing @csrf tokens<br>
                                • <strong>Similar Todos:</strong> Loaded with additional inefficient queries
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
