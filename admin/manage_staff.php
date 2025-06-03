<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$message = "";

// Add or Update Staff
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
    $user_id = $_POST["user_id"];
    $designation = $_POST["designation"];
    $shift = $_POST["shift"];
    $assigned_area = $_POST["assigned_area"];
    $qualification = $_POST["qualification"];
    $joining_date = $_POST["joining_date"];
    $salary = $_POST["salary"];

    if ($id > 0) {
        // Update
        $stmt = $conn->prepare("UPDATE staff SET user_id=?, designation=?, shift=?, assigned_area=?, qualification=?, joining_date=?, salary=? WHERE id=?");
        $stmt->bind_param("isssssdi", $user_id, $designation, $shift, $assigned_area, $qualification, $joining_date, $salary, $id);
        $stmt->execute();
        $message = "Staff record updated.";
    } else {
        // Add
        $stmt = $conn->prepare("INSERT INTO staff (user_id, designation, shift, assigned_area, qualification, joining_date, salary) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssd", $user_id, $designation, $shift, $assigned_area, $qualification, $joining_date, $salary);
        $stmt->execute();
        $message = "Staff added successfully.";
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM staff WHERE id = $id");
    $message = "Staff deleted.";
}

$result = mysqli_query($conn, "SELECT s.*, u.username FROM staff s JOIN users u ON s.user_id = u.id");
$users = mysqli_query($conn, "SELECT id, username FROM users WHERE user_type IN ('staff')");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Staff</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css" />

</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5">
    <h2 class="mb-4">Staff Management</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <button class="btn btn-success mb-3" onclick="addStaff()">Add New Staff</button>

    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Username</th>
            <th>Designation</th>
            <th>Shift</th>
            <th>Assigned Area</th>
            <th>Qualification</th>
            <th>Joining Date</th>
            <th>Salary</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $row['username'] ?></td>
                <td><?= $row['designation'] ?></td>
                <td><?= $row['shift'] ?></td>
                <td><?= $row['assigned_area'] ?></td>
                <td><?= $row['qualification'] ?></td>
                <td><?= $row['joining_date'] ?></td>
                <td><?= $row['salary'] ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick='editStaff(<?= json_encode($row) ?>)'>Edit</button>
                    <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="staffModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form method="post" class="modal-content">
            <input type="hidden" name="staff_id" id="staff_id">
            <div class="modal-header">
                <h5 class="modal-title">Staff Form</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>User</label>
                    <select name="user_id" id="user_id" class="form-control" required>
                        <option value="">Select User</option>
                        <?php mysqli_data_seek($users, 0); while ($u = mysqli_fetch_assoc($users)): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Designation</label>
                    <select name="designation" id="designation" class="form-control" required>
                        <option value="caregiver">Caregiver</option>
                        <option value="medical_staff">Medical Staff</option>
                        <option value="manager">Manager</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Shift</label>
                    <select name="shift" id="shift" class="form-control" required>
                        <option value="morning">Morning</option>
                        <option value="evening">Evening</option>
                        <option value="night">Night</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Assigned Area</label>
                    <input type="text" name="assigned_area" id="assigned_area" class="form-control">
                </div>
                <div class="form-group">
                    <label>Qualification</label>
                    <input type="text" name="qualification" id="qualification" class="form-control">
                </div>
                <div class="form-group">
                    <label>Joining Date</label>
                    <input type="date" name="joining_date" id="joining_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Salary</label>
                    <input type="number" step="0.01" name="salary" id="salary" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Save</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function addStaff() {
    document.querySelector('form').reset();
    document.getElementById("staff_id").value = '';
    $('#staffModal').modal('show');
}

function editStaff(data) {
    document.getElementById("staff_id").value = data.id;
    document.getElementById("user_id").value = data.user_id;
    document.getElementById("designation").value = data.designation;
    document.getElementById("shift").value = data.shift;
    document.getElementById("assigned_area").value = data.assigned_area;
    document.getElementById("qualification").value = data.qualification;
    document.getElementById("joining_date").value = data.joining_date;
    document.getElementById("salary").value = data.salary;
    $('#staffModal').modal('show');
}
</script>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
