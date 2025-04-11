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

// Handle search
// Initialize an empty array to store search results
$search_results = [];

if (isset($_GET['search'])) {
    // Check if search request is triggered via GET method
    // Get search type and term from GET request
    $search_type = $_GET['search_type'];
    $search_input = $_GET['search_term'];

    // Define valid search columns to avoid SQL injection and ensure valid mapping
    // Map search types to actual column names in the database
    $valid_columns = [
        'title' => 'book_name',
        'author' => 'book_author',
        'isbn' => 'isbn',
        'id' => 'book_id' 
    ];
    
    if (array_key_exists($search_type, $valid_columns)) {
        // Check if the search type is valid and map it to actual database column
        $column = $valid_columns[$search_type];

        if ($search_type === 'id') {
            // Perform exact match for book ID and convert input to integer
            $search_id = intval($search_input); // Prevent SQL injection by forcing integer
            $stmt = $conn->prepare("SELECT * FROM library_book WHERE $column = ? ORDER BY book_name");
            $stmt->bind_param("i", $search_id); // Bind parameter as integer
        } else {
            // Perform partial match for other search types (title, author, isbn)
            $search_term = "%" . $search_input . "%"; // Add wildcards for LIKE query
            $stmt = $conn->prepare("SELECT * FROM library_book WHERE $column LIKE ? ORDER BY book_name");
            $stmt->bind_param("s", $search_term); // Bind parameter as string
        }

        $stmt->execute(); // Execute the prepared statement
        $search_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // Fetch all matching records as associative array
    }
}

// Handle new book addition
if (isset($_POST['add_book'])) {
    // Check if the form to add a new book has been submitted

    $book_name = htmlspecialchars($_POST['book_name']); // Sanitize book name input
    $book_author = htmlspecialchars($_POST['book_author']); // Sanitize author input
    $isbn = htmlspecialchars($_POST['isbn']); // Sanitize ISBN input
    $copies = filter_input(INPUT_POST, 'copies', FILTER_VALIDATE_INT, 
                         ['options' => ['min_range' => 1]]); // Validate that number of copies is at least 1

    $error = null; // Initialize error variable

    if (empty($book_name) || empty($book_author) || empty($isbn)) {
        // Check if any required fields are empty
        $error = "All fields are required!";
    } elseif (!$copies) {
        // Check if the number of copies is invalid
        $error = "Invalid number of copies!";
    } else {
        // Check if a book with the same ISBN already exists
        $check_stmt = $conn->prepare("SELECT book_id FROM library_book WHERE isbn = ?");
        $check_stmt->bind_param("s", $isbn); // Bind ISBN as string
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            // If the book already exists, show error
            $error = "Book with this ISBN already exists!";
        } else {
            // Insert the new book into the database
            $insert_stmt = $conn->prepare("INSERT INTO library_book 
                (book_name, book_author, isbn, total_copies, available_copies)
                VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("sssii", $book_name, $book_author, $isbn, $copies, $copies); // Bind values

            if ($insert_stmt->execute()) {
                // If insertion successful, set success message
                $success = "Book added successfully!";
            } else {
                // Handle any insertion errors
                $error = "Error adding book: " . $conn->error;
            }
        }
    }
}

