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
?>

<?php
// Handle form submission via POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // If the form is for adding a new teacher
    if (isset($_POST['add_teacher'])) {
        // Sanitize and collect form data
        $firstName     = sanitize($_POST['first_name']);
        $lastName      = sanitize($_POST['last_name']);
        $gender        = $_POST['gender'];
        $birthDate     = $_POST['birth_date'];
        $phone         = sanitize($_POST['phone']);
        $email         = sanitize($_POST['email']);
        $address       = sanitize($_POST['address']);
        $salary        = (float)$_POST['salary'];
        $hireDate      = $_POST['hire_date'];
        $qualification = $_POST['qualification'];

        // Prepare SQL insert statement for teacher
        $stmt = $conn->prepare("INSERT INTO teacher 
            (t_first_name, t_last_name, t_gender, t_birth_date, t_phone, 
             t_email, t_address, t_annual_salary, t_hire_date, t_qualification)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Bind the form inputs to the statement
        $stmt->bind_param(
            'sssssssdss',
            $firstName,
            $lastName,
            $gender,
            $birthDate,
            $phone,
            $email,
            $address,
            $salary,
            $hireDate,
            $qualification
        );

        // Execute insert operation
        $stmt->execute();
    }
}

// Handle deletion of a teacher via GET
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $teacherId = (int)$_GET['id'];

    // Check if the teacher is not assigned to any class
    $classCheck = $conn->query("SELECT class_id FROM class WHERE teacher_id = $teacherId");

    // Only delete if no class is linked to this teacher
    if ($classCheck->num_rows === 0) {
        $conn->query("DELETE FROM teacher WHERE teacher_id = $teacherId");
    }

    // Redirect back to teacher list
    header("Location: teacher.php");
    exit();
}

// Retrieve statistics for salary distribution
$salaryRanges = $conn->query("
    SELECT 
        SUM(IF(t_annual_salary < 42000, 1, 0)) AS 'under_42k',
        SUM(IF(t_annual_salary >= 42000 AND t_annual_salary < 45000, 1, 0)) AS '42k_45k',
        SUM(IF(t_annual_salary >= 45000 AND t_annual_salary < 48000, 1, 0)) AS '45k_48k',
        SUM(IF(t_annual_salary >= 48000, 1, 0)) AS 'over_48k'
    FROM teacher
")->fetch_assoc();

// Initialize filter string
$filters = "WHERE 1=1";

// Add keyword-based filter
if (!empty($_GET['keyword'])) {
    // Sanitize the keyword input
    // Use real_escape_string to prevent SQL injection
    $k = $conn->real_escape_string($_GET['keyword']);
    // Add to filter string
    // Use LIKE for partial matches
    $filters .= " AND (
        t.teacher_id LIKE '%$k%' OR 
        t.t_first_name LIKE '%$k%' OR 
        t.t_last_name LIKE '%$k%' OR 
        t.t_phone LIKE '%$k%' OR 
        t.t_email LIKE '%$k%'
    )";
}

// Add age range filter (min)
if (!empty($_GET['age_min'])) {
    // Sanitize the age input
    $filters .= " AND TIMESTAMPDIFF(YEAR, t_birth_date, CURDATE()) >= " . (int)$_GET['age_min'];
}

// Add age range filter (max)
if (!empty($_GET['age_max'])) {
    $filters .= " AND TIMESTAMPDIFF(YEAR, t_birth_date, CURDATE()) <= " . (int)$_GET['age_max'];
}

// Add minimum salary filter
if (!empty($_GET['salary_min'])) {
    // Sanitize the salary input
    $filters .= " AND t_annual_salary >= " . (int)$_GET['salary_min'];
}

// Add maximum salary filter
if (!empty($_GET['salary_max'])) {
    $filters .= " AND t_annual_salary <= " . (int)$_GET['salary_max'];
}

// Add qualification filter (exact match)
if (!empty($_GET['qualification'])) {
    // Sanitize the qualification input
    // Use real_escape_string to prevent SQL injection
    $q = $conn->real_escape_string($_GET['qualification']);
    // Add to filter string
    $filters .= " AND t_qualification = '$q'";
}

