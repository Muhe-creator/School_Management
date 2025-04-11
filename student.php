<?php
// ------------------【 initial setup 】------------------
session_start(); // Set permissions setting

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

$action = $_GET['action'] ?? 'list';
$success = '';
$error = '';

// ------------------【 Definition of General Functions 】------------------
// Function to escape HTML characters
function safe($str) {
    // htmlspecialchars() converts special characters to HTML entities
    // ENT_QUOTES converts both double and single quotes
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function getClassOptions($conn, $selected_id = null) {
    // Fetch class options from the database
    // and return as HTML <option> elements
    $options = "";
    $res = $conn->query("SELECT class_id, class_name FROM class ORDER BY class_name");
    while ($row = $res->fetch_assoc()) {
        // Check if the current class_id matches the selected_id
        // If so, add the 'selected' attribute to the <option>
        $selected = ($row['class_id'] == $selected_id) ? 'selected' : '';
        $options .= "<option value='{$row['class_id']}' $selected>" . safe($row['class_name']) . "</option>";
    }
    // Return the generated options
    return $options;
}

// Function to check if a value is selected
function selected($val, $target) {
    // Check if the value matches the target
    return ($val === $target) ? 'selected' : '';
}
?>

<?php if ($action === 'list'): ?>

<?php
// Handle the deletion request
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // Check if the pupil_id is valid
    $stmt = $conn->prepare("SELECT class_id FROM pupil WHERE pupil_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $class_id = $row['class_id'];

        // Check the number of pupils in the class
        // Use COUNT(*) to get the total number of pupils in the class
        $countStmt = $conn->prepare("SELECT COUNT(*) AS count FROM pupil WHERE class_id = ?");
        $countStmt->bind_param("i", $class_id);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult->fetch_assoc();

        if ($countRow['count'] <= 10) {
            $error = "This class has only 10 pupils. Cannot delete to maintain minimum class size.";
        } else {
            // Proceed with the deletion
            // Prepare the SQL statement to delete the pupil
            $deleteStmt = $conn->prepare("DELETE FROM pupil WHERE pupil_id = ?");
            $deleteStmt->bind_param("i", $id);
            if ($deleteStmt->execute()) {
                $success = "Pupil deleted successfully.";
            } else {
                // If deletion fails, set an error message
                $error = "Delete failed: " . $conn->error;
            }
        }
    } else {
        $error = "Pupil not found.";
    }
}


// Handle the search/filtering
// Initialize variables for search filters
$keyword = $_GET['keyword'] ?? '';
$gender = $_GET['gender'] ?? '';
$class_id = $_GET['class_id'] ?? '';

// Sanitize the input
$where = "WHERE 1=1";
$params = [];
$types = '';

if ($keyword !== '') {
    // Add keyword search to the WHERE clause
    // Use LIKE for partial matching
    $where .= " AND (p.p_first_name LIKE ? OR p.p_last_name LIKE ?)";
    // Add the keyword to the parameters array
    $params[] = '%' . $keyword . '%';
    // Add the keyword again for the last name search
    $params[] = '%' . $keyword . '%';
    $types .= 'ss';
}
if ($gender !== '') {
    $where .= " AND p.p_gender = ?";
    $params[] = $gender;
    $types .= 's';
}
if ($class_id !== '') {
    $where .= " AND p.class_id = ?";
    $params[] = $class_id;
    $types .= 'i';
}

// Prepare the SQL statement with the WHERE clause
// Use JOIN to get class names
// Use ORDER BY to sort by pupil_id
$sql = "SELECT p.*, c.class_name 
        FROM pupil p
        JOIN class c ON p.class_id = c.class_id
        $where
        ORDER BY p.pupil_id ";
$stmt = $conn->prepare($sql);

// Bind the parameters if any
// Check if there are parameters to bind
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
// Execute the statement
$stmt->execute();
$students = $stmt->get_result();
?>

<!-- HTML and PHP code for displaying the list of pupils -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pupil Management</title>
    <link rel="stylesheet" href="student.css">
</head>
<body>


