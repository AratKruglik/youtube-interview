<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Todo</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Edit Todo</h1>
                <div class="space-x-2">
                    <a href="/todos/{{ $todo->id }}"
                       class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        View
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

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow-md rounded-lg p-6">
                <form method="POST" action="/todos/{{ $todo->id }}">
                    @method('PATCH')

                    <div class="mb-4">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                        <input type="text"
                               id="title"
                               name="title"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Enter todo title..."
                               value="{{ old('title', $todo->title) }}"
                               required>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="description"
                                  name="description"
                                  rows="4"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Enter todo description...">{{ old('description', $todo->description) }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                        <select id="priority"
                                name="priority"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="low" {{ old('priority', $todo->priority) == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ old('priority', $todo->priority) == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ old('priority', $todo->priority) == 'high' ? 'selected' : '' }}>High</option>
                            <option value="urgent" {{ old('priority', $todo->priority) == 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select id="category_id"
                                name="category_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">No Category</option>
                            <!-- Data from controller's N+1 queries in loops -->
                            @foreach($categoryOptions as $option)
                                <option value="{{ $option['id'] }}"
                                        {{ old('category_id', $todo->category_id) == $option['id'] ? 'selected' : '' }}>
                                    {{ $option['name'] }} ({{ $option['todo_count'] }} todos)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Hidden field for user_id - Should be handled properly with auth -->
                    <input type="hidden" name="user_id" value="{{ $todo->user_id }}">

                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox"
                                   name="completed"
                                   value="1"
                                   class="mr-2"
                                   {{ old('completed', $todo->completed) ? 'checked' : '' }}>
                            <span class="text-sm text-gray-700">Mark as completed</span>
                        </label>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <a href="/todos/{{ $todo->id }}"
                           class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            Cancel
                        </a>
                        <button type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Update Todo
                        </button>
                    </div>
                </form>
            </div>

            <!-- Current Todo Information -->
            <div class="bg-white shadow-md rounded-lg p-6 mt-6">
                <h3 class="text-lg font-semibold mb-4">Current Todo Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

            <!-- Quick Actions without CSRF -->
            <div class="bg-white shadow-md rounded-lg p-6 mt-6">
                <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                <div class="flex space-x-4">

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
                                Quick Complete
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="/todos/{{ $todo->id }}" class="inline">
                        @method('PATCH')
                        <!-- Missing @csrf token -->
                        <input type="hidden" name="priority" value="urgent">
                        <input type="hidden" name="title" value="{{ $todo->title }}">
                        <input type="hidden" name="description" value="{{ $todo->description }}">
                        <input type="hidden" name="completed" value="{{ $todo->completed ? '1' : '0' }}">
                        <input type="hidden" name="category_id" value="{{ $todo->category_id }}">
                        <input type="hidden" name="user_id" value="{{ $todo->user_id }}">
                        <button type="submit"
                                class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                            Mark Urgent
                        </button>
                    </form>

                    <form method="POST" action="/todos/{{ $todo->id }}" class="inline">
                        @method('DELETE')
                        <!-- Missing @csrf token -->
                        <button type="submit"
                                class="bg-gray-600 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded"
                                onclick="return confirm('Are you sure you want to delete this todo?')">
                            Delete
                        </button>
                    </form>
                </div>
            </div>

            <!-- Bulk Edit Form (Another CSRF vulnerability) -->
            <div class="bg-white shadow-md rounded-lg p-6 mt-6">
                <h3 class="text-lg font-semibold mb-4">Bulk Priority Update</h3>
                <p class="text-sm text-gray-600 mb-4">Update priority for all todos in the same category</p>

                <form method="POST" action="#" class="flex space-x-2">
                    <!-- Missing @csrf token -->
                    <input type="hidden" name="category_id" value="{{ $todo->category_id }}">
                    <select name="new_priority" class="flex-1 px-3 py-2 border border-gray-300 rounded-md">
                        <option value="low">Low Priority</option>
                        <option value="medium">Medium Priority</option>
                        <option value="high">High Priority</option>
                        <option value="urgent">Urgent Priority</option>
                    </select>
                    <button type="submit"
                            class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                        Bulk Update
                    </button>
                </form>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    console.log('Submitting form without CSRF protection');
                });
            });

            const prioritySelect = document.getElementById('priority');
            prioritySelect.addEventListener('change', function() {
                if (this.value === 'urgent') {
                    document.querySelector('form').style.border = '2px solid red';
                }
            });
        });
    </script>
</body>
</html>
