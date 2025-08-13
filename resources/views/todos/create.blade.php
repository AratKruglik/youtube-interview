<!DOCTYPE html>
<html>
<head>
    <title>Create New Todo</title>
    <!-- BAD PRACTICE: Duplicate inline styles instead of shared CSS -->
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
        .required { color: red; }
        .char-counter { font-size: 11px; color: #666; }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Create New Todo</h1>

        <!-- BAD PRACTICE: Form validation errors displayed with complex logic -->
        @if($errors->any())
            <div style="background: #f8d7da; padding: 15px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                <strong>Oops! Something went wrong:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <?php foreach($errors->all() as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        @endif

        <!-- BAD PRACTICE: Form without CSRF protection -->
        <form method="POST" action="{{ route('todos.store') }}" onsubmit="return validateForm()">
            <!-- Intentionally omitting @csrf -->

            <div class="form-group">
                <label for="title">Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title') }}"
                       maxlength="255" onkeyup="updateCharCount('title', 255)">
                <div id="title-count" class="char-counter">0/255 characters</div>
                @error('title')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"
                         onkeyup="updateCharCount('description', 1000)">{{ old('description') }}</textarea>
                <div id="description-count" class="char-counter">0/1000 characters</div>
                <!-- BAD PRACTICE: No server-side limit but client-side limit -->
            </div>

            <div class="form-group">
                <label for="priority">Priority</label>
                <select id="priority" name="priority">
                    <option value="">Select Priority</option>
                    <?php foreach($priorities as $priority): ?>
                        <option value="<?= $priority ?>" <?= old('priority') == $priority ? 'selected' : '' ?>>
                            <?= ucfirst($priority) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="">Select Category</option>
                    <?php foreach($categories as $category): ?>
                        <option value="<?= $category ?>" <?= old('category') == $category ? 'selected' : '' ?>>
                            <?= $category ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- BAD PRACTICE: Inline JavaScript for dynamic options -->
                <div style="margin-top: 5px;">
                    <input type="text" id="custom-category" placeholder="Or type a custom category..."
                           onchange="addCustomCategory()" style="font-size: 12px;">
                </div>
            </div>

            <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date" value="{{ old('due_date') }}"
                       onchange="checkDueDate()">
                <div id="due-date-warning" style="color: orange; font-size: 12px; margin-top: 3px;"></div>
            </div>

            <!-- BAD PRACTICE: Hidden field with user data -->
            <input type="hidden" name="user_id" value="<?= auth()->id() ?>">

            <div style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">Create Todo</button>
                <a href="{{ route('todos.index') }}" class="btn btn-secondary">Cancel</a>

                <!-- BAD PRACTICE: Reset button that clears everything without confirmation -->
                <button type="button" onclick="clearForm()" class="btn"
                        style="background: #dc3545; color: white;">Clear All</button>
            </div>
        </form>
    </div>

    <!-- BAD PRACTICE: Massive inline JavaScript -->
    <script>
        // BAD PRACTICE: No proper validation library
        function validateForm() {
            let errors = [];
            let title = document.getElementById('title').value.trim();
            let description = document.getElementById('description').value;
            let dueDate = document.getElementById('due_date').value;

            // BAD PRACTICE: Client-side validation only
            if (!title) {
                errors.push('Title is required');
                document.getElementById('title').style.border = '2px solid red';
            } else if (title.length < 3) {
                errors.push('Title must be at least 3 characters');
                document.getElementById('title').style.border = '2px solid red';
            } else {
                document.getElementById('title').style.border = '1px solid #ccc';
            }

            // BAD PRACTICE: Inconsistent validation
            if (description && description.length > 1000) {
                errors.push('Description is too long');
                document.getElementById('description').style.border = '2px solid red';
            }

            // BAD PRACTICE: Complex date validation in JavaScript
            if (dueDate) {
                let today = new Date();
                let selectedDate = new Date(dueDate);
                let daysDiff = (selectedDate - today) / (1000 * 60 * 60 * 24);

                if (daysDiff < 0) {
                    errors.push('Due date cannot be in the past');
                    document.getElementById('due_date').style.border = '2px solid red';
                } else if (daysDiff > 365) {
                    errors.push('Due date cannot be more than a year from now');
                    document.getElementById('due_date').style.border = '2px solid red';
                } else {
                    document.getElementById('due_date').style.border = '1px solid #ccc';
                }
            }

            if (errors.length > 0) {
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
                return false;
            }

            return true;
        }

        // BAD PRACTICE: Character counting in real-time
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

        // BAD PRACTICE: Dynamic form modification
        function addCustomCategory() {
            let customInput = document.getElementById('custom-category');
            let categorySelect = document.getElementById('category');
            let customValue = customInput.value.trim();

            if (customValue && customValue.length > 0) {
                // Check if option already exists
                let exists = false;
                for (let i = 0; i < categorySelect.options.length; i++) {
                    if (categorySelect.options[i].value === customValue) {
                        exists = true;
                        break;
                    }
                }

                if (!exists) {
                    let option = new Option(customValue, customValue, true, true);
                    categorySelect.add(option);
                }

                categorySelect.value = customValue;
                customInput.value = '';
            }
        }

        // BAD PRACTICE: Due date warnings
        function checkDueDate() {
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

        // BAD PRACTICE: Clear form without confirmation in some cases
        function clearForm() {
            if (confirm('Are you sure you want to clear all fields? This cannot be undone!')) {
                document.getElementById('title').value = '';
                document.getElementById('description').value = '';
                document.getElementById('priority').value = '';
                document.getElementById('category').value = '';
                document.getElementById('due_date').value = '';
                document.getElementById('custom-category').value = '';

                // Reset character counters
                updateCharCount('title', 255);
                updateCharCount('description', 1000);

                // Reset field styles
                let fields = ['title', 'description', 'priority', 'category', 'due_date'];
                fields.forEach(function(fieldId) {
                    document.getElementById(fieldId).style.border = '1px solid #ccc';
                });

                document.getElementById('due-date-warning').textContent = '';
            }
        }

        // BAD PRACTICE: Initialize character counters on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCharCount('title', 255);
            updateCharCount('description', 1000);

            // BAD PRACTICE: Auto-focus on first field (bad for accessibility)
            document.getElementById('title').focus();
        });

        // BAD PRACTICE: Prevent form submission on Enter in text fields (terrible UX)
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' && event.target.type === 'text') {
                event.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>