// Final query to fetch teacher list with filters
$search_results = $conn->query("
    SELECT 
        t.*, 
        c.class_name,
        COUNT(c.class_id) AS class_count,
        TIMESTAMPDIFF(YEAR, t.t_birth_date, CURDATE()) AS age,
        DATE_FORMAT(t.t_hire_date, '%Y-%m') AS hire_year_month
    FROM teacher t
    LEFT JOIN class c ON t.teacher_id = c.teacher_id
    $filters
    GROUP BY t.teacher_id
");

// Determine if this is a search request
$is_search = isset($_GET['keyword']) || isset($_GET['age_min']) || isset($_GET['age_max']) ||
             isset($_GET['salary_min']) || isset($_GET['salary_max']) || isset($_GET['qualification']);
?>


<?php
// Initialize edit form data to null
$edit_data = null;

// Check if we are in edit mode with a valid teacher ID
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];

    // Handle the update form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_teacher'])) {
        // Sanitize and assign form data
        $first         = sanitize($_POST['first_name']);
        $last          = sanitize($_POST['last_name']);
        $gender        = $_POST['gender'];
        $birth         = $_POST['birth_date'];
        $phone         = sanitize($_POST['phone']);
        $email         = sanitize($_POST['email']);
        $address       = sanitize($_POST['address']);
        $salary        = (float)$_POST['salary'];
        $hire          = $_POST['hire_date'];
        $qualification = $_POST['qualification'];

        // Prepare update query
        $stmt = $conn->prepare("UPDATE teacher SET 
            t_first_name=?, t_last_name=?, t_gender=?, t_birth_date=?, 
            t_phone=?, t_email=?, t_address=?, t_annual_salary=?, t_hire_date=?, t_qualification=?
            WHERE teacher_id=?");

        // Bind the parameters for update
        $stmt->bind_param(
            'ssssssssssi',
            $first,
            $last,
            $gender,
            $birth,
            $phone,
            $email,
            $address,
            $salary,
            $hire,
            $qualification,
            $edit_id
        );

        // If update is successful, redirect to teacher list
        if ($stmt->execute()) {
            // Redirect to the teacher list page
            header("Location: teacher.php");
            exit;
        } else {
            // If update fails, show error message
            $error = "Update failed: {$conn->error}";
        }
    }

    // Retrieve the teacher's current information
    $result = $conn->query("SELECT * FROM teacher WHERE teacher_id = $edit_id");

    // If teacher exists, store info in $edit_data
    if ($result && $result->num_rows === 1) {
        // Fetch the teacher's data
        $edit_data = $result->fetch_assoc();
    } else {
        $error = "Teacher not found or invalid ID.";
    }
}
?>

<!-- HTML Section-->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Management</title>
    <link rel="stylesheet" href="teacher.css">
    <!-- Chart.js for salary chart rendering -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body>

<!-- Homepage navigation button -->
<a href="homepage.php" class="home-button" aria-label="Return to homepage">
    <span class="desktop-text">Return to Home</span>
    <span class="mobile-text">Home</span>
</a>

<!-- Page Title -->
<h1>Teacher Management</h1>

<!-- Search Form Section -->
<div class="form-section">
    <h2>Search Teachers</h2>
    <form method="GET" style="margin-bottom: 30px;">
        <!-- Search fields: keyword, age, salary, qualification -->
        <input type="text" name="keyword" placeholder="Keyword (ID / Name / Phone / Email)" value="<?= $_GET['keyword'] ?? '' ?>">
        <input type="number" name="age_min" placeholder="Age ≥" value="<?= $_GET['age_min'] ?? '' ?>">
        <input type="number" name="age_max" placeholder="Age ≤" value="<?= $_GET['age_max'] ?? '' ?>">
        <input type="number" name="salary_min" placeholder="Salary ≥" value="<?= $_GET['salary_min'] ?? '' ?>">
        <input type="number" name="salary_max" placeholder="Salary ≤" value="<?= $_GET['salary_max'] ?? '' ?>">

        <!-- Qualification dropdown -->
        <select name="qualification">
            <option value="">-- Qualification --</option>
            <option <?= ($_GET['qualification'] ?? '') === 'Bachelor' ? 'selected' : '' ?>>Bachelor</option>
            <option <?= ($_GET['qualification'] ?? '') === 'Master' ? 'selected' : '' ?>>Master</option>
            <option <?= ($_GET['qualification'] ?? '') === 'PhD' ? 'selected' : '' ?>>PhD</option>
        </select>

        <!-- Search and Reset buttons -->
        <button type="submit" class="btn-search">Search</button>
        <a href="teacher.php" class="btn-reset">Reset</a>
    </form>
