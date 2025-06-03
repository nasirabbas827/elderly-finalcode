<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$filter = "";
$params = [];
$start_date = $end_date = "";

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['from']) && isset($_GET['to'])) {
    $start_date = $_GET['from'];
    $end_date = $_GET['to'];
    $filter = "WHERE donation_date BETWEEN ? AND ?";
    $params = [$start_date, $end_date];
}

// Update Acknowledgement
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['donation_id'])) {
    $donation_id = $_POST['donation_id'];
    $acknowledged = $_POST['acknowledged'];

    $stmt = $conn->prepare("UPDATE donations SET acknowledged=? WHERE donation_id=?");
    $stmt->bind_param("si", $acknowledged, $donation_id);
    $stmt->execute();
    $message = "Status updated.";
}

$query = "SELECT * FROM donations $filter ORDER BY donation_date DESC";
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param("ss", ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Donations</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

    <style>
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
<?php include('admin_navbar.php'); ?>
<div class="container mt-5">
    <h2>Manage Donations</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <form method="get" class="form-inline no-print mb-3">
        <label class="mr-2">From:</label>
        <input type="date" name="from" class="form-control mr-2" value="<?= htmlspecialchars($start_date) ?>" required>
        <label class="mr-2">To:</label>
        <input type="date" name="to" class="form-control mr-2" value="<?= htmlspecialchars($end_date) ?>" required>
        <button class="btn btn-primary">Filter</button>
        <button type="button" onclick="window.print()" class="btn btn-secondary ml-2">Print Report</button>
    </form>

    <table class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>ID</th>
            <th>Donor ID</th>
            <th>Amount</th>
            <th>Type</th>
            <th>Purpose</th>
            <th>Reference ID</th>
            <th>Transaction</th>
            <th>Date</th>
            <th>Status</th>
            <th class="no-print">Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['donation_id'] ?></td>
                <td><?= $row['donor_id'] ?></td>
                <td><?= $row['amount'] ?></td>
                <td><?= htmlspecialchars($row['donation_type']) ?></td>
                <td><?= htmlspecialchars($row['purpose']) ?></td>
                <td><?= $row['payment_reference_id'] ?? 'N/A' ?></td>
                <td>
                    <?php if ($row['transaction_picture']): ?>
                        <a href="../images/<?= $row['transaction_picture'] ?>" target="_blank">View</a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td><?= $row['donation_date'] ?></td>
                <td><?= $row['acknowledged'] ?></td>
                <td class="no-print">
                    <button class="btn btn-sm btn-warning" onclick='editStatus(<?= json_encode($row) ?>)'>Update</button>
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
            <input type="hidden" name="donation_id" id="donation_id">
            <div class="modal-header">
                <h5 class="modal-title">Update Acknowledgement</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <label>Status</label>
                <select name="acknowledged" id="acknowledged" class="form-control" required>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="not approved">Not Approved</option>
                </select>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success">Save</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editStatus(data) {
    document.getElementById("donation_id").value = data.donation_id;
    document.getElementById("acknowledged").value = data.acknowledged;
    $('#statusModal').modal('show');
}
</script>
</body>
</html>