<!-- Return button with a link to the homepage -->
<a href="homepage.php" class="home-button" aria-label="Return to homepage">
        <span class="desktop-text">Return to Home</span>
        <span class="mobile-text">Home</span>
</a>

<!-- Main container for the pupil management page -->
<div class="container">
    <h1>Pupil Management</h1>

    <!-- Display success or error messages -->
    <?php if ($success): ?><div class="success"><?= safe($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error"><?= safe($error) ?></div><?php endif; ?>

    <!-- Search form for filtering pupils -->
    <h2>Search Pupils</h2>
    <form method="get" class="search-form">
        <!-- Hidden input to maintain the action -->
        <input type="hidden" name="action" value="list">
        <!-- Input fields for search criteria -->
        <input type="text" name="keyword" placeholder="Search name..." value="<?= safe($keyword) ?>">
        <select name="gender">
            <option value="">Gender</option>
            <option value="Male" <?= selected('Male', $gender) ?>>Male</option>
            <option value="Female" <?= selected('Female', $gender) ?>>Female</option>
        </select>
        <!-- Class selection dropdown -->
        <select name="class_id">
            <option value="">Class</option>
            <?= getClassOptions($conn, $class_id) ?>
        </select>
        <!-- Submit button and reset link -->
        <button type="submit">Search</button>
        <a href="?" class="btn-cancel">Reset</a>
        <a href="?action=add" class="btn-add">+ Add Pupil</a>
        <a href="?action=chart" class="btn-secondary">Statistics</a>
    </form>

    <!-- Table to display the list of pupils -->
    <h2>Pupil List</h2>
    <table class="styled-table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Gender</th>
            <th>Birth</th>
            <th>Class</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <!-- Loop through the result set and display each pupil -->
        <!-- If no pupils found, display a message -->
        <?php if ($students->num_rows === 0): ?>
            <tr><td colspan="6" style="text-align:center;">No students found.</td></tr>
        <?php else: ?>
            <!-- Loop through each pupil and display their details -->
            <!-- Use fetch_assoc() to get an associative array of the result -->
            <!-- Use safe() function to escape output -->
            <?php while ($p = $students->fetch_assoc()): ?>
                <tr>
                    <!-- Display pupil details in table cells -->
                    <td><?= $p['pupil_id'] ?></td>
                    <td><?= safe($p['p_first_name'] . ' ' . $p['p_last_name']) ?></td>
                    <td><?= $p['p_gender'] ?></td>
                    <td><?= $p['p_birth_date'] ?></td>
                    <td><?= safe($p['class_name']) ?></td>
                    <td>
                        <!-- Action links for each pupil -->
                        <a href="?action=detail&id=<?= $p['pupil_id'] ?>">View</a> |
                        <a href="?action=edit&id=<?= $p['pupil_id'] ?>">Edit</a> |
                        <a href="?action=list&delete=<?= $p['pupil_id'] ?>" onclick="return confirm('Delete this pupil?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>


</body>
</html>
<?php endif; // end of list ?>


<?php if ($action === 'add' || $action === 'edit'): ?>
<?php
$is_edit = ($action === 'edit');
$pupil_id = $_GET['id'] ?? null;

// Initialize variables for form fields
// Set default values for the form fields
$first = $last = $gender = $birth = $address = $class_id = '';

// If editing, fetch the pupil's data from the database
// Check if the pupil_id is valid and fetch the data
if ($is_edit) {
    // Validate the pupil_id
    if (!$pupil_id || !is_numeric($pupil_id)) {
        die("Invalid ID.");
    }
    // Prepare the SQL statement to fetch pupil data
    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM pupil WHERE pupil_id = ?");
    $stmt->bind_param("i", $pupil_id);
    $stmt->execute();
    $result = $stmt->get_result();
    // Check if the pupil exists
    // If not, display an error message
    if ($result->num_rows === 0) {
        die("Student not found.");
    }
    // Fetch the pupil data
    // Use fetch_assoc() to get an associative array of the result
    $row = $result->fetch_assoc();
    $first = $row['p_first_name'];
    $last = $row['p_last_name'];
    $gender = $row['p_gender'];
    $birth = $row['p_birth_date'];
    $address = $row['p_address'];
    $class_id = $row['class_id'];
}

// Check if the form is submitted
// If the request method is POST, process the form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = $_POST['p_first_name'];
    $last = $_POST['p_last_name'];
    $gender = $_POST['p_gender'];
    $birth = $_POST['p_birth_date'];
    $address = $_POST['p_address'];
    $class_id = intval($_POST['class_id']);

    // Validate the form data
    // Check if all required fields are filled
    if ($first && $last && $gender && $birth && $address && $class_id) {
        // Prepare the SQL statement for insert or update
        if ($is_edit) {
            // If editing, update the pupil's data
            $stmt = $conn->prepare("UPDATE pupil SET p_first_name=?, p_last_name=?, p_gender=?, p_birth_date=?, p_address=?, class_id=? WHERE pupil_id=?");
            // Bind the parameters
            // Use bind_param() to bind the variables to the prepared statement
            // Use "ssssssi" to specify the types of the parameters
            // s = string, i = integer
            $stmt->bind_param("ssssssi", $first, $last, $gender, $birth, $address, $class_id, $pupil_id);
            if ($stmt->execute()) {
                // If the update is successful, set a success message
                $success = "Pupil updated successfully!";
                header("Location: student.php?action=list");
                exit;
            } else {
                $error = "Update failed.";
            }
        } else {
            // If adding a new pupil, insert the data
            $stmt = $conn->prepare("INSERT INTO pupil (p_first_name, p_last_name, p_gender, p_birth_date, p_address, class_id) VALUES (?, ?, ?, ?, ?, ?)");
            // Bind the parameters
            $stmt->bind_param("sssssi", $first, $last, $gender, $birth, $address, $class_id);
            if ($stmt->execute()) {
                // If the insert is successful, set a success message
                $success = "Pupil added successfully!";
                header("Location: student.php?action=list");
                exit;
            } else {
                $error = "Add failed.";
            }
        }
    } else {
        // If any required field is empty, set an error message
        $error = "All fields are required.";
    }
}
?>