</div>

<?php
// Check whether this is an actual search (not edit/delete)
$is_search = isset($_GET['keyword']) || isset($_GET['age_min']) || isset($_GET['salary_min']) || isset($_GET['qualification']);
?>

<!-- If search matches found, display table -->
<?php if ($is_search && $search_results && $search_results->num_rows > 0): ?>
    <!-- Display search results in a table -->
    <h2>Search Results (<?= $search_results->num_rows ?>)</h2>
    <table class="styled-table">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Birth</th>
            <th>Contact</th>
            <th>Annual Salary</th>
            <th>Class</th>
            <th>Hire Date</th>
            <th>Qualification</th>
            <th>Actions</th>
        </tr>

        <!-- Loop through each search result row -->
        <?php while ($row = $search_results->fetch_assoc()): ?>
        <tr>
            <td><?= $row['teacher_id'] ?></td>
            <!-- Display teacher's full name -->
            <td><?= "{$row['t_last_name']} {$row['t_first_name']}" ?></td>
            <td>
                <?= $row['t_birth_date'] ?><br>
                <small style="color:#555;">Age: <?= $row['age'] ?></small>
            </td>
            <td>
                <!-- Contact dropdown to toggle between phone/email -->
                <select onchange="this.nextElementSibling.textContent = this.value">
                    <option value="<?= $row['t_phone'] ?>">Phone</option>
                    <option value="<?= $row['t_email'] ?>">Email</option>
                </select>
                <div><?= $row['t_phone'] ?></div>
            </td>
            <!-- Display annual salary -->
            <td>£<?= number_format($row['t_annual_salary'], 2) ?></td>
            <td>
                <!-- If teacher assigned to class, link to class -->
                <?php if ($row['class_count'] > 0): ?>
                    <!-- Link to class page with teacher ID -->
                    <a href="class.php?teacher_id=<?= $row['teacher_id'] ?>"><?= $row['class_name'] ?></a>
                <?php else: ?>
                    <!-- No class assigned -->
                    <span class="no-class">Undistributed</span>
                <?php endif; ?>
            </td>
            <!-- Display hire date -->
            <td><?= $row['hire_year_month'] ?></td>
            <td><?= $row['t_qualification'] ?></td>
            <td>
                <!-- Edit and Delete action buttons -->
                <a href="teacher.php?action=edit&id=<?= $row['teacher_id'] ?>">Edit</a> |
                <a href="?action=delete&id=<?= $row['teacher_id'] ?>" onclick="return confirm('Delete this teacher?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

<!-- If no matching teachers, show notice -->
<?php elseif ($is_search): ?>
    <!-- No matching teachers found -->
    <div class="notice">No matching teachers found.</div>
<?php endif; ?>

<!-- Add New Teacher Form Section -->
<div class="form-section">
    <h2>Add New Teacher</h2>
    <form method="POST">
        <table>
            <!-- Name fields -->
            <tr>
                <td><label>First Name:</label></td>
                <td><input type="text" name="first_name" required></td>
                <td><label>Last Name:</label></td>
                <td><input type="text" name="last_name" required></td>
            </tr>

            <!-- Gender and Birth Date -->
            <tr>
                <td><label>Gender:</label></td>
                <td>
                    <select name="gender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </td>
                <td><label>Birth Date:</label></td>
                <td><input type="date" name="birth_date" required></td>
            </tr>

            <!-- Phone and Email -->
            <tr>
                <td><label>Phone:</label></td>
                <td><input type="tel" name="phone" pattern="[0-9]{11}" required></td>
                <td><label>E-mail:</label></td>
                <td><input type="email" name="email" required></td>
            </tr>

            <!-- Address -->
            <tr>
                <td><label>Address:</label></td>
                <td colspan="3"><input type="text" name="address" style="width: 500px;" required></td>
            </tr>

            <!-- Salary and Hire Date -->
            <tr>
                <td><label>Annual Salary:</label></td>
                <td><input type="number" name="salary" step="0.01" min="30000" required></td>
                <td><label>Hire Date:</label></td>
                <td><input type="date" name="hire_date" required></td>
            </tr>

            <!-- Qualification and Action buttons -->
            <tr>
                <td><label>Qualification:</label></td>
                <td>
                    <select name="qualification" required>
                        <option value="Bachelor">Bachelor</option>
                        <option value="Master">Master</option>
                        <option value="PhD">PhD</option>
                    </select>
                </td>
                <td colspan="2">
                    <!-- Add and cancel buttons -->
                    <button type="submit" name="add_teacher" class="btn">Add</button>
                    <a href="teacher.php" class="btn-cancel">Cancel</a>
                </td>
            </tr>
        </table>
    </form>
</div>


<!-- Edit Teacher Form -->
<?php if ($edit_data): ?>
<div class="form-section">
    <!-- Display the teacher's full name in the title -->
    <h2>Edit Teacher: <?= "{$edit_data['t_first_name']} {$edit_data['t_last_name']}" ?></h2>
    <form method="POST">
        <!-- Hidden input to detect update submission -->
        <input type="hidden" name="update_teacher" value="1">
        <table>
            <tr>
                <!-- Pre-fill first and last name -->
                <td><label>First Name:</label></td>
                <td><input type="text" name="first_name" value="<?= $edit_data['t_first_name'] ?>" required></td>
                <td><label>Last Name:</label></td>
                <td><input type="text" name="last_name" value="<?= $edit_data['t_last_name'] ?>" required></td>
            </tr>
            <tr>
                <!-- Pre-fill gender and birthdate -->
                <td><label>Gender:</label></td>
                <td>
                    <select name="gender" required>
                        <option value="Male" <?= $edit_data['t_gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $edit_data['t_gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </td>
                <td><label>Birth Date:</label></td>
                <td><input type="date" name="birth_date" value="<?= $edit_data['t_birth_date'] ?>" required></td>
            </tr>
            <tr>
                <!-- Pre-fill phone and email -->
                <td><label>Phone:</label></td>
                <td><input type="tel" name="phone" value="<?= $edit_data['t_phone'] ?>" required></td>
                <td><label>Email:</label></td>
                <td><input type="email" name="email" value="<?= $edit_data['t_email'] ?>" required></td>
            </tr>
            <tr>
                <!-- Pre-fill address -->
                <td><label>Address:</label></td>
                <td colspan="3">
                    <input type="text" name="address" value="<?= $edit_data['t_address'] ?>" style="width: 500px;" required>
                </td>
            </tr>
            <tr>
                <!-- Pre-fill salary and hire date -->
                <td><label>Annual Salary:</label></td>
                <td><input type="number" name="salary" value="<?= $edit_data['t_annual_salary'] ?>" step="0.01" required></td>
                <td><label>Hire Date:</label></td>
                <td><input type="date" name="hire_date" value="<?= $edit_data['t_hire_date'] ?>" required></td>
            </tr>
            <tr>
                <!-- Pre-select qualification -->
                <td><label>Qualification:</label></td>
                <td>
                    <select name="qualification" required>
                        <option value="Bachelor" <?= $edit_data['t_qualification'] === 'Bachelor' ? 'selected' : '' ?>>Bachelor</option>
                        <option value="Master" <?= $edit_data['t_qualification'] === 'Master' ? 'selected' : '' ?>>Master</option>
                        <option value="PhD" <?= $edit_data['t_qualification'] === 'PhD' ? 'selected' : '' ?>>PhD</option>
                    </select>
                </td>
                <td colspan="2">
                    <!-- Update and cancel buttons -->
                    <button type="submit" class="btn">Update</button>
                    <a href="teacher.php" class="btn-cancel">Cancel</a>
                </td>
            </tr>
        </table>
    </form>
</div>
<?php endif; ?>

<!-- Teacher List Table Section -->
<h2>Teacher List</h2>
<?php
// Query all teachers with associated class info
$teachers = $conn->query("
    SELECT 
        t.*, 
        c.class_name,
        COUNT(c.class_id) AS class_count,
        TIMESTAMPDIFF(YEAR, t.t_birth_date, CURDATE()) AS age,
        DATE_FORMAT(t.t_hire_date, '%Y-%m') AS hire_year_month
    FROM teacher t
    LEFT JOIN class c ON t.teacher_id = c.teacher_id
    GROUP BY t.teacher_id
");
// Check if any teachers exist
?>
<table class="styled-table">
    <tr>
        <!-- Table headers -->
        <th>ID</th>
        <th>Name</th>
        <th>Birth</th>
        <th>Contact</th>
        <th>Annual Salary</th>
        <th>Class</th>
        <th>Hire Date</th>
        <th>Qualification</th>
        <th>Actions</th>
    </tr>
    <!-- Loop through each teacher row -->
    <?php while ($row = $teachers->fetch_assoc()): ?>
    <tr>
        <!-- Display teacher information -->
        <td><?= $row['teacher_id'] ?></td>
        <td><?= "{$row['t_last_name']} {$row['t_first_name']}" ?></td>
        <td>
            <!-- Display birth date and age -->
            <?= $row['t_birth_date'] ?><br>
            <small style="color:#555;">Age: <?= $row['age'] ?></small>
        </td>
        <td>
            <!-- Toggle display of phone/email -->
            <select onchange="this.nextElementSibling.textContent = this.value">
                <option value="<?= $row['t_phone'] ?>">Phone</option>
                <option value="<?= $row['t_email'] ?>">Email</option>
            </select>
            <div><?= $row['t_phone'] ?></div>
        </td>
        <!-- Display annual salary -->
        <td>£<?= number_format($row['t_annual_salary'], 2) ?></td>
        <td>
            <!-- Class assignment check -->
            <?php if ($row['class_count'] > 0): ?>
                <!-- If teacher assigned to class, link to class -->
                <a href="class.php?teacher_id=<?= $row['teacher_id'] ?>" class="class-link">
                    <?= $row['class_name'] ?>
                </a>
            <?php else: ?>
                <!-- No class assigned -->
                <span class="no-class">Undistributed</span>
            <?php endif; ?>
        </td>
        <!-- Display hire date -->
        <td><?= $row['hire_year_month'] ?></td>
        <td><?= $row['t_qualification'] ?></td>
        <td>
            <!-- Edit and Delete options -->
            <a href="teacher.php?action=edit&id=<?= $row['teacher_id'] ?>">Edit</a> |
            <a href="?action=delete&id=<?= $row['teacher_id'] ?>" onclick="return confirm('Are you sure to delete this teacher')">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<!-- Salary Chart Section -->
<div class="form-section">
    <h2>Teachers Annual Salary Distribution</h2>
    <canvas id="salaryChart"></canvas>
</div>

<script>
// Prepare chart dataset from PHP
const salaryData = {
    labels: ['< £42,000', '£42,000 - £45,000', '£45,000 - £48,000', '> £48,000'],
    datasets: [{
        // Data from PHP variables
        label: 'Salary Distribution',
        data: [
            <?= $salaryRanges['under_42k'] ?>,
            <?= $salaryRanges['42k_45k'] ?>,
            <?= $salaryRanges['45k_48k'] ?>,
            <?= $salaryRanges['over_48k'] ?>
        ],
        backgroundColor: [
            'rgba(255, 99, 132, 0.6)',    // Red
            'rgba(54, 162, 235, 0.6)',    // Blue
            'rgba(255, 206, 86, 0.6)',    // Yellow
            'rgba(75, 192, 192, 0.6)'     // Green
        ],
        borderColor: [
            'rgba(255, 99, 132, 1)',
            'rgba(54, 162, 235, 1)',
            'rgba(255, 206, 86, 1)',
            'rgba(75, 192, 192, 1)'
        ],
        borderWidth: 1
    }]
};

// Initialize Chart.js bar chart
new Chart(document.getElementById('salaryChart'), {
    // Set chart type to bar
    type: 'bar',
    data: salaryData,
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            title: {
                display: true,
                text: 'Teachers Annual Salary Distribution'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 16
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