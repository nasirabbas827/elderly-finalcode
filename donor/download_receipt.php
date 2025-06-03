<?php
session_start();
include('config.php');

if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== 'donor') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION["id"];

if (!isset($_GET['donation_id']) || !is_numeric($_GET['donation_id'])) {
    echo "Invalid donation ID.";
    exit();
}

$donation_id = (int)$_GET['donation_id'];

$stmt = $conn->prepare("SELECT amount, donation_date, donation_type, payment_reference_id, purpose, acknowledged FROM donations WHERE donation_id=? AND donor_id=? LIMIT 1");
$stmt->bind_param("ii", $donation_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Donation not found.";
    exit();
}

$donation = $result->fetch_assoc();

if ($donation['acknowledged'] !== 'approved') {
    echo "<p>Receipt is not yet available. Thank you for your patience and generosity.</p>";
    exit();
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Donation Receipt - #<?= htmlspecialchars($donation_id) ?></title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 20px;
        background: #f7f9fc;
        color: #333;
    }
    .receipt-container {
        max-width: 700px;
        margin: auto;
        background: white;
        border-radius: 8px;
        padding: 30px 40px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    h1, h2 {
        text-align: center;
        color: #2c3e50;
        margin-bottom: 10px;
    }
    .details, .usage-report {
        margin-top: 25px;
        border-top: 1px solid #ccc;
        padding-top: 15px;
    }
    .details table, .usage-report table {
        width: 100%;
        border-collapse: collapse;
    }
    .details th, .details td, .usage-report th, .usage-report td {
        text-align: left;
        padding: 8px 12px;
        border-bottom: 1px solid #ddd;
    }
    .appreciation {
        margin-top: 30px;
        background: #e7f5ff;
        padding: 15px 20px;
        border-left: 6px solid #1e90ff;
        font-style: italic;
        color: #0056b3;
    }
    #downloadBtn {
        display: block;
        margin: 30px auto 0 auto;
        background-color: #1e90ff;
        color: white;
        border: none;
        padding: 12px 28px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
    }
    #downloadBtn:hover {
        background-color: #0d6efd;
    }
</style>
</head>
<body>
<div class="receipt-container">
    <h1>Donation Receipt</h1>
    <h2>Thank you for your generous support!</h2>

    <div class="details">
        <h3>Donation Details</h3>
        <table>
            <tr><th>Donation ID</th><td><?= htmlspecialchars($donation_id) ?></td></tr>
            <tr><th>Donor ID</th><td><?= htmlspecialchars($user_id) ?></td></tr>
            <tr><th>Date</th><td><?= htmlspecialchars($donation['donation_date']) ?></td></tr>
            <tr><th>Amount</th><td>$<?= number_format($donation['amount'], 2) ?></td></tr>
            <tr><th>Donation Type</th><td><?= htmlspecialchars(ucfirst($donation['donation_type'])) ?></td></tr>
            <tr><th>Payment Reference ID</th><td><?= htmlspecialchars($donation['payment_reference_id'] ?? 'N/A') ?></td></tr>
            <tr><th>Purpose</th><td><?= htmlspecialchars(ucfirst($donation['purpose'])) ?></td></tr>
            <tr><th>Status</th><td><?= htmlspecialchars(ucfirst($donation['acknowledged'])) ?></td></tr>
        </table>
    </div>

    <div class="usage-report">
        <h3>How Your Donation Has Been Used</h3>
        <table>
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Description</th>
                    <th>Amount Allocated</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Medical Supplies</td>
                    <td>Purchased essential medicines and equipment.</td>
                    <td>$<?= number_format($donation['amount'] * 0.4, 2) ?></td>
                </tr>
                <tr>
                    <td>Facility Improvement</td>
                    <td>Upgraded infrastructure and sanitation facilities.</td>
                    <td>$<?= number_format($donation['amount'] * 0.3, 2) ?></td>
                </tr>
                <tr>
                    <td>Community Outreach</td>
                    <td>Organized awareness and health camps.</td>
                    <td>$<?= number_format($donation['amount'] * 0.3, 2) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="appreciation">
        <p>
            Your generosity makes a real difference in people’s lives. Every contribution helps us provide critical care, improve facilities, and support our community programs. Thank you for standing with us.
        </p>
        <p>
            Remember to keep this receipt for your records. We look forward to your continued support and invite you to get more receipts for your future donations.
        </p>
    </div>

    <button id="downloadBtn" onclick="window.print()">Download Receipt PDF</button>
</div>

<script>
window.onload = () => {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    doc.setFontSize(22);
    doc.setTextColor("#2c3e50");
    doc.text("Donation Receipt", 105, 20, null, null, "center");
    doc.setFontSize(14);
    doc.setTextColor("#1e90ff");
    doc.text("Thank you for your generous support!", 105, 30, null, null, "center");

    doc.setFontSize(12);
    doc.setTextColor("#333");
    let y = 45;
    const lineHeight = 8;

    const details = [
        ["Donation ID", "<?= htmlspecialchars($donation_id) ?>"],
        ["Donor ID", "<?= htmlspecialchars($user_id) ?>"],
        ["Date", "<?= htmlspecialchars($donation['donation_date']) ?>"],
        ["Amount", "$<?= number_format($donation['amount'], 2) ?>"],
        ["Donation Type", "<?= htmlspecialchars(ucfirst($donation['donation_type'])) ?>"],
        ["Payment Reference ID", "<?= htmlspecialchars($donation['payment_reference_id'] ?? 'N/A') ?>"],
        ["Purpose", "<?= htmlspecialchars(ucfirst($donation['purpose'])) ?>"],
        ["Status", "<?= htmlspecialchars(ucfirst($donation['acknowledged'])) ?>"]
    ];

    doc.setDrawColor(200);
    doc.setLineWidth(0.5);

    doc.text("Donation Details", 14, y);
    y += 6;
    doc.line(14, y, 196, y);
    y += lineHeight;

    details.forEach(([key, value]) => {
        doc.text(`${key}:`, 14, y);
        doc.text(value.toString(), 70, y);
        y += lineHeight;
    });

    y += 10;
    doc.text("How Your Donation Has Been Used", 14, y);
    y += 6;
    doc.line(14, y, 196, y);
    y += lineHeight;

    const usage = [
        ["Medical Supplies", "Purchased essential medicines and equipment.", <?= $donation['amount'] * 0.4 ?>],
        ["Facility Improvement", "Upgraded infrastructure and sanitation facilities.", <?= $donation['amount'] * 0.3 ?>],
        ["Community Outreach", "Organized awareness and health camps.", <?= $donation['amount'] * 0.3 ?>]
    ];

    usage.forEach(([project, desc, amt]) => {
        doc.text(project, 14, y);
        doc.text(desc, 60, y, {maxWidth: 100});
        doc.text(`$${amt.toFixed(2)}`, 165, y);
        y += lineHeight;
    });

    y += 10;
    doc.setFontStyle("italic");
    doc.setTextColor("#0056b3");
    doc.text("Your generosity makes a real difference in people’s lives. Every contribution helps us provide critical care, improve facilities, and support our community programs. Thank you for standing with us.", 14, y, {maxWidth: 180});
    y += 20;
    doc.text("Remember to keep this receipt for your records. We look forward to your continued support and invite you to get more receipts for your future donations.", 14, y, {maxWidth: 180});

    function savePDF() {
        doc.save(`Donation_Receipt_#<?= $donation_id ?>.pdf`);
    }

    // Auto download after 1 sec
    setTimeout(savePDF, 1000);

    document.getElementById('downloadBtn').onclick = savePDF;
};
</script>
</body>
</html>