<!-- HTML and PHP code for adding/editing a pupil -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Edit' : 'Add' ?> Pupil</title>
    <link rel="stylesheet" href="student.css">
</head>
<body>
<div class="container">
    <!-- Return button with a link to the homepage -->
    <h2><?= $is_edit ? 'Edit' : 'Add New' ?> Pupil</h2>

    <!-- Display success or error messages -->
    <?php if ($error): ?><div class="error"><?= safe($error) ?></div><?php endif; ?>

    <!-- Form for adding/editing a pupil -->
    <form method="post">
        <!-- Hidden input to maintain the action -->
        <input type="text" name="p_first_name" placeholder="First Name" value="<?= safe($first) ?>" required>
        <input type="text" name="p_last_name" placeholder="Last Name" value="<?= safe($last) ?>" required>

        <select name="p_gender" required>
            <option value="">Select Gender</option>
            <option value="Male" <?= selected('Male', $gender) ?>>Male</option>
            <option value="Female" <?= selected('Female', $gender) ?>>Female</option>
        </select>

        <!-- Date input for birth date -->
        <!-- Use the value attribute to set the default date -->
        <input type="date" name="p_birth_date" value="<?= safe($birth) ?>" required>
        <input type="text" name="p_address" placeholder="Address" value="<?= safe($address) ?>" required>

        <!-- Class selection dropdown -->
        <select name="class_id" required>
            <option value="">Select Class</option>
            <?= getClassOptions($conn, $class_id) ?>
        </select>

        <!-- Submit button and cancel link -->
        <button type="submit"><?= $is_edit ? 'Update' : 'Add' ?> Pupil</button>
        <a href="student.php?action=list" class="btn-cancel">Cancel</a>
    </form>
</div>
</body>
</html>
<!-- End of add/edit pupil form -->
<?php endif; // end of add/edit ?>


<?php if ($action === 'detail'): ?>
<?php
// Obtain the student ID
// Check if the pupil_id is set in the URL
$pupil_id = $_GET['id'] ?? null;
if (!$pupil_id || !is_numeric($pupil_id)) {
    die("Invalid pupil ID.");
}
// Prepare the SQL statement to fetch pupil details