// Borrow Book
if (isset($_POST['borrow'])) {
    // Check if borrow action has been triggered

    $book_id = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT); // Validate book ID
    $pupil_id = filter_input(INPUT_POST, 'pupil_id', FILTER_VALIDATE_INT); // Validate pupil ID

    $conn->begin_transaction(); // Start a database transaction for consistency

    try {
        // Check if there are available copies using a row-level lock
        $check_stmt = $conn->prepare("SELECT available_copies FROM library_book WHERE book_id = ? FOR UPDATE");
        $check_stmt->bind_param("i", $book_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result()->fetch_assoc();
        
        if ($result['available_copies'] > 0) {
            // If book is available, decrease stock by one
            $update_stmt = $conn->prepare("UPDATE library_book SET available_copies = available_copies - 1 WHERE book_id = ?");
            $update_stmt->bind_param("i", $book_id);
            $update_stmt->execute();

            // Insert new loan record into book_loan table
            $insert_stmt = $conn->prepare("INSERT INTO book_loan (pupil_id, book_id, book_status, borrowed_date, due_date) 
                                         VALUES (?, ?, 'Borrowed', CURDATE(), CURDATE() + INTERVAL 14 DAY)");
            $insert_stmt->bind_param("ii", $pupil_id, $book_id); // Bind IDs

            $insert_stmt->execute(); // Execute loan insertion
            $conn->commit(); // Commit the transaction

            $success = "Book borrowed successfully!"; // Set success message
        } else {
            // If no copies available, show error
            $error = "No available copies!";
        }
    } catch (Exception $e) {
        $conn->rollback(); // Rollback transaction in case of any failure
        $error = "Operation failed: " . $e->getMessage(); // Capture exception error
    }
}

// Return Book
if (isset($_GET['return'])) {
    // Check if return action is triggered via GET request

    $loan_id = filter_input(INPUT_GET, 'return', FILTER_VALIDATE_INT); // Validate the loan ID

    try {
        // Retrieve the corresponding book ID using loan ID
        $get_book = $conn->prepare("SELECT book_id FROM book_loan WHERE loan_id = ?");
        $get_book->bind_param("i", $loan_id);
        $get_book->execute();
        $book_id = $get_book->get_result()->fetch_assoc()['book_id']; // Get the book ID from result

        // Mark the book as returned and update return date
        $update_loan = $conn->prepare("UPDATE book_loan SET 
                                    book_status = 'Returned',
                                    returned_date = CURDATE()
                                    WHERE loan_id = ?");
        $update_loan->bind_param("i", $loan_id);
        $update_loan->execute();

        // Increase the available copy count in library_book
        $update_book = $conn->prepare("UPDATE library_book 
                                     SET available_copies = available_copies + 1 
                                     WHERE book_id = ?");
        $update_book->bind_param("i", $book_id);
        $update_book->execute();

        $success = "Book returned successfully!"; // Set success message after return
    } catch (Exception $e) {
        // Handle any errors during the return process
        $error = "Return failed: " . $e->getMessage();
    }
}
?>


<!-- HTML Section-->
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Set character encoding and responsive viewport -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Library Management</title>
    
    <!-- Link external stylesheet for layout and styling -->
    <link rel="stylesheet" href="library.css">
</head>
<body>
    <!-- Navigation link to return to homepage -->
    <a href="homepage.php" class="home-button">
        <span class="desktop-text">Return to Home</span>
        <span class="mobile-text">Home</span>
    </a>

    <!-- Main heading of the page -->
    <h1>School Library Management</h1>
    
    <!-- Display success or error message if set -->
    <?php if(isset($success)): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php elseif(isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="container">
        <!-- Search Section -->
        <section class="search-section">
            <h2>Search Books</h2>

            <!-- Book search form with method GET -->
            <form class="search-form" method="get">
                
                <!-- Search type dropdown selection -->
                <select name="search_type" style="padding: 8px; width: 80px;">
                    <!-- Option for each searchable field, retaining user selection -->
                    <option value="title" <?= ($_GET['search_type'] ?? '') === 'title' ? 'selected' : '' ?>>Title</option>
                    <option value="author" <?= ($_GET['search_type'] ?? '') === 'author' ? 'selected' : '' ?>>Author</option>
                    <option value="id" <?= ($_GET['search_type'] ?? '') === 'id' ? 'selected' : '' ?>>ID</option>
                    <option value="isbn" <?= ($_GET['search_type'] ?? '') === 'isbn' ? 'selected' : '' ?>>ISBN</option>
                </select>

                <!-- Input for search term and submit button -->
                <input type="text" name="search_term" 
                       placeholder="Search term..." 
                       value="<?= htmlspecialchars($_GET['search_term'] ?? '') ?>">
                <button type="submit" name="search" class="search_button">Search</button>
                <a href="?" class="btn-cancel">Cancel</a> <!-- Link to reset form -->
            </form>
            
            <!-- Display search results if any found -->
            <?php if(!empty($search_results)): ?>
            <div class="search-results">
                <h3>Search Results (<?= count($search_results) ?>)</h3>
                
                <!-- Table displaying found books -->
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Available</th>
                    </tr>
                    <?php foreach($search_results as $book): ?>
                    <tr>
                        <!-- Print book data in each table cell -->
                        <td><?= $book['book_id'] ?></td>
                        <td><?= htmlspecialchars($book['book_name']) ?></td>
                        <td><?= htmlspecialchars($book['book_author']) ?></td>
                        <td><?= htmlspecialchars($book['isbn']) ?></td>
                        <td><?= $book['available_copies'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endif; ?>
        </section>

        <!-- Book List -->
        <section>
            <h2>Book List</h2>

            <!-- Table for displaying all books in the library -->
            <table class="styled-table">
                <tr>
                    <th>ID</th>
                    <th>Book Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>ISBN</th>
                    <th>Total</th>
                    <th>Available</th>
                    <th>Action</th>
                </tr>

                <!-- Fetch and display all books from the database -->
                <?php
                $books = $conn->query("SELECT * FROM library_book");
                if (!$books) {
                    die("Books query failed: " . $conn->error); // Handle query error
                }
                
                while ($book = $books->fetch_assoc()):
                ?>
                <tr>
                    <!-- Output each book field -->
                    <td><?= $book['book_id'] ?></td>
                    <td><?= htmlspecialchars($book['book_name']) ?></td>
                    <td><?= htmlspecialchars($book['book_author']) ?></td>
                    <td><?= htmlspecialchars($book['book_category']) ?></td>
                    <td><?= htmlspecialchars($book['isbn']) ?></td>
                    <td><?= $book['total_copies'] ?></td>
                    <td><?= $book['available_copies'] ?></td>
                    <td>
                        <!-- Form to borrow a book by providing student ID -->
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
                            <input type="number" name="pupil_id" placeholder="Student ID" min="1" required>
                            <button type="submit" name="borrow">Borrow</button>
                            <a href="?" class="btn-cancel">Cancel</a>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </section>

        <!-- Add New Book Section -->
        <section class="add-book">
            <h2>Add New Book</h2>

            <!-- Form to add a new book with all required inputs -->
            <form class="add-book-form" method="post">
                <div class="form-row">
                    <input type="text" name="book_name" placeholder="Book Title" required>
                </div>
                <div class="form-row">
                    <input type="text" name="book_author" placeholder="Author" required>
                </div>
                <div class="form-row">
                    <input type="text" name="book_category" placeholder="Category" required>
                </div>
                <div class="form-row">
                    <!-- Input with pattern to ensure valid ISBN format -->
                    <input type="text" name="isbn" placeholder="ISBN" required 
                           pattern="\d{10,13}" title="10-13 digit ISBN">
                </div>
                <div class="form-row">
                    <!-- Input for number of copies with validation -->
                    <input type="number" name="copies" placeholder="Number of copies" 
                           min="1" required>
                </div>
                <button type="submit" name="add_book" class="add-button">Add Book</button>
                <a href="?" class="btn-cancel">Cancel</a>
            </form>
        </section>

        <!-- Active Loans -->
        <section class="loan-section">
            <h2>Active Loans</h2>

            <!-- Table showing books currently on loan -->
            <table>
                <tr>
                    <th>Student ID</th>
                    <th>Book ID</th>
                    <th>Due Date</th>
                    <th>Action</th>
                </tr>

                <!-- Fetch all borrowed books from database -->
                <?php
                $loans = $conn->query("SELECT * FROM book_loan WHERE book_status = 'Borrowed'");
                while ($loan = $loans->fetch_assoc()):
                ?>
                <tr>
                    <!-- Display loan data and return action -->
                    <td><?= $loan['pupil_id'] ?></td>
                    <td><?= $loan['book_id'] ?></td>
                    <td><?= $loan['due_date'] ?></td>
                    <td>
                        <!-- Link to return book with confirmation prompt -->
                        <a href="?return=<?= $loan['loan_id'] ?>" 
                           onclick="return confirm('Confirm return?')">Return</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </section>
    </div>

    <!-- Loan History -->
    <div class="history-section">
    <section class="history-section">
        <h2>Loan History</h2>

        <!-- Display full loan history with JOIN queries -->
        <table class="styled-table">
            <tr>
                <th>Loan ID</th>
                <th>Book Title</th>
                <th>Student Name</th>
                <th>Status</th>
                <th>Borrowed</th>
                <th>Due</th>
                <th>Returned</th>
            </tr>
            <?php
                // Query joining books, loans, and pupil names
                $history = $conn->query("SELECT 
                    bl.loan_id, lb.book_name, 
                    CONCAT(s.p_first_name, ' ', s.p_last_name) AS student_name,
                    bl.book_status, bl.borrowed_date, bl.due_date, bl.returned_date
                    FROM book_loan bl
                    JOIN library_book lb ON bl.book_id = lb.book_id
                    LEFT JOIN pupil s ON bl.pupil_id = s.pupil_id
                    ORDER BY bl.borrowed_date DESC");

                if ($history && $history->num_rows > 0):
                    while ($row = $history->fetch_assoc()):
            ?>
            <tr>
                <!-- Output loan details in table format -->
                <td><?= $row['loan_id'] ?></td>
                <td><?= htmlspecialchars($row['book_name']) ?></td>
                <td><?= $row['student_name'] ?: '<em>Unknown</em>' ?></td>
                <td style="color: <?= $row['book_status'] === 'Borrowed' ? '#dc3545' : '#28a745' ?>">
                    <?= htmlspecialchars($row['book_status']) ?>
                </td>
                <td><?= $row['borrowed_date'] ?></td>
                <td><?= $row['due_date'] ?></td>
                <td>
                    <!-- Show returned date or pending if not returned -->
                    <?= $row['returned_date'] ? $row['returned_date'] : '<em style="color:#777;">Pending</em>' ?>
                </td>
            </tr>
            <?php endwhile; else: ?>
                <!-- Message if no loan history exists -->
                <tr><td colspan="7" style="text-align:center; color:#777;">No loan records found.</td></tr>
            <?php endif; ?>
        </table>
    </section>
    </div>

</body>
</html>
<!-- End of HTML Section -->

<!-- Close the database connection -->
<?php
// All logic done, now close connection
$conn->close();
?>