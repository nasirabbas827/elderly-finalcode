<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$message = "";

// Update status
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['report_id'])) {
    $report_id = intval($_POST['report_id']);
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE emergency_reports SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $report_id);
    $stmt->execute();
    $message = "Report status updated.";
}

// Date filter
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$where = "";

if ($from && $to) {
    $where = "WHERE DATE(report_time) BETWEEN '$from' AND '$to'";
}

// Fetch reports
$reports = mysqli_query($conn, "
    SELECT er.*, em.first_name AS elderly_name, u.username AS reporter_name
    FROM emergency_reports er
    JOIN elderly_members em ON er.elderly_id = em.member_id
    JOIN users u ON er.reported_by = u.id
    $where
    ORDER BY er.report_time DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Emergency Reports</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5">
    <h2>Emergency Reports</h2>

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
                <th>Reported By</th>
                <th>Description</th>
                <th>Admin Notified</th>
                <th>Family Notified</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($r = mysqli_fetch_assoc($reports)): ?>
                <tr>
                    <td><?= $r['report_time'] ?></td>
                    <td><?= htmlspecialchars($r['elderly_name']) ?></td>
                    <td><?= htmlspecialchars($r['reporter_name']) ?></td>
                    <td><?= htmlspecialchars($r['description']) ?></td>
                    <td><?= $r['notified_admin'] ? 'Yes' : 'No' ?></td>
                    <td><?= $r['notified_family'] ? 'Yes' : 'No' ?></td>
                    <td><?= $r['status'] ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick='editStatus(<?= json_encode($r) ?>)'>Update</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <input type="hidden" name="report_id" id="report_id">
            <div class="modal-header">
                <h5 class="modal-title">Update Status</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="open">Open</option>
                        <option value="resolved">Resolved</option>
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
function editStatus(data) {
    document.getElementById("report_id").value = data.id;
    document.getElementById("status").value = data.status;
    $('#statusModal').modal('show');
}
</script>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
