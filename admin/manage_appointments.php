<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$message = "";

// Update status
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['appointment_id'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE appointments SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $appointment_id);
    $stmt->execute();
    $message = "Appointment status updated.";
}

// Date filter
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$where = "";

if ($from && $to) {
    $where = "WHERE DATE(appointment_date) BETWEEN '$from' AND '$to'";
}

// Fetch appointments
$appointments = mysqli_query($conn, "
    SELECT a.*, em.first_name AS elderly_name, fm.username AS family_name
    FROM appointments a
    JOIN elderly_members em ON a.elderly_id = em.member_id
    JOIN users fm ON a.family_member_id = fm.id
    $where
    ORDER BY a.appointment_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Appointments</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5">
    <h2>Appointments</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="get" class="form-inline mb-3">
        <label class="mr-2">From</label>
        <input type="date" name="from" class="form-control mr-2" value="<?= htmlspecialchars($from) ?>">
        <label class="mr-2">To</label>
        <input type="date" name="to" class="form-control mr-2" value="<?= htmlspecialchars($to) ?>">
        <button class="btn btn-primary">Filter</button>
        <button type="button" onclick="window.print()" class="btn btn-secondary ml-2">Print</button>
    </form>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Elderly</th>
                <th>Family Member</th>
                <th>Type</th>
                <th>Status</th>
                <th>Notes</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($appointments)): ?>
                <tr>
                    <td><?= $row['appointment_date'] ?></td>
                    <td><?= htmlspecialchars($row['elderly_name']) ?></td>
                    <td><?= htmlspecialchars($row['family_name']) ?></td>
                    <td><?= $row['type'] ?></td>
                    <td><?= $row['status'] ?></td>
                    <td><?= nl2br(htmlspecialchars($row['notes'])) ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick='editAppointment(<?= json_encode($row) ?>)'>Update</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="appointmentModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <input type="hidden" name="appointment_id" id="appointment_id">
            <div class="modal-header">
                <h5 class="modal-title">Update Appointment Status</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="completed">Completed</option>
                        <option value="rejected">Rejected</option>
                    </select>
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
function editAppointment(data) {
    document.getElementById("appointment_id").value = data.id;
    document.getElementById("status").value = data.status;
    $('#appointmentModal').modal('show');
}
</script>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
