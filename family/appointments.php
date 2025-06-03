<?php
session_start();
include('config.php');

if ($_SESSION["user_type"] !== 'family_member') {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION["id"];
$message = "";

// Add/Edit Appointment
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $elderly_id = $_POST["elderly_id"];
    $appointment_date = $_POST["appointment_date"];
    $type = $_POST["type"];
    $notes = $_POST["notes"];
    $status = 'pending';

    if ($id > 0) {
        $check = mysqli_query($conn, "SELECT * FROM appointments WHERE id = $id AND status = 'pending' AND family_member_id = $user_id");
        if (mysqli_num_rows($check)) {
            $stmt = mysqli_prepare($conn, "UPDATE appointments SET elderly_id=?, appointment_date=?, type=?, notes=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "isssi", $elderly_id, $appointment_date, $type, $notes, $id);
            mysqli_stmt_execute($stmt);
            $message = "Appointment updated.";
        }
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO appointments (elderly_id, family_member_id, appointment_date, type, notes, status) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iissss", $elderly_id, $user_id, $appointment_date, $type, $notes, $status);
        mysqli_stmt_execute($stmt);
        $message = "Appointment requested.";
    }
}

// Delete Appointment
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM appointments WHERE id = $id AND family_member_id = $user_id AND status = 'pending'");
    $message = "Appointment deleted.";
}

// Get user’s elderly members
$members = mysqli_query($conn, "SELECT member_id, first_name, last_name FROM elderly_members WHERE family_member_id = $user_id");

// Fetch appointments
$appointments = mysqli_query($conn, "
    SELECT a.*, CONCAT(e.first_name, ' ', e.last_name) AS elderly_name 
    FROM appointments a
    JOIN elderly_members e ON a.elderly_id = e.member_id
    WHERE a.family_member_id = $user_id
    ORDER BY appointment_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Appointments</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css" />

</head>
<body>
<?php include('navbar.php'); ?>
<div class="container mt-5">
    <h2 class="mb-4">Appointments</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <button class="btn btn-success mb-3" data-toggle="modal" data-target="#appointmentModal" onclick="addAppointment()">Request Appointment</button>

    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Elderly</th>
            <th>Date</th>
            <th>Type</th>
            <th>Status</th>
            <th>Notes</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($appointments)): ?>
            <tr>
                <td><?= htmlspecialchars($row['elderly_name']) ?></td>
                <td><?= $row['appointment_date'] ?></td>
                <td><?= ucfirst($row['type']) ?></td>
                <td><?= ucfirst($row['status']) ?></td>
                <td><?= htmlspecialchars($row['notes']) ?></td>
                <td>
                    <?php if ($row['status'] === 'pending'): ?>
                        <button class="btn btn-sm btn-primary" onclick='editAppointment(<?= json_encode($row) ?>)'>Edit</button>
                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                    <?php else: ?>
                        <span class="text-muted">No Action</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="appointmentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form method="post" class="modal-content">
            <input type="hidden" name="id" id="id">
            <div class="modal-header">
                <h5 class="modal-title">Appointment Form</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Elderly Member</label>
                    <select name="elderly_id" id="elderly_id" class="form-control" required>
                        <option value="">Select</option>
                        <?php while ($m = mysqli_fetch_assoc($members)): ?>
                            <option value="<?= $m['member_id'] ?>"><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Appointment Date</label>
                    <input type="datetime-local" name="appointment_date" id="appointment_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" id="type" class="form-control" required>
                        <option value="physical">Physical</option>
                        <option value="virtual">Virtual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" id="notes" class="form-control"></textarea>
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
function addAppointment() {
    document.querySelector('form').reset();
    document.getElementById("id").value = '';
}

function editAppointment(data) {
    document.getElementById("id").value = data.id;
    document.getElementById("elderly_id").value = data.elderly_id;
    document.getElementById("appointment_date").value = data.appointment_date.replace(" ", "T");
    document.getElementById("type").value = data.type;
    document.getElementById("notes").value = data.notes;
    $('#appointmentModal').modal('show');
}
</script>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
