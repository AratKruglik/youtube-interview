<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Todo - Anti-Pattern Demo</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Create New Todo</h1>
                <a href="/todos" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to List
                </a>
            </div>

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <!-- ANTI-PATTERN #4: Form without CSRF protection -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <form method="POST" action="/todos">
                    <!-- Missing @csrf token - MAJOR SECURITY VULNERABILITY -->

                    <div class="mb-4">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                        <input type="text"
                               id="title"
                               name="title"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Enter todo title..."
                               value="{{ old('title') }}"
                               required>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="description"
                                  name="description"
                                  rows="4"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Enter todo description...">{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                        <select id="priority"
                                name="priority"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                            <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select id="category_id"
                                name="category_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Category (Optional)</option>
                            <!-- Data from controller's N+1 queries -->
                            @foreach($processedCategories as $item)
                                <option value="{{ $item['category']->id }}"
                                        {{ old('category_id') == $item['category']->id ? 'selected' : '' }}>
                                    {{ $item['category']->name }} ({{ $item['todo_count'] }} todos)
                                </option>
                            @endforeach
                        </select>
                        <p class="text-sm text-gray-500 mt-1">If no category is selected, it will be auto-assigned to "General"</p>
                    </div>

                    <!-- Hidden field for user_id - In a real app, this would come from auth -->
                    <input type="hidden" name="user_id" value="1">

                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox"
                                   name="completed"
                                   value="1"
                                   class="mr-2"
                                   {{ old('completed') ? 'checked' : '' }}>
                            <span class="text-sm text-gray-700">Mark as completed</span>
                        </label>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <a href="/todos"
                           class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            Cancel
                        </a>
                        <button type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Create Todo
                        </button>
                    </div>
                </form>
            </div>

            <!-- ANTI-PATTERN #4: Another form without CSRF protection for quick category creation -->
            <div class="bg-white shadow-md rounded-lg p-6 mt-6">
                <h3 class="text-lg font-semibold mb-4">Quick Create Category</h3>
                <form method="POST" action="#" class="flex space-x-2">
                    <!-- Missing @csrf token here too -->
                    <input type="text"
                           name="category_name"
                           placeholder="Category name..."
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md">
                    <input type="color"
                           name="category_color"
                           value="#6366f1"
                           class="w-12 h-10 border border-gray-300 rounded-md">
                    <button type="submit"
                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Create
                    </button>
                </form>
            </div>

            <div class="mt-6 text-sm text-gray-600">
                <div class="bg-red-50 border-l-4 border-red-400 p-4">
                    <div class="flex">
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <strong>⚠️ SECURITY WARNING - Anti-pattern demonstrated:</strong><br>
                                • <strong>No CSRF Protection:</strong> Both forms are missing @csrf tokens<br>
                                • <strong>Mass Assignment:</strong> Form data is passed directly to create() method<br>
                                • <strong>N+1 Queries:</strong> Category dropdown loaded with inefficient queries<br>
                                • <strong>Hardcoded User ID:</strong> No proper authentication check
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ANTI-PATTERN: Inline JavaScript in Blade templates
        // This should be in separate JS files
        document.addEventListener('DOMContentLoaded', function() {
            // Unsafe DOM manipulation without CSRF consideration
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const title = document.getElementById('title').value;
                if (title.length < 3) {
                    alert('Title must be at least 3 characters long');
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
