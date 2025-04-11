<?php
// Database Connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'SA_PriSchool';
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Helper function for sanitization
function sanitize_input($data) {
    return htmlspecialchars(trim($data));
}

// Function to get current students in a class
function get_current_students($class_id) {
    // Access the global database connection
    global $conn;

    // Prepare SQL statement to count pupils in a class
    $stmt = $conn->prepare("SELECT COUNT(pupil_id) FROM pupil WHERE class_id = ?");
    $stmt->bind_param('i', $class_id); // Bind the class ID as an integer
    $stmt->execute(); // Execute the SQL query

    // Fetch and return the count result
    return $stmt->get_result()->fetch_row()[0];
}

// Check if user has selected a class to view students
$students = []; // Initialize empty array to store students

if (isset($_GET['view_students']) && !empty($_GET['selected_class'])) {
    // If 'view_students' is set and a class is selected

    $selected_class = (int)$_GET['selected_class']; // Sanitize class ID

    // Prepare SQL to get pupil info by class
    $stmt = $conn->prepare("
        SELECT 
            pupil_id, 
            p_first_name, 
            p_last_name, 
            p_gender 
        FROM pupil 
        WHERE class_id = ?
        ORDER BY p_last_name, p_first_name
    ");
    $stmt->bind_param('i', $selected_class); // Bind class ID
    $stmt->execute(); // Run the query
    $result = $stmt->get_result(); // Get result set
    $students = $result->fetch_all(MYSQLI_ASSOC); // Fetch as associative array
}

// Process form submission (Create, Update, Add Student)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only handle if form is submitted via POST

    if (isset($_POST['create'])) {
        // Placeholder for create logic (not implemented)
    
    } elseif (isset($_POST['update'])) {
        // If 'update' action is triggered

        $class_id = (int)$_POST['class_id']; // Sanitize class ID
        $class_name = sanitize_input($_POST['class_name']); // Sanitize class name
        $class_capacity = (int)$_POST['class_capacity']; // Sanitize capacity
        $teacher_id = (int)$_POST['teacher_id']; // Sanitize teacher ID

        // Get number of current students in class
        $current_students = get_current_students($class_id);

        // If the form includes adding a new student
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
            $first = sanitize_input($_POST['p_first_name']); // Get and sanitize first name
            $last = sanitize_input($_POST['p_last_name']);   // Get and sanitize last name
            $gender = sanitize_input($_POST['p_gender']);    // Sanitize gender
            $dob = $_POST['p_dob'];                          // Date of birth (no extra sanitize)
            $address = sanitize_input($_POST['p_address']);  // Sanitize address
            $class_id = (int)$_POST['class_id'];             // Re-confirm class ID
        
            // Insert student into pupil table
            $stmt = $conn->prepare("INSERT INTO pupil (p_first_name, p_last_name, p_gender, p_date_of_birth, p_address, class_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssssi', $first, $last, $gender, $dob, $address, $class_id); // Bind all fields
        
            if ($stmt->execute()) {
                $success = "Student added successfully!"; // Success feedback
            } else {
                $error = "Failed to add student: " . $conn->error; // Error feedback
            }
        }

        // Validation: Capacity cannot be lower than current student count
        if ($class_capacity < $current_students) {
            $error = "New capacity cannot be less than current student count ($current_students)";
        } else {
            // Check if teacher is already assigned to another class
            $stmt = $conn->prepare("SELECT class_id FROM class WHERE teacher_id = ? AND class_id != ?");
            $stmt->bind_param('ii', $teacher_id, $class_id); // Bind teacher and current class
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                // Prevent one teacher from being assigned to multiple classes
                $error = "This teacher is already assigned to another class.";
            } else {
                // Update class info in database
                $stmt = $conn->prepare("UPDATE class SET class_name=?, class_capacity=?, teacher_id=? WHERE class_id=?");
                $stmt->bind_param('siii', $class_name, $class_capacity, $teacher_id, $class_id); // Bind new values

                if ($stmt->execute()) {
                    $success = "Class updated successfully!"; // Show success message
                } else {
                    $error = "Update failed: " . $conn->error; // Show error message
                }
            }
        }
    }
}

