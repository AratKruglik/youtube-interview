<!DOCTYPE html>
<html>
<head>
    <title>Edit Todo</title>
    <!-- BAD PRACTICE: Copy-pasted styles instead of shared stylesheet -->
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .form-container { background: white; padding: 30px; border: 1px solid #ccc; max-width: 600px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input, textarea, select { width: 100%; padding: 8px; border: 1px solid #ccc; }
        .error { color: red; font-size: 12px; margin-top: 3px; }
        .btn { padding: 10px 20px; margin: 5px; border: none; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-secondary { background: #6c757d; color: white; text-decoration: none; display: inline-block; }
        .btn-danger { background: #dc3545; color: white; }
        .required { color: red; }
        .char-counter { font-size: 11px; color: #666; }
        .completion-status { padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        .completed-status { background: #d4edda; border: 1px solid #c3e6cb; }
        .pending-status { background: #fff3cd; border: 1px solid #ffeaa7; }
    </style>
</head>
<body>
    <div class="form-container">
        <!-- BAD PRACTICE: Complex PHP logic in view for title -->
        <h1>Edit Todo: <?= htmlspecialchars($todo->title) ?></h1>

        <!-- BAD PRACTICE: Complex status display logic in view -->
        <div class="completion-status <?= $todo->completed ? 'completed-status' : 'pending-status' ?>">
            <?php if($todo->completed): ?>
                ✅ <strong>Completed Todo</strong> - This todo is marked as complete
            <?php else: ?>
                ⏳ <strong>Pending Todo</strong> - This todo is still pending
                <?php
                if($todo->due_date && strtotime($todo->due_date) < time()) {
                    echo " <span style='color: red;'>⚠️ OVERDUE!</span>";
                }
                ?>
            <?php endif; ?>
        </div>

        @if($errors->any())
            <div style="background: #f8d7da; padding: 15px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                <strong>Please fix these errors:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <?php foreach($errors->all() as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        @endif

        <!-- BAD PRACTICE: Form without CSRF token -->
        <form method="POST" action="{{ route('todos.update', $todo->id) }}" onsubmit="return validateEditForm()">
            <!-- Intentionally omitting @csrf -->
            <input type="hidden" name="_method" value="PUT">

            <div class="form-group">
                <label for="title">Title <span class="required">*</span></label>
                <input type="text" id="title" name="title"
                       value="<?= htmlspecialchars(old('title', $todo->title)) ?>"
                       maxlength="255" onkeyup="updateCharCount('title', 255)">
                <div id="title-count" class="char-counter">0/255 characters</div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"
                         onkeyup="updateCharCount('description', 1000)"><?= htmlspecialchars(old('description', $todo->description ?? '')) ?></textarea>
                <div id="description-count" class="char-counter">0/1000 characters</div>
            </div>

            <div class="form-group">
                <label for="priority">Priority</label>
                <select id="priority" name="priority">
                    <?php
                    $currentPriority = old('priority', $todo->priority);
                    foreach($priorities as $priority):
                    ?>
                        <option value="<?= $priority ?>" <?= $currentPriority == $priority ? 'selected' : '' ?>>
                            <?= ucfirst($priority) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="">No Category</option>
                    <?php
                    $currentCategory = old('category', $todo->category);
                    foreach($categories as $category):
                    ?>
                        <option value="<?= $category ?>" <?= $currentCategory == $category ? 'selected' : '' ?>>
                            <?= $category ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date"
                       value="<?= old('due_date', $todo->due_date) ?>"
                       onchange="checkDueDateEdit()">
                <div id="due-date-warning" style="color: orange; font-size: 12px; margin-top: 3px;"></div>
            </div>

            <!-- BAD PRACTICE: Complex completion toggle -->
            <div class="form-group">
                <label>
                    <input type="checkbox" id="completed" name="completed" value="1"
                           <?= old('completed', $todo->completed) ? 'checked' : '' ?>
                           onchange="toggleCompletionUI()">
                    Mark as completed
                </label>

                <!-- BAD PRACTICE: Dynamic UI changes based on completion -->
                <div id="completion-info" style="margin-top: 10px; padding: 10px; display: none; background: #e2f3ff; border: 1px solid #bee5eb;">
                    <small>
                        <strong>Note:</strong> Marking this as completed will:
                        <ul style="margin: 5px 0 0 20px; font-size: 12px;">
                            <li>Update your completion statistics</li>
                            <li>Send achievement notifications if applicable</li>
                            <li>Archive this todo from active view</li>
                        </ul>
                    </small>
                </div>
            </div>

            <div style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">Update Todo</button>
                <a href="{{ route('todos.index') }}" class="btn btn-secondary">Cancel</a>

                <!-- BAD PRACTICE: Delete button in edit form -->
                <button type="button" onclick="deleteTodo()" class="btn btn-danger">Delete Todo</button>
            </div>
        </form>

        <!-- BAD PRACTICE: Separate delete form without CSRF -->
        <form id="delete-form" method="POST" action="{{ route('todos.destroy', $todo->id) }}" style="display: none;">
            <!-- No CSRF token -->
            <input type="hidden" name="_method" value="DELETE">
        </form>
    </div>

    <!-- BAD PRACTICE: Duplicate JavaScript from create form -->
    <script>
        // BAD PRACTICE: Copied validation logic
        function validateEditForm() {
            let errors = [];
            let title = document.getElementById('title').value.trim();
            let description = document.getElementById('description').value;

            if (!title) {
                errors.push('Title is required');
                document.getElementById('title').style.border = '2px solid red';
            } else if (title.length < 3) {
                errors.push('Title must be at least 3 characters');
                document.getElementById('title').style.border = '2px solid red';
            } else {
                document.getElementById('title').style.border = '1px solid #ccc';
            }

            if (description && description.length > 1000) {
                errors.push('Description is too long');
                document.getElementById('description').style.border = '2px solid red';
            }

            if (errors.length > 0) {
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
                return false;
            }

            return true;
        }

        // BAD PRACTICE: Duplicate character counting function
        function updateCharCount(fieldId, maxLength) {
            let field = document.getElementById(fieldId);
            let counter = document.getElementById(fieldId + '-count');
            let currentLength = field.value.length;

            counter.textContent = currentLength + '/' + maxLength + ' characters';

            if (currentLength > maxLength * 0.9) {
                counter.style.color = 'orange';
            }
            if (currentLength > maxLength) {
                counter.style.color = 'red';
                field.style.border = '2px solid red';
            } else if (currentLength <= maxLength) {
                field.style.border = '1px solid #ccc';
                if (currentLength <= maxLength * 0.9) {
                    counter.style.color = '#666';
                }
            }
        }

        // BAD PRACTICE: Complex UI state management
        function toggleCompletionUI() {
            let checkbox = document.getElementById('completed');
            let infoDiv = document.getElementById('completion-info');

            if (checkbox.checked) {
                infoDiv.style.display = 'block';
                // BAD PRACTICE: Change form styling based on completion
                document.querySelector('.form-container').style.background = '#f8f9fa';
            } else {
                infoDiv.style.display = 'none';
                document.querySelector('.form-container').style.background = 'white';
            }
        }

        // BAD PRACTICE: Due date validation (duplicate code)
        function checkDueDateEdit() {
            let dueDateField = document.getElementById('due_date');
            let warningDiv = document.getElementById('due-date-warning');
            let selectedDate = new Date(dueDateField.value);
            let today = new Date();

            if (dueDateField.value) {
                let daysDiff = Math.ceil((selectedDate - today) / (1000 * 60 * 60 * 24));

                if (daysDiff < 0) {
                    warningDiv.textContent = '⚠️ This date is in the past!';
                    warningDiv.style.color = 'red';
                } else if (daysDiff === 0) {
                    warningDiv.textContent = '⏰ Due today!';
                    warningDiv.style.color = 'orange';
                } else if (daysDiff <= 3) {
                    warningDiv.textContent = `⏰ Due in ${daysDiff} day(s)`;
                    warningDiv.style.color = 'orange';
                } else {
                    warningDiv.textContent = `📅 Due in ${daysDiff} days`;
                    warningDiv.style.color = 'green';
                }
            } else {
                warningDiv.textContent = '';
            }
        }

        // BAD PRACTICE: Delete function that submits hidden form
        function deleteTodo() {
            if (confirm('Are you absolutely sure you want to delete this todo?\n\nThis action cannot be undone!')) {
                if (confirm('This will permanently remove the todo from your list. Continue?')) {
                    document.getElementById('delete-form').submit();
                }
            }
        }

        // BAD PRACTICE: Initialize form state on load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize character counters
            updateCharCount('title', 255);
            updateCharCount('description', 1000);

            // Initialize completion UI
            toggleCompletionUI();

            // Initialize due date warning
            checkDueDateEdit();

            // BAD PRACTICE: Show different messages based on todo status
            <?php if($todo->completed): ?>
                console.log('Editing completed todo');
            <?php else: ?>
                console.log('Editing pending todo');
            <?php endif; ?>
        });

        // BAD PRACTICE: Prevent accidental navigation away
        let formChanged = false;

        document.addEventListener('input', function() {
            formChanged = true;
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });

        // BAD PRACTICE: Mark form as unchanged when submitted
        document.querySelector('form').addEventListener('submit', function() {
            formChanged = false;
        });
    </script>
</body>
</html>
