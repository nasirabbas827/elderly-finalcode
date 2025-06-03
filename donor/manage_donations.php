<?php
session_start();
include('config.php');

if ($_SESSION["user_type"] !== 'donor') {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION["id"];

$message = "";

// Handle Add/Edit Donation
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $donation_id = isset($_POST['donation_id']) ? intval($_POST['donation_id']) : 0;
    $amount = $_POST['amount'];
    $donation_date = date('Y-m-d'); // always today
    $donation_type = $_POST['donation_type'];
    $payment_reference_id = !empty($_POST['payment_reference_id']) ? intval($_POST['payment_reference_id']) : null;
    $purpose = $_POST['purpose'];
    $acknowledged = 'pending'; // default status on insert and cannot be changed by donor

    // Handle file upload if donation_type is online
    $transaction_picture = null;
    if ($donation_type === 'online' && isset($_FILES['transaction_picture']) && $_FILES['transaction_picture']['error'] == 0) {
        $ext = pathinfo($_FILES['transaction_picture']['name'], PATHINFO_EXTENSION);
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        if (in_array(strtolower($ext), $allowed_ext)) {
            $new_filename = uniqid('txn_') . '.' . $ext;
            $upload_dir = __DIR__ . '/../images/'; // changed to images folder
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            move_uploaded_file($_FILES['transaction_picture']['tmp_name'], $upload_dir . $new_filename);
            $transaction_picture = $new_filename;
        }
    }

    if ($donation_id > 0) {
        // Update only if status is pending and donation belongs to user
        $params = [$amount, $donation_type, $payment_reference_id, $purpose, $donation_id, $user_id];
        $sql = "UPDATE donations SET amount=?, donation_type=?, payment_reference_id=?, purpose=?";
        if ($transaction_picture) {
            $sql .= ", transaction_picture=?";
            $params[] = $transaction_picture;
        }
        $sql .= " WHERE donation_id=? AND donor_id=? AND acknowledged='pending'";

        $stmt = $conn->prepare($sql);

        if ($transaction_picture) {
            $stmt->bind_param("dsi ssii", $amount, $donation_type, $payment_reference_id, $purpose, $transaction_picture, $donation_id, $user_id);
        } else {
            $stmt->bind_param("dsi sii", $amount, $donation_type, $payment_reference_id, $purpose, $donation_id, $user_id);
        }
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = "Donation updated.";
        } else {
            $message = "Update failed or donation not editable.";
        }
        $stmt->close();
    } else {
        // Insert new donation
        $stmt = $conn->prepare("INSERT INTO donations 
            (donor_id, amount, donation_date, donation_type, transaction_picture, payment_reference_id, purpose, acknowledged) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("idsssis", $user_id, $amount, $donation_date, $donation_type, $transaction_picture, $payment_reference_id, $purpose);
        $stmt->execute();
        $message = "Donation added.";
        $stmt->close();
    }
}

// Handle Delete donation if pending
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM donations WHERE donation_id=? AND donor_id=? AND acknowledged='pending'");
    $stmt->bind_param("ii", $del_id, $user_id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $message = "Donation deleted.";
    } else {
        $message = "Delete failed or donation not deletable.";
    }
    $stmt->close();
}

// Fetch donor donations
$donations = $conn->prepare("SELECT * FROM donations WHERE donor_id=? ORDER BY donation_date DESC");
$donations->bind_param("i", $user_id);
$donations->execute();
$result = $donations->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Donor Dashboard - Manage Donations</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css" />
</head>
<body>
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2>Manage Your Donations</h2>
    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <button class="btn btn-success mb-3" onclick="openForm()">Add New Donation</button>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Date</th>
                <th>Amount</th>
                <th>Type</th>
                <th>Purpose</th>
                <th>Acknowledged</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($don = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($don['donation_date']) ?></td>
                <td><?= htmlspecialchars($don['amount']) ?></td>
                <td><?= htmlspecialchars($don['donation_type']) ?></td>
                <td><?= htmlspecialchars($don['purpose']) ?></td>
                <td><?= htmlspecialchars(ucfirst($don['acknowledged'])) ?></td>
                <td>
                    <?php if ($don['acknowledged'] === 'pending'): ?>
                        <button class="btn btn-primary btn-sm" onclick='editDonation(<?= json_encode($don) ?>)'>Edit</button>
                        <a href="?delete=<?= $don['donation_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this donation?')">Delete</a>
                    <?php elseif ($don['acknowledged'] === 'approved'): ?>
                        <a href="download_receipt.php?donation_id=<?= $don['donation_id'] ?>" class="btn btn-success btn-sm">Download Receipt</a>
                    <?php else: ?>
                        <em>No actions</em>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Donation Form Modal -->
<div class="modal" tabindex="-1" role="dialog" id="donationModal">
  <div class="modal-dialog" role="document">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <input type="hidden" name="donation_id" id="donation_id" value="">
      <div class="modal-header">
        <h5 class="modal-title">Donation Form</h5>
        <button type="button" class="close" onclick="closeForm()"><span>&times;</span></button>
      </div>
      <div class="modal-body">
            <div class="form-group">
                <label>Amount</label>
                <input type="number" name="amount" id="amount" class="form-control" required step="0.01" min="0.01">
            </div>
            <div class="form-group">
                <label>Donation Type</label>
                <select name="donation_type" id="donation_type" class="form-control" required onchange="toggleTransactionPicture()">
                    <option value="cash">Cash</option>
                    <option value="check">Check</option>
                    <option value="online">Online</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group" id="transaction_picture_div" style="display:none;">
                <label>Transaction Picture (for online)</label>
                <input type="file" name="transaction_picture" id="transaction_picture" accept=".jpg,.jpeg,.png,.gif,.pdf">
            </div>
            <div class="form-group">
                <label>Payment Reference ID</label>
                <input type="number" name="payment_reference_id" id="payment_reference_id" class="form-control" min="0" step="1">
            </div>
            <div class="form-group">
                <label>Purpose</label>
                <select name="purpose" id="purpose" class="form-control" required>
                    <option value="general">General</option>
                    <option value="medical">Medical</option>
                    <option value="facility">Facility</option>
                    <option value="other">Other</option>
                </select>
            </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Save Donation</button>
        <button type="button" class="btn btn-secondary" onclick="closeForm()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openForm() {
    $('#donationModal form')[0].reset();
    $('#donation_id').val('');
    $('#transaction_picture_div').hide();
    $('#donationModal').modal('show');
}

function closeForm() {
    $('#donationModal').modal('hide');
}

function editDonation(data) {
    $('#donation_id').val(data.donation_id);
    $('#amount').val(data.amount);
    $('#donation_type').val(data.donation_type);
    toggleTransactionPicture();
    $('#payment_reference_id').val(data.payment_reference_id);
    $('#purpose').val(data.purpose);
    $('#donationModal').modal('show');
}

function toggleTransactionPicture() {
    if ($('#donation_type').val() === 'online') {
        $('#transaction_picture_div').show();
    } else {
        $('#transaction_picture_div').hide();
        $('#transaction_picture').val('');
    }
}
</script>
</body>
</html>
