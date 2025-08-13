<!DOCTYPE html>
<html>
<head>
    <title>View Todo</title>
    <!-- BAD PRACTICE: More duplicate inline styles -->
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .todo-container { background: white; padding: 30px; border: 1px solid #ccc; max-width: 800px; }
        .todo-header { border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        .todo-meta { font-size: 12px; color: #666; margin: 10px 0; }
        .btn { padding: 8px 15px; margin: 3px; border: none; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #007bff; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .completed { background: #d4edda; }
        .pending { background: #fff3cd; }
        .overdue { background: #f8d7da; }
        .related-todos { margin-top: 30px; }
        .related-todo { border: 1px solid #ddd; padding: 10px; margin: 5px 0; background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="todo-container">
        <!-- BAD PRACTICE: Complex status logic in view -->
        <div class="todo-header <?php
            if($todo->completed) {
                echo 'completed';
            } elseif($todo->due_date && strtotime($todo->due_date) < time()) {
                echo 'overdue';
            } else {
                echo 'pending';
            }
        ?>">
            <!-- BAD PRACTICE: Unescaped output -->
            <h1><?= $todo->title ?></h1>

            <div class="todo-meta">
                <strong>Status:</strong>
                <?php if($todo->completed): ?>
                    ✅ Completed
                <?php else: ?>
                    ⏳ Pending
                    <?php if($todo->due_date && strtotime($todo->due_date) < time()): ?>
                        <span style="color: red; font-weight: bold;"> - OVERDUE!</span>
                    <?php endif; ?>
                <?php endif; ?>

                <br><strong>Priority:</strong>
                <span style="color: <?php
                    echo $todo->priority == 'high' ? 'red' : ($todo->priority == 'medium' ? 'orange' : 'green');
                ?>"><?= ucfirst($todo->priority) ?></span>

                <?php if($todo->category): ?>
                    <br><strong>Category:</strong> <?= $todo->category ?>
                <?php endif; ?>

                <?php if($todo->due_date): ?>
                    <br><strong>Due Date:</strong> <?= date('F j, Y', strtotime($todo->due_date)) ?>
                    <?php
                    $daysUntilDue = ceil((strtotime($todo->due_date) - time()) / 86400);
                    if($daysUntilDue < 0 && !$todo->completed) {
                        echo " <span style='color: red;'>(" . abs($daysUntilDue) . " days overdue)</span>";
                    } elseif($daysUntilDue >= 0 && $daysUntilDue <= 7 && !$todo->completed) {
                        echo " <span style='color: orange;'>(" . $daysUntilDue . " days left)</span>";
                    }
                    ?>
                <?php endif; ?>

                <br><strong>Owner:</strong> <?= $todo->user->name ?>
                <br><strong>Created:</strong> <?= date('M j, Y \a\t g:i A', strtotime($todo->created_at)) ?>
                <br><strong>Last Updated:</strong> <?= date('M j, Y \a\t g:i A', strtotime($todo->updated_at)) ?>
            </div>
        </div>

        <?php if($todo->description): ?>
            <div style="margin: 20px 0;">
                <h3>Description</h3>
                <!-- BAD PRACTICE: Unescaped description output -->
                <p style="white-space: pre-line;"><?= $todo->description ?></p>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px;">
            <a href="{{ route('todos.edit', $todo->id) }}" class="btn btn-primary">Edit Todo</a>
            <a href="{{ route('todos.index') }}" class="btn btn-secondary">Back to List</a>

            <?php if(!$todo->completed): ?>
                <!-- BAD PRACTICE: Mark complete form without CSRF -->
                <form method="POST" action="{{ route('todos.update', $todo->id) }}" style="display: inline;">
                    <!-- No CSRF token -->
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="title" value="<?= htmlspecialchars($todo->title) ?>">
                    <input type="hidden" name="description" value="<?= htmlspecialchars($todo->description ?? '') ?>">
                    <input type="hidden" name="priority" value="<?= $todo->priority ?>">
                    <input type="hidden" name="due_date" value="<?= $todo->due_date ?>">
                    <input type="hidden" name="category" value="<?= $todo->category ?>">
                    <input type="hidden" name="completed" value="1">
                    <button type="submit" class="btn btn-success">Mark as Complete</button>
                </form>
            <?php endif; ?>

            <!-- BAD PRACTICE: Delete form without CSRF -->
            <form method="POST" action="{{ route('todos.destroy', $todo->id) }}" style="display: inline;">
                <!-- No CSRF token -->
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this todo?')">Delete</button>
            </form>
        </div>

        <!-- BAD PRACTICE: Related todos section with complex logic -->
        <?php if(!empty($relatedTodos)): ?>
            <div class="related-todos">
                <h3>Related Todos in "<?= $todo->category ?>" Category</h3>
                <?php foreach($relatedTodos as $related): ?>
                    <div class="related-todo">
                        <strong><?= $related->title ?></strong>
                        <span style="float: right;">
                            <span style="color: <?= $related->priority == 'high' ? 'red' : ($related->priority == 'medium' ? 'orange' : 'green') ?>">
                                <?= ucfirst($related->priority) ?>
                            </span>
                            <?= $related->completed ? '✅' : '⏳' ?>
                        </span>
                        <br>
                        <small>
                            <?php if($related->due_date): ?>
                                Due: <?= date('M j', strtotime($related->due_date)) ?>
                            <?php endif; ?>
                        </small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- BAD PRACTICE: Inline JavaScript for page interactions -->
    <script>
        // BAD PRACTICE: Console logging private data
        console.log('Viewing todo:', {
            id: <?= $todo->id ?>,
            title: '<?= addslashes($todo->title) ?>',
            completed: <?= $todo->completed ? 'true' : 'false' ?>,
            user_id: <?= $todo->user_id ?>
        });

        // BAD PRACTICE: Automatic actions without user consent
        setTimeout(function() {
            // Mark as viewed in local storage
            localStorage.setItem('last_viewed_todo', <?= $todo->id ?>);
        }, 1000);
    </script>
</body>
</html>
