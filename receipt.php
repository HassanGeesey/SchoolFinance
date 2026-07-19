<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$payment = $db->prepare("
    SELECT fp.*, s.name as student_name, s.email as student_email, s.phone as student_phone,
           fs.name as fee_name, fs.amount as fee_amount,
           u.username as received_by_name
    FROM fee_payments fp
    JOIN students s ON fp.student_id = s.id
    JOIN fee_structures fs ON fp.fee_structure_id = fs.id
    LEFT JOIN users u ON fp.received_by = u.id
    WHERE fp.id = ?
");
$payment->execute([$id]);
$payment = $payment->fetch();

if (!$payment) {
    header('Location: fee_payments.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt #<?= $payment['id'] ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/design-system.css">
    <style>
        body { padding: 2rem; display: flex; flex-direction: column; align-items: center; min-height: 100vh; justify-content: center; background-color: var(--bg-body); }
        .receipt-card { width: 100%; max-width: 800px; background: var(--bg-card); border-radius: var(--radius); box-shadow: var(--shadow-lg); border: 1px solid var(--border); overflow: hidden; margin-bottom: 1.5rem; }
        .receipt-header { background-color: var(--primary); padding: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .receipt-header h1 { color: #fff; font-size: 1.5rem; margin-bottom: 0.25rem; font-weight: 600; }
        .receipt-header p { color: rgba(255,255,255,0.7); font-size: 0.875rem; margin: 0; }
        .receipt-header .receipt-number { text-align: right; }
        .receipt-header .receipt-number p.label { color: rgba(255,255,255,0.7); font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .receipt-header .receipt-number p.number { color: #fff; font-size: 1.5rem; font-weight: 700; margin: 0; }
        
        .receipt-body { padding: 2.5rem; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2.5rem; }
        .info-block h3 { font-size: 0.8rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; font-weight: 600; }
        .info-block .name { font-size: 1.125rem; color: var(--text-main); font-weight: 600; margin-bottom: 0.25rem; }
        .info-block p { color: var(--text-muted); font-size: 0.875rem; margin: 0 0 0.25rem 0; }
        .info-block.right { text-align: right; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 2.5rem; }
        .items-table th { text-align: left; font-size: 0.8rem; color: var(--text-light); text-transform: uppercase; font-weight: 600; padding-bottom: 0.75rem; border-bottom: 2px solid var(--border); }
        .items-table th.right { text-align: right; }
        .items-table td { padding: 1.25rem 0; border-bottom: 1px solid var(--border); color: var(--text-main); }
        .items-table td.right { text-align: right; font-weight: 600; }
        .items-table .item-name { font-weight: 600; font-size: 1rem; color: var(--text-main); margin-bottom: 0.25rem; display: block; }
        .items-table .item-desc { font-size: 0.875rem; color: var(--text-muted); margin: 0; }
        
        .total-row td { border-bottom: none; padding-top: 1.5rem; font-weight: 600; font-size: 1.125rem; color: var(--text-main); }
        .total-row td.amount { color: var(--accent); font-size: 1.75rem; font-weight: 700; }
        
        .remarks-box { background-color: var(--bg-body); border-radius: var(--radius-sm); padding: 1.25rem; margin-bottom: 2.5rem; border: 1px solid var(--border); }
        .remarks-box p { margin: 0; font-size: 0.875rem; color: var(--text-main); }
        
        .receipt-footer { display: flex; justify-content: space-between; align-items: flex-end; }
        .signature p { color: var(--text-muted); font-size: 0.875rem; margin: 0 0 0.25rem 0; }
        
        .verified-stamp { width: 90px; height: 90px; border-radius: 50%; background-color: rgba(16, 185, 129, 0.1); border: 2px dashed #059669; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #059669; transform: rotate(-15deg); }
        .verified-stamp i { font-size: 2rem; margin-bottom: 0.25rem; }
        .verified-stamp span { font-size: 0.65rem; font-weight: 600; text-transform: uppercase; }
        
        .actions-bar { width: 100%; max-width: 800px; display: flex; justify-content: center; gap: 1rem; }
        
        @media print {
            body { background: none; padding: 0; }
            .receipt-card { box-shadow: none; border: none; margin-bottom: 0; }
            .actions-bar { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt-card">
        <div class="receipt-header">
            <div>
                <h1>English Institute</h1>
                <p>Excellence in Education</p>
            </div>
            <div class="receipt-number">
                <p class="label">Receipt</p>
                <p class="number">#<?= str_pad($payment['id'], 6, '0', STR_PAD_LEFT) ?></p>
            </div>
        </div>
        
        <div class="receipt-body">
            <div class="info-grid">
                <div class="info-block">
                    <h3>Student Information</h3>
                    <div class="name"><?= htmlspecialchars($payment['student_name']) ?></div>
                    <p><?= htmlspecialchars($payment['student_email'] ?? 'N/A') ?></p>
                    <p><?= htmlspecialchars($payment['student_phone'] ?? 'N/A') ?></p>
                </div>
                <div class="info-block right">
                    <h3>Payment Details</h3>
                    <p><strong>Date:</strong> <?= date('F d, Y', strtotime($payment['payment_date'])) ?></p>
                    <p><strong>Method:</strong> <?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></p>
                    <?php if ($payment['transaction_id']): ?>
                        <p><strong>Trans ID:</strong> <?= htmlspecialchars($payment['transaction_id']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <span class="item-name"><?= htmlspecialchars($payment['fee_name']) ?></span>
                            <p class="item-desc">Payment received</p>
                        </td>
                        <td class="right">$<?= number_format($payment['amount'], 2) ?></td>
                    </tr>
                    <tr class="total-row">
                        <td>Total Paid</td>
                        <td class="right amount">$<?= number_format($payment['amount'], 2) ?></td>
                    </tr>
                </tbody>
            </table>
            
            <?php if ($payment['remarks']): ?>
                <div class="remarks-box">
                    <p><strong>Remarks:</strong> <?= htmlspecialchars($payment['remarks']) ?></p>
                </div>
            <?php endif; ?>
            
            <div class="receipt-footer">
                <div class="signature">
                    <p>Received by: <strong><?= htmlspecialchars($payment['received_by_name'] ?? 'Admin') ?></strong></p>
                    <p>Generated: <?= date('F d, Y h:i A') ?></p>
                </div>
                <div class="verified-stamp">
                    <i class="fas fa-check"></i>
                    <span>Verified</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="actions-bar">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print Receipt
        </button>
        <a href="fee_payments.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Back to Payments
        </a>
    </div>
</body>
</html>
