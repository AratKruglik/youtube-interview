<!DOCTYPE html>
<html>
<head>
    <title>Todo List</title>
    <!-- BAD PRACTICE: Inline styles instead of proper CSS -->
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .todo { border: 1px solid #ccc; padding: 10px; margin: 5px; background: white; }
        .completed { background: #d4edda !important; }
        .overdue { background: #f8d7da !important; }
        .stats { background: #e2e3e5; padding: 15px; margin-bottom: 20px; }
        form { display: inline; }
        button { margin: 2px; padding: 5px 10px; }
        .high { color: red; }
        .medium { color: orange; }
        .low { color: green; }
    </style>
</head>
<body>
    <!-- BAD PRACTICE: Mixed PHP logic directly in template -->
    <h1>My Todos (<?php echo count($todos); ?>)</h1>

    <!-- BAD PRACTICE: Complex logic in view -->
    <div class="stats">
        <h3>Statistics</h3>
        <p>Total: <?= $stats['total'] ?></p>
        <p>Completed: <?= $stats['completed'] ?></p>
        <p>Pending: <?= $stats['pending'] ?></p>
        <p>Overdue: <?= $stats['overdue'] ?></p>

        <!-- BAD PRACTICE: Calculations in view -->
        <?php
        $completionRate = $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100, 1) : 0;
        echo "<p>Completion Rate: {$completionRate}%</p>";
        ?>
    </div>

    <!-- BAD PRACTICE: No CSRF token on forms -->
    <div style="margin-bottom: 20px;">
        <a href="{{ route('todos.create') }}" style="background: #007bff; color: white; padding: 10px 15px; text-decoration: none;">Add New Todo</a>

        <!-- BAD PRACTICE: Bulk update form without CSRF -->
        <form method="POST" action="{{ route('todos.bulk-update') }}" style="display: inline; margin-left: 10px;">
            <button type="submit" onclick="return confirm('Mark all visible as completed?')"
                    style="background: #28a745; color: white; border: none; padding: 10px 15px;">
                Bulk Complete All
            </button>
            <!-- BAD PRACTICE: Hidden fields populated by JavaScript -->
            <input type="hidden" name="todo_ids" id="bulk-ids">
        </form>
    </div>

    @if(session('success'))
        <div style="background: #d4edda; padding: 10px; margin-bottom: 15px; border: 1px solid #c3e6cb;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background: #f8d7da; padding: 10px; margin-bottom: 15px; border: 1px solid #f5c6cb;">
            {{ session('error') }}
        </div>
    @endif

    <!-- BAD PRACTICE: Complex logic and unsafe data rendering -->
    <?php foreach($todos as $todo): ?>
        <div class="todo <?php
            echo $todo->completed ? 'completed' : '';
            // BAD PRACTICE: Complex logic in view
            if (!$todo->completed && $todo->due_date && strtotime($todo->due_date) < time()) {
                echo ' overdue';
            }
        ?>">
            <!-- BAD PRACTICE: Unescaped output (XSS vulnerability) -->
            <h4><?= $todo->title ?></h4>

            <!-- BAD PRACTICE: More complex view logic -->
            <?php if($todo->description): ?>
                <p><?= $todo->description ?></p>
            <?php endif; ?>

            <div style="font-size: 12px; color: #666;">
                <span class="<?= $todo->priority ?>"><?= ucfirst($todo->priority) ?> Priority</span>

                <?php if($todo->category): ?>
                    | Category: <?= $todo->category ?>
                <?php endif; ?>

                <?php if($todo->due_date): ?>
                    | Due: <?= date('M j, Y', strtotime($todo->due_date)) ?>
                    <!-- BAD PRACTICE: Date calculations in view -->
                    <?php
                    $daysUntilDue = ceil((strtotime($todo->due_date) - time()) / 86400);
                    if($daysUntilDue < 0 && !$todo->completed) {
                        echo " <strong style='color: red;'>(" . abs($daysUntilDue) . " days overdue!)</strong>";
                    } elseif($daysUntilDue >= 0 && $daysUntilDue <= 3 && !$todo->completed) {
                        echo " <strong style='color: orange;'>(" . $daysUntilDue . " days left)</strong>";
                    }
                    ?>
                <?php endif; ?>

                <!-- BAD PRACTICE: Database queries results displayed directly -->
                <br>User: <?= $todo->user_name ?> (<?= $todo->user_completed_count ?> total completed)
            </div>

            <div style="margin-top: 10px;">
                <a href="{{ route('todos.show', $todo->id) }}" style="color: #007bff;">View</a>
                <a href="{{ route('todos.edit', $todo->id) }}" style="color: #ffc107; margin-left: 10px;">Edit</a>

                <!-- BAD PRACTICE: Delete form without CSRF token -->
                <form method="POST" action="{{ route('todos.destroy', $todo->id) }}" style="display: inline; margin-left: 10px;">
                    <!-- Intentionally omitting @csrf and @method('DELETE') -->
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" onclick="return confirm('Are you sure?')"
                            style="background: #dc3545; color: white; border: none; padding: 5px 10px;">
                        Delete
                    </button>
                </form>

                <!-- BAD PRACTICE: Toggle completion without CSRF -->
                <?php if(!$todo->completed): ?>
                    <form method="POST" action="{{ route('todos.update', $todo->id) }}" style="display: inline; margin-left: 10px;">
                        <!-- No CSRF token -->
                        <input type="hidden" name="_method" value="PUT">
                        <input type="hidden" name="title" value="<?= htmlspecialchars($todo->title) ?>">
                        <input type="hidden" name="description" value="<?= htmlspecialchars($todo->description ?? '') ?>">
                        <input type="hidden" name="priority" value="<?= $todo->priority ?>">
                        <input type="hidden" name="due_date" value="<?= $todo->due_date ?>">
                        <input type="hidden" name="category" value="<?= $todo->category ?>">
                        <input type="hidden" name="completed" value="1">
                        <button type="submit" style="background: #28a745; color: white; border: none; padding: 5px 10px;">
                            Mark Complete
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if(empty($todos)): ?>
        <div style="text-align: center; margin-top: 50px; color: #666;">
            <h3>No todos found!</h3>
            <p>Why not <a href="{{ route('todos.create') }}">create your first todo</a>?</p>
        </div>
    <?php endif; ?>

    <!-- BAD PRACTICE: JavaScript mixed with HTML -->
    <script>
        // Populate bulk update form with all todo IDs
        document.addEventListener('DOMContentLoaded', function() {
            const todoIds = [<?php echo implode(',', array_map(function($todo) { return $todo->id; }, $todos)); ?>];
            document.getElementById('bulk-ids').value = todoIds.join(',');
        });

        // BAD PRACTICE: Inline event handlers and no validation
        function toggleTodo(todoId, completed) {
            // This would be a terrible way to handle AJAX requests
            fetch(`/todos/${todoId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    // BAD PRACTICE: No CSRF token in AJAX requests
                },
                body: JSON.stringify({completed: !completed})
            }).then(response => {
                if(response.ok) {
                    location.reload(); // BAD PRACTICE: Full page reload
                }
            });
        }
    </script>
</body>
</html>
