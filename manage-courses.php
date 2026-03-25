<?php
// Database configuration
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "programmerslab_db";

// Connect to database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Handle actions
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        // Get all courses
        $result = $conn->query("SELECT * FROM courses ORDER BY category, course_name");
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Manage Courses - Programmers Lab</title>
            <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <div class="container mt-5">
                <h2>Manage Courses</h2>
                <a href="?action=add" class="btn btn-primary mb-3">Add New Course</a>
                
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Course Name</th>
                            <th>Category</th>
                            <th>Duration</th>
                            <th>Fee</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['course_name']) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td><?= htmlspecialchars($row['duration'] ?? 'N/A') ?></td>
                            <td><?= $row['fee'] ? 'Rs. ' . number_format($row['fee']) : 'N/A' ?></td>
                            <td><?= $row['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>' ?></td>
                            <td>
                                <a href="?action=edit&id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </body>
        </html>
        <?php
        break;

    case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $course_name = $_POST['course_name'];
            $course_slug = strtolower(str_replace(' ', '-', $_POST['course_name']));
            $description = $_POST['description'];
            $duration = $_POST['duration'];
            $fee = $_POST['fee'];
            $category = $_POST['category'];
            
            $stmt = $conn->prepare("INSERT INTO courses (course_name, course_slug, description, duration, fee, category) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssds", $course_name, $course_slug, $description, $duration, $fee, $category);
            
            if ($stmt->execute()) {
                header("Location: manage-courses.php?action=list");
                exit();
            } else {
                echo "Error: " . $stmt->error;
            }
        }
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Add Course</title>
            <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <div class="container mt-5">
                <h2>Add New Course</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Course Name</label>
                        <input type="text" name="course_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Duration (e.g., 3 months)</label>
                        <input type="text" name="duration" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Fee (Rs.)</label>
                        <input type="number" name="fee" class="form-control" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" class="form-control" required>
                            <option value="Web Development">Web Development</option>
                            <option value="Mobile Development">Mobile Development</option>
                            <option value="Programming">Programming</option>
                            <option value="Database">Database</option>
                            <option value="Digital Marketing">Digital Marketing</option>
                            <option value="Design">Design</option>
                            <option value="IT Fundamentals">IT Fundamentals</option>
                            <option value="Testing">Testing</option>
                            <option value="Career Development">Career Development</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Course</button>
                    <a href="?action=list" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </body>
        </html>
        <?php
        break;

    case 'edit':
        $id = $_GET['id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $course_name = $_POST['course_name'];
            $description = $_POST['description'];
            $duration = $_POST['duration'];
            $fee = $_POST['fee'];
            $category = $_POST['category'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            $stmt = $conn->prepare("UPDATE courses SET course_name=?, description=?, duration=?, fee=?, category=?, is_active=? WHERE id=?");
            $stmt->bind_param("sssdsii", $course_name, $description, $duration, $fee, $category, $is_active, $id);
            
            if ($stmt->execute()) {
                header("Location: manage-courses.php?action=list");
                exit();
            }
        }
        
        $result = $conn->query("SELECT * FROM courses WHERE id=$id");
        $course = $result->fetch_assoc();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Edit Course</title>
            <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <div class="container mt-5">
                <h2>Edit Course</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Course Name</label>
                        <input type="text" name="course_name" class="form-control" value="<?= htmlspecialchars($course['course_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($course['description']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Duration</label>
                        <input type="text" name="duration" class="form-control" value="<?= htmlspecialchars($course['duration']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Fee (Rs.)</label>
                        <input type="number" name="fee" class="form-control" step="0.01" value="<?= $course['fee'] ?>">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" class="form-control" required>
                            <option value="Web Development" <?= $course['category'] == 'Web Development' ? 'selected' : '' ?>>Web Development</option>
                            <option value="Mobile Development" <?= $course['category'] == 'Mobile Development' ? 'selected' : '' ?>>Mobile Development</option>
                            <option value="Programming" <?= $course['category'] == 'Programming' ? 'selected' : '' ?>>Programming</option>
                            <option value="Database" <?= $course['category'] == 'Database' ? 'selected' : '' ?>>Database</option>
                            <option value="Digital Marketing" <?= $course['category'] == 'Digital Marketing' ? 'selected' : '' ?>>Digital Marketing</option>
                            <option value="Design" <?= $course['category'] == 'Design' ? 'selected' : '' ?>>Design</option>
                            <option value="IT Fundamentals" <?= $course['category'] == 'IT Fundamentals' ? 'selected' : '' ?>>IT Fundamentals</option>
                            <option value="Testing" <?= $course['category'] == 'Testing' ? 'selected' : '' ?>>Testing</option>
                            <option value="Career Development" <?= $course['category'] == 'Career Development' ? 'selected' : '' ?>>Career Development</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" <?= $course['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Course</button>
                    <a href="?action=list" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </body>
        </html>
        <?php
        break;

    case 'delete':
        $id = $_GET['id'];
        $conn->query("DELETE FROM courses WHERE id=$id");
        header("Location: manage-courses.php?action=list");
        exit();
        break;
}

$conn->close();