// Use prepared statements to prevent SQL injection
$stmt = $conn->prepare("SELECT p.*, c.class_name 
                        FROM pupil p 
                        JOIN class c ON p.class_id = c.class_id 
                        WHERE pupil_id = ?");
// Bind the pupil_id parameter
$stmt->bind_param("i", $pupil_id);
$stmt->execute();
// Fetch the pupil data
$pupil = $stmt->get_result()->fetch_assoc();
if (!$pupil) {
    die("Pupil not found.");
}

// Prepare the SQL statement to fetch linked guardians
// Use JOIN to get guardian details
$stmt = $conn->prepare("SELECT g.*, pg.relationship_type 
                        FROM pupil_guardian pg
                        JOIN guardian g ON pg.guardian_id = g.guardian_id
                        WHERE pg.pupil_id = ?");
$stmt->bind_param("i", $pupil_id);
$stmt->execute();
$guardians = $stmt->get_result();

// Prepare the SQL statement to fetch medical profile
// Use JOIN to get medical details
$stmt = $conn->prepare("SELECT * FROM medical_profile WHERE pupil_id = ?");
$stmt->bind_param("i", $pupil_id);
$stmt->execute();
$medical = $stmt->get_result()->fetch_assoc();
?>

<!-- HTML and PHP code for displaying pupil details -->
<!DOCTYPE html>
<html>
<head>
    <title>Pupil Detail</title>
    <link rel="stylesheet" href="student.css">
</head>
<body>
<div class="container">
    <h2>Pupil Details</h2>

    <p><strong>Name:</strong> <?= safe($pupil['p_first_name'] . ' ' . $pupil['p_last_name']) ?></p>
    <p><strong>Gender:</strong> <?= safe($pupil['p_gender']) ?></p>
    <p><strong>Birth Date:</strong> <?= safe($pupil['p_birth_date']) ?></p>
    <p><strong>Address:</strong> <?= safe($pupil['p_address']) ?></p>
    <p><strong>Class:</strong> <?= safe($pupil['class_name']) ?></p>

    <h3>Guardians</h3>
    <!-- Display linked guardians -->
    <!-- If no guardians are linked, display a message -->
    <?php if ($guardians->num_rows === 0): ?>
        <p><em>No guardians linked.</em></p>
    <?php else: ?>
        <ul>
        <!-- Loop through each guardian and display their details -->
        <?php while ($g = $guardians->fetch_assoc()): ?>
            <li>
                <!-- Display guardian details -->
                <?= safe($g['g_first_name'] . ' ' . $g['g_last_name']) ?> (<?= safe($g['relationship_type']) ?>)  
                – <?= safe($g['g_phone']) ?> | <?= safe($g['g_email']) ?>
            </li>
        <?php endwhile; ?>
        </ul>
    <?php endif; ?>
    <!-- Link to manage guardians -->
    <!-- Use the pupil_id to link to the manage guardians page -->
    <a href="?action=guardians&id=<?= $pupil_id ?>" class="btn-secondary">Manage Guardians</a>

    <h3>Medical Info</h3>
    <!-- Display medical profile details -->
    <!-- If no medical profile is available, display a message -->
    <?php if ($medical): ?>
        <p><strong>Blood Type:</strong> <?= safe($medical['blood_type']) ?></p>
        <p><strong>Allergies:</strong> <?= nl2br(safe($medical['allergies'])) ?></p>
        <p><strong>Chronic Issues:</strong> <?= nl2br(safe($medical['chronic_issues'])) ?></p>
    <?php else: ?>
        <!-- If no medical profile is available, display a message -->
        <p><em>No medical profile available.</em></p>
    <?php endif; ?>
    <br>
    <!-- Link to edit pupil details -->
    <a href="student.php?action=list" class="btn-cancel">Back to List</a>
</div>
</body>
</html>

<?php endif; // end of detail ?>

<?php if ($action === 'guardians'): ?>
<?php
$pupil_id = $_GET['id'] ?? null;
if (!$pupil_id || !is_numeric($pupil_id)) {
    die("Invalid pupil ID.");
}

// Prepare the SQL statement to fetch pupil details
$stmt = $conn->prepare("SELECT p_first_name, p_last_name FROM pupil WHERE pupil_id = ?");
$stmt->bind_param("i", $pupil_id);
$stmt->execute();
$pupil = $stmt->get_result()->fetch_assoc();
if (!$pupil) {
    die("Pupil not found.");
}

// Prepare the SQL statement to fetch pupil details
// Check if the unbind parameter is set in the URL
// If so, delete the guardian link
if (isset($_GET['unbind'])) {
    $guardian_id = intval($_GET['unbind']);
    // Prepare the SQL statement to delete the guardian link
    $stmt = $conn->prepare("DELETE FROM pupil_guardian WHERE pupil_id = ? AND guardian_id = ?");
    $stmt->bind_param("ii", $pupil_id, $guardian_id);
    $stmt->execute();
}

// Handle the binding of a new guardian
// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardian_id'], $_POST['relationship_type'])) {
    $guardian_id = intval($_POST['guardian_id']);
    $relationship = trim($_POST['relationship_type']);

    // Validate the guardian_id and relationship
    $check = $conn->prepare("SELECT * FROM pupil_guardian WHERE pupil_id = ? AND guardian_id = ?");
    $check->bind_param("ii", $pupil_id, $guardian_id);
    $check->execute();
    // Check if the guardian is already linked
    // If not, insert the new guardian link
    if ($check->get_result()->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO pupil_guardian (pupil_id, guardian_id, relationship_type) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $pupil_id, $guardian_id, $relationship);
        $stmt->execute();
    }
}

// Prepare the SQL statement to fetch linked guardians
// Use JOIN to get guardian details
$stmt = $conn->prepare("SELECT g.*, pg.relationship_type 
                        FROM pupil_guardian pg 
                        JOIN guardian g ON pg.guardian_id = g.guardian_id 
                        WHERE pg.pupil_id = ?");
// Bind the pupil_id parameter
$stmt->bind_param("i", $pupil_id);
$stmt->execute();
$linked_guardians = $stmt->get_result();

// Prepare the SQL statement to fetch all guardians
// Use ORDER BY to sort by first name
$all_guardians = $conn->query("SELECT guardian_id, g_first_name, g_last_name FROM guardian ORDER BY g_first_name");
?>

<!-- HTML and PHP code for managing guardians -->
<!DOCTYPE html>
<html>
<head>
    <title>Manage Guardians</title>
    <link rel="stylesheet" href="student.css">
</head>
<body>
<div class="container">
    <!-- Return button with a link to the homepage -->
    <h2>Manage Guardians for <?= safe($pupil['p_first_name'] . ' ' . $pupil['p_last_name']) ?></h2>
    

    <h3>Linked Guardians</h3>
    <!-- Display linked guardians -->
    <!-- If no guardians are linked, display a message -->
    <!-- Use the num_rows property to check if there are any linked guardians -->
    <?php if ($linked_guardians->num_rows === 0): ?>
        <p><em>No guardians linked.</em></p>
    <?php else: ?>
        <ul>
        <!-- Loop through each linked guardian and display their details -->
        <?php while ($g = $linked_guardians->fetch_assoc()): ?>
            <li>
                <!-- Display guardian details -->
                <?= safe($g['g_first_name'] . ' ' . $g['g_last_name']) ?>
                <!-- Display relationship type -->
                (<?= safe($g['relationship_type']) ?>)
                <!-- Display guardian contact details -->
                - <a href="?action=guardians&id=<?= $pupil_id ?>&unbind=<?= $g['guardian_id'] ?>"
                     onclick="return confirm('Unbind this guardian?')">Unbind</a>
            </li>
        <?php endwhile; ?>
        </ul>
    <?php endif; ?>

    <h3>Add Guardian Link</h3>
    <!-- Form for adding a new guardian link -->
    <form method="post">
        <select name="guardian_id" required>
            <!-- Dropdown for selecting a guardian -->
            <option value="">Select Guardian</option>
            <!-- Loop through all guardians and display them as options -->
            <!-- Use the fetch_assoc() method to get an associative array of the result -->
            <?php while ($g = $all_guardians->fetch_assoc()): ?>
                <option value="<?= $g['guardian_id'] ?>">
                    <?= safe($g['g_first_name'] . ' ' . $g['g_last_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
        <!-- Input field for relationship type -->
        <input type="text" name="relationship_type" placeholder="e.g., Father / Mother" required>
        <button type="submit">Bind Guardian</button>
    </form>

    <br>
    <a href="student.php?action=detail&id=<?= $pupil_id ?>" class="btn-cancel">Back to Details</a>
</div>
</body>
</html>

<?php endif; // end of guardians ?>

<?php if ($action === 'chart'): ?>

<?php
//Query gender statistics
$gender_data = [];
$res = $conn->query("SELECT p_gender, COUNT(*) AS total FROM pupil GROUP BY p_gender");
while ($row = $res->fetch_assoc()) {
    $gender_data[$row['p_gender']] = $row['total'];
}

// Query statistics of birth years
// Extract the year from the birth date using the YEAR() function
// Calculate the number of students for each year using the COUNT() function
$birth_years = [];
$res = $conn->query("SELECT YEAR(p_birth_date) AS year, COUNT(*) AS total FROM pupil GROUP BY year ORDER BY year");
while ($row = $res->fetch_assoc()) {
    $birth_years[$row['year']] = $row['total'];
}

// Query for age statistics
$ages = [];
// Calculate the age using TIMESTAMPDIFF() function
$res = $conn->query("SELECT TIMESTAMPDIFF(YEAR, p_birth_date, CURDATE()) AS age, COUNT(*) AS total FROM pupil GROUP BY age ORDER BY age");
while ($row = $res->fetch_assoc()) {
    // Store the age and total count in the $ages array
    $ages[$row['age']] = $row['total'];
}
?>

<!-- HTML and PHP code for displaying charts -->
<!-- Include Chart.js library for rendering charts -->
<!DOCTYPE html>
<html>
<head>
    <title>Pupil Statistics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="student.css">
</head>
<body>
<div class="container">
    <h2>Pupil Statistics</h2>

    <div style="width: 50%; margin: auto;">
    <canvas id="genderChart"></canvas>
</div>

<div style="width: 80%; margin: 40px auto;">
    <canvas id="birthYearChart"></canvas>
</div>

<div style="width: 80%; margin: 40px auto;">
    <canvas id="ageChart"></canvas>
</div>

<br>
<a href="student.php" class="btn-cancel">← Back to List</a>

<script>
const genderChart = new Chart(document.getElementById('genderChart'), {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_keys($gender_data)) ?>,
        datasets: [{
            label: 'Gender Distribution',
            data: <?= json_encode(array_values($gender_data)) ?>,
            backgroundColor: ['#36A2EB', '#FF6384']
        }]
    }
});

// Create a bar chart for birth years
const birthYearChart = new Chart(document.getElementById('birthYearChart'), {
    // Set the chart type to 'bar'
    // Use the Chart.js library to create a bar chart
    type: 'bar',
    data: {
        // Set the labels for the x-axis
        labels: <?= json_encode(array_keys($birth_years)) ?>,
        datasets: [{
            label: 'Pupils by Birth Year',
            data: <?= json_encode(array_values($birth_years)) ?>,
            backgroundColor: 'rgb(123, 142, 250)'
        }]
    },
    // Set the chart options
    options: {
        responsive: true,
        plugins: {
            title: { display: true, text: 'Pupils by Birth Year' }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Create a bar chart for ages
const ageChart = new Chart(document.getElementById('ageChart'), {
    type: 'bar',
    // Set the chart type to 'bar'
    data: {
        // Set the labels for the x-axis
        labels: <?= json_encode(array_keys($ages)) ?>,
        datasets: [{
            label: 'Pupils by Age',
            data: <?= json_encode(array_values($ages)) ?>,
            backgroundColor: 'rgb(55, 171, 249)'
        }]
    },
    // Set the chart options
    options: {
        responsive: true,
        plugins: {
            title: { display: true, text: 'Pupils by Age' }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

</body>
</html>

<?php endif; // end of chart ?>

<?php
// All logic done, now close connection
$conn->close();
?>