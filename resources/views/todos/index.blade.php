<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo List - Anti-Pattern Demo</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Todo List</h1>
            <a href="/todos/create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add New Todo
            </a>
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

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion Rate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($processedTodos as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $item['todo']['title'] }}</div>
                                <div class="text-sm text-gray-500">{{ Str::limit($item['todo']['description'], 50) }}</div>
                                @if($item['is_overdue'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Overdue
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $item['user']['name'] ?? 'Unknown' }}</div>
                                <div class="text-sm text-gray-500">{{ $item['user']['email'] ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($item['category'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          style="background-color: {{ $item['category']['color'] }}20; color: {{ $item['category']['color'] }}">
                                        {{ $item['category']['name'] }}
                                    </span>
                                @else
                                    <span class="text-gray-400">No Category</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($item['todo']['priority'] == 'urgent') bg-red-100 text-red-800
                                    @elseif($item['todo']['priority'] == 'high') bg-yellow-100 text-yellow-800
                                    @elseif($item['todo']['priority'] == 'medium') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($item['todo']['priority']) }}
                                </span>
                                <div class="text-xs text-gray-500">Score: {{ $item['priority_score'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($item['todo']['completed'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Completed
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Pending
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $item['completion_rate'] }}%
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="/todos/{{ $item['todo']['id'] }}" class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                                <a href="/todos/{{ $item['todo']['id'] }}/edit" class="text-yellow-600 hover:text-yellow-900 mr-3">Edit</a>
                                <form method="POST" action="/todos/{{ $item['todo']['id'] }}" class="inline">
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Are you sure you want to delete this todo?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>

                        @if($item['related_todos']->count() > 0)
                            <tr class="bg-gray-50">
                                <td colspan="7" class="px-6 py-2">
                                    <div class="text-xs text-gray-600">
                                        <strong>Related todos:</strong>
                                        @foreach($item['related_todos'] as $related)
                                            <span class="mr-2">{{ $related->title }}</span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                No todos found. <a href="/todos/create" class="text-blue-500 hover:text-blue-700">Create one now!</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
