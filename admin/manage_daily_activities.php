<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$message = "";

// Insert/Update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $activity_id = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;
    $elderly_id = $_POST["elderly_id"];
    $date = $_POST["date"];
    $activity_type = $_POST["activity_type"];
    $description = $_POST["description"];
    $start_time = $_POST["start_time"];
    $end_time = $_POST["end_time"];
    $staff_id = !empty($_POST["staff_id"]) ? $_POST["staff_id"] : null;
    $status = $_POST["status"];

    if ($activity_id > 0) {
        $stmt = $conn->prepare("UPDATE daily_activities SET elderly_id=?, date=?, activity_type=?, description=?, start_time=?, end_time=?, staff_id=?, status=? WHERE activity_id=?");
        $stmt->bind_param("isssssssi", $elderly_id, $date, $activity_type, $description, $start_time, $end_time, $staff_id, $status, $activity_id);
        $stmt->execute();
        $message = "Activity updated.";
    } else {
        $stmt = $conn->prepare("INSERT INTO daily_activities (elderly_id, date, activity_type, description, start_time, end_time, staff_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $elderly_id, $date, $activity_type, $description, $start_time, $end_time, $staff_id, $status);
        $stmt->execute();
        $message = "Activity added.";
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM daily_activities WHERE activity_id = $id");
    $message = "Activity deleted.";
}

// Fetch
$records = mysqli_query($conn, "
    SELECT da.*, em.first_name AS elderly_name, u.username AS staff_name
    FROM daily_activities da
    JOIN elderly_members em ON da.elderly_id = em.member_id
    LEFT JOIN users u ON da.staff_id = u.id
    ORDER BY da.date DESC, da.start_time
");

$elderly = mysqli_query($conn, "SELECT member_id, first_name FROM elderly_members");
$staff = mysqli_query($conn, "SELECT id, username FROM users WHERE user_type='staff'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Daily Activities</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5">
    <h2>Daily Activities</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <button class="btn btn-success mb-3" onclick="addActivity()">Add New Activity</button>

    <table class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Date</th>
            <th>Elderly</th>
            <th>Type</th>
            <th>Description</th>
            <th>Time</th>
            <th>Staff</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($records)): ?>
            <tr>
                <td><?= $row['date'] ?></td>
                <td><?= htmlspecialchars($row['elderly_name']) ?></td>
                <td><?= $row['activity_type'] ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td><?= $row['start_time'] ?> - <?= $row['end_time'] ?></td>
                <td><?= $row['staff_name'] ?? 'N/A' ?></td>
                <td><?= $row['status'] ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick='editActivity(<?= json_encode($row) ?>)'>Edit</button>
                    <a href="?delete=<?= $row['activity_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this activity?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="activityModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <input type="hidden" name="activity_id" id="activity_id">
            <div class="modal-header">
                <h5 class="modal-title">Daily Activity</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body row">
                <div class="form-group col-md-6">
                    <label>Elderly Member</label>
                    <select name="elderly_id" id="elderly_id" class="form-control" required>
                        <option value="">Select</option>
                        <?php mysqli_data_seek($elderly, 0); while ($e = mysqli_fetch_assoc($elderly)): ?>
                            <option value="<?= $e['member_id'] ?>"><?= htmlspecialchars($e['first_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Staff (optional)</label>
                    <select name="staff_id" id="staff_id" class="form-control">
                        <option value="">None</option>
                        <?php mysqli_data_seek($staff, 0); while ($s = mysqli_fetch_assoc($staff)): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['username']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Date</label>
                    <input type="date" name="date" id="date" class="form-control" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Start Time</label>
                    <input type="time" name="start_time" id="start_time" class="form-control">
                </div>
                <div class="form-group col-md-4">
                    <label>End Time</label>
                    <input type="time" name="end_time" id="end_time" class="form-control">
                </div>
                <div class="form-group col-md-6">
                    <label>Activity Type</label>
                    <select name="activity_type" id="activity_type" class="form-control" required>
                        <option value="meal">Meal</option>
                        <option value="medication">Medication</option>
                        <option value="exercise">Exercise</option>
                        <option value="event">Event</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Status</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="skipped">Skipped</option>
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label>Description</label>
                    <textarea name="description" id="description" class="form-control" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success">Save</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function addActivity() {
    document.querySelector('form').reset();
    document.getElementById("activity_id").value = '';
    $('#activityModal').modal('show');
}

function editActivity(data) {
    document.getElementById("activity_id").value = data.activity_id;
    document.getElementById("elderly_id").value = data.elderly_id;
    document.getElementById("staff_id").value = data.staff_id ?? '';
    document.getElementById("date").value = data.date;
    document.getElementById("activity_type").value = data.activity_type;
    document.getElementById("start_time").value = data.start_time;
    document.getElementById("end_time").value = data.end_time;
    document.getElementById("description").value = data.description;
    document.getElementById("status").value = data.status;
    $('#activityModal').modal('show');
}
</script>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