// Handle Delete Action (with student existence check)
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $class_id = (int)$_GET['id']; // Sanitize class ID from GET

    // Check if any students are still in this class
    $stmt = $conn->prepare("SELECT pupil_id FROM pupil WHERE class_id = ?");
    $stmt->bind_param('i', $class_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        // If class is empty, proceed with deletion
        $stmt = $conn->prepare("DELETE FROM class WHERE class_id = ?");
        $stmt->bind_param('i', $class_id);
        $stmt->execute();
    }

    // Redirect to current page without query string
    header("Location: ".strtok($_SERVER['REQUEST_URI'], '?'));
    exit(); // Stop further execution
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Basic page setup for proper encoding and responsiveness -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Management System</title>

    <!-- Link to external CSS file for styling -->
    <link rel="stylesheet" href="class.css">
</head>
<body>
    <!-- Navigation button back to homepage -->
    <a href="homepage.php" class="home-button" aria-label="Return to homepage">
        <span class="desktop-text">Return to Home</span>
        <span class="mobile-text">Home</span>
    </a>
    
    <h1>Class Management</h1>

    <!-- Navigation links to different sections -->
    <div class="nav">
        <a href="?action=list" class="link">Class List</a> | 
        <a href="#create_form" class="link">Create New Class</a> | 
        <a href="?action=history" class="link">Change History</a>
    </div>

    <!-- Success or Error message display -->
    <?php if (isset($success)): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <!-- Class List Section -->
    <h2>Class List</h2>
    <?php
    // Query to retrieve class info, teacher name, and current student count
    $classes = $conn->query("
        SELECT 
            c.class_id,
            c.class_name,
            c.class_capacity,
            CONCAT(t.t_first_name, ' ', t.t_last_name) AS teacher_name,
            COUNT(p.pupil_id) AS student_count 
        FROM class c
        LEFT JOIN teacher t ON c.teacher_id = t.teacher_id
        LEFT JOIN pupil p ON c.class_id = p.class_id
        GROUP BY c.class_id
    ");
    ?>

    <!-- Display class table with progress bars -->
    <table class="styled-table">
        <tr>
            <th>Class ID</th>
            <th>Class Name</th>
            <th>Capacity</th>
            <th>Current Population</th>
            <th>Teacher</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $classes->fetch_assoc()): ?>
        <tr>
            <td><?= $row['class_id'] ?></td>
            <td><?= $row['class_name'] ?></td>
            <td><?= $row['class_capacity'] ?></td>
            <td>
                <?= $row['student_count'] ?>
                <!-- Dynamic progress bar for class capacity usage -->
                <div class="progress-bar">
                    <div style="width: <?= ($row['student_count'] / $row['class_capacity']) * 100 ?>%"></div>
                </div>
            </td>
            <td><?= $row['teacher_name'] ?></td>
            <td>
                <!-- Links for editing or deleting class -->
                <a href="?action=edit&id=<?= $row['class_id'] ?>">Edit</a>
                <a href="?action=delete&id=<?= $row['class_id'] ?>" 
                    onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <!-- Class Edit Form -->
    <?php if (isset($_GET['action']) && $_GET['action'] === 'edit'): ?>
        <?php
        // Get class data for editing
        $class_id = (int)$_GET['id'];
        $stmt = $conn->prepare("SELECT * FROM class WHERE class_id = ?");
        $stmt->bind_param('i', $class_id);
        $stmt->execute();
        $class = $stmt->get_result()->fetch_assoc();

        // Get list of teachers for dropdown
        $teachers = $conn->query("
            SELECT 
                teacher_id,
                CONCAT(t_first_name, ' ', t_last_name) AS teacher_name 
            FROM teacher
        ");
        ?>

        <!-- Class Edit Form UI -->
        <div class="form-section">
            <h2>Edit Class</h2>
            <form method="POST">
                <!-- Hidden field for class ID -->
                <input type="hidden" name="class_id" value="<?= $class['class_id'] ?>">
                <div class="form-row">
                    <label>Class Name: 
                        <input type="text" name="class_name" 
                            value="<?= $class['class_name'] ?>" required>
                    </label>
                </div>
                <div class="form-row">
                    <label>Capacity: 
                        <input type="number" name="class_capacity" 
                            value="<?= $class['class_capacity'] ?>" min="1" required>
                    </label>
                </div>
                <div class="form-row">
                    <label>Assigned Teacher:
                        <!-- Teacher dropdown with current selection -->
                        <select name="teacher_id" required>
                            <?php while ($row = $teachers->fetch_assoc()): ?>
                                <option value="<?= $row['teacher_id'] ?>" 
                                    <?= $class['teacher_id'] == $row['teacher_id'] ? 'selected' : '' ?>>
                                    <?= $row['teacher_name'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </label>
                </div>
                <div class="form-row">
                    <!-- Submit update or cancel -->
                    <button type="submit" name="update">Update Class</button>
                    <a href="?" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- View Students by Class -->
    <div class="student-list-section">
        <h2>View Students by Class</h2>
        <form method="GET">
            <div class="form-row">
                <label>Select Class:
                    <!-- Class selector for student viewing -->
                    <select name="selected_class" required>
                        <option value="">Choose a class</option>
                        <?php
                        $classes = $conn->query("SELECT class_id, class_name FROM class");
                        while ($class = $classes->fetch_assoc()): ?>
                            <option value="<?= $class['class_id'] ?>" 
                                <?= isset($_GET['selected_class']) && $_GET['selected_class'] == $class['class_id'] ? 'selected' : '' ?>>
                                <?= $class['class_name'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </label>
                <button type="submit" name="view_students" class="view_button">View Students</button>
                <a href="?" class="btn-cancel">Cancel</a>
            </div>
        </form>

        <!-- If students are found, show them in a table -->
        <?php if (!empty($students)): ?>
        <div class="student-results">
            <h3>Students in Selected Class (<?=count($students)?>)</h3>
            <table class="styled-table">
                <tr>
                    <th>Student ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Gender</th>
                </tr>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= $student['pupil_id'] ?></td>
                    <td><?= htmlspecialchars($student['p_first_name']) ?></td>
                    <td><?= htmlspecialchars($student['p_last_name']) ?></td>
                    <td><?= htmlspecialchars($student['p_gender']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php elseif (isset($_GET['view_students'])): ?>
            <!-- Message if no students in selected class -->
            <div class="notice">No students found in this class.</div>
        <?php endif; ?>
    </div>

    <!-- Add New Student Form -->
    <section class="form-section">
    <h2>Add Student to Existing Class</h2>
    <form method="POST">
        <!-- Hidden field to signal student addition -->
        <input type="hidden" name="add_student" value="1">
        <div class="form-row">
            <label>First Name:
                <input type="text" name="p_first_name" required>
            </label>
        </div>
        <div class="form-row">
            <label>Last Name:
                <input type="text" name="p_last_name" required>
            </label>
        </div>
        <div class="form-row">
            <label>Gender:
                <!-- Gender dropdown with options -->
                <select name="p_gender" required>
                    <option value="">Select</option>
                    <option>Male</option>
                    <option>Female</option>
                    <option>Other</option>
                    <option>Prefer not to say</option>
                </select>
            </label>
        </div>
        <div class="form-row">
            <label>Date of Birth:
                <input type="date" name="p_dob" required>
            </label>
        </div>
        <div class="form-row">
            <label>Address:
                <input type="text" name="p_address" required>
            </label>
        </div>
        <div class="form-row">
            <label>Select Class:
                <!-- Dropdown for assigning student to a class -->
                <select name="class_id" required>
                    <?php
                    $classOptions = $conn->query("SELECT class_id, class_name FROM class");
                    while ($row = $classOptions->fetch_assoc()): ?>
                        <option value="<?= $row['class_id'] ?>"><?= $row['class_name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </label>
        </div>
        <div class="form-row">
            <!-- Submit or cancel buttons -->
            <button type="submit" class="btn-save">Add Student</button>
            <a href="?" class="btn-cancel">Cancel</a>
        </div>
    </form>
    </section>

<!-- Create New Class Form -->
<section id="create_form">
    <div class="form-section">
        <h2>Create New Class</h2>
        
        <!-- Form to create a new class -->
        <form method="POST">
            <div class="form-row">
                <!-- Input for class name -->
                <label>Class Name: 
                    <input type="text" name="class_name" required>
                </label>
            </div>
            
            <div class="form-row">
                <!-- Input for capacity with minimum of 1 -->
                <label>Capacity: 
                    <input type="number" name="class_capacity" min="1" required>
                </label>
            </div>
            
            <div class="form-row">
                <!-- Dropdown to assign teacher to new class -->
                <label>Assigned Teacher:
                    <?php
                    // Fetch all available teachers
                    $teachers = $conn->query("
                        SELECT 
                            teacher_id,
                            CONCAT(t_first_name, ' ', t_last_name) AS teacher_name 
                        FROM teacher
                    ");
                    ?>
                    <select name="teacher_id" required>
                        <!-- Loop through each teacher as an option -->
                        <?php while ($row = $teachers->fetch_assoc()): ?>
                            <option value="<?= $row['teacher_id'] ?>">
                                <?= $row['teacher_name'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </label>
            </div>
            
            <div class="form-row">
                <!-- Submit and cancel buttons -->
                <button type="submit" name="create">Create Class</button>
                <a href="?" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</section>
    
<!-- Change History Section -->
<?php if (isset($_GET['action']) && $_GET['action'] === 'history'): ?>
    <h2>Class Change History</h2>
    <?php
    // Query class_history table joined with class names
    $history = $conn->query("
        SELECT 
            ch.change_date,
            c.class_name,
            ch.changed_field,
            ch.old_value,
            ch.new_value 
        FROM class_history ch
        JOIN class c ON ch.class_id = c.class_id
        ORDER BY ch.change_date DESC
    ");
    ?>
    <table>
        <tr>
            <th>Date</th>
            <th>Class</th>
            <th>Field Changed</th>
            <th>Old Value</th>
            <th>New Value</th>
        </tr>
        <!-- Display each change as a row -->
        <?php while ($row = $history->fetch_assoc()): ?>
        <tr>
            <td><?= $row['change_date'] ?></td>
            <td><?= $row['class_name'] ?></td>
            <td><?= $row['changed_field'] ?></td>
            <td><?= $row['old_value'] ?></td>
            <td><?= $row['new_value'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
<?php endif; ?>

<?php
// Query number of students per class for chart
$class_labels = [];
$class_counts = [];

// SQL to count pupils in each class
$res = $conn->query("
    SELECT c.class_name, COUNT(p.pupil_id) AS total
    FROM class c
    LEFT JOIN pupil p ON c.class_id = p.class_id
    GROUP BY c.class_id
");

// Save class names and counts for chart
while ($row = $res->fetch_assoc()) {
    $class_labels[] = $row['class_name'];
    $class_counts[] = $row['total'];
}
?>

<!-- Chart Section -->
<div class="form-section">
    <h2>Class Size Chart</h2>

    <!-- Container for Chart.js bar chart -->
    <div style="max-width: 800px; margin: 30px auto;">
        <canvas id="classChart"></canvas>
    </div>
</div>

<!-- Load Chart.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Initialize Chart.js bar chart
    const classChart = new Chart(document.getElementById('classChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($class_labels) ?>, // Set x-axis labels
            datasets: [{
                label: 'Number of Pupils per Class',
                data: <?= json_encode($class_counts) ?>, // Set y-axis data
                backgroundColor: 'rgba(54, 162, 235, 0.6)', // Bar fill color
                borderColor: 'rgba(54, 162, 235, 1)', // Bar border color
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                legend: { display: false } // Hide legend
            },
            scales: {
                y: {
                    max: 30, // Set y-axis maximum value
                    beginAtZero: true, // Start y-axis from 0
                    precision: 0,
                    ticks: { stepSize: 1 } // Steps of 1 for clarity
                }
            }
        }
    });
</script>

</body>
</html>

<?php
// All logic done, now close connection
$conn->close();
?>