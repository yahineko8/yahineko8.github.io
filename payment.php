<?php
require_once 'config.php';
requireAuth();

$db = getDB();

// Get all components for selection
$components = $db->query("SELECT * FROM components WHERE stock_quantity > 0 ORDER BY name");

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $component_id = intval($_POST['component_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $payment_method = $_POST['payment_method'] ?? '';
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_email = $_POST['customer_email'] ?? '';
    $customer_phone = $_POST['customer_phone'] ?? '';

    // Get component details including RFID
    $comp_result = $db->query("SELECT * FROM components WHERE id = $component_id");
    if ($comp_result->num_rows === 0) {
        setFlash('error', 'Component not found');
        redirect('payment.php');
    }

    $component = $comp_result->fetch_assoc();

    if ($component['stock_quantity'] < $quantity) {
        setFlash('error', 'Insufficient stock');
        redirect('payment.php');
    }

    $unit_price = $component['price'];
    $total = $unit_price * $quantity;
    $transaction_code = 'TRX-' . strtoupper(uniqid());
    $rfid_tag = $component['rfid_tag']; // Capture RFID at time of sale

    // Start transaction
    $db->begin_transaction();

    try {
        // Insert transaction with RFID reference
        $stmt = $db->prepare("INSERT INTO transactions 
            (transaction_code, user_id, component_id, rfid_tag, quantity, unit_price, total_amount, 
             payment_method, payment_status, customer_name, customer_email, customer_phone) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?, ?, ?)");
        $stmt->bind_param("siisiddssss", 
            $transaction_code, 
            $_SESSION['user']['id'], 
            $component_id, 
            $rfid_tag,
            $quantity, 
            $unit_price, 
            $total,
            $payment_method,
            $customer_name,
            $customer_email,
            $customer_phone
        );
        $stmt->execute();

        // Update stock
        $db->query("UPDATE components SET stock_quantity = stock_quantity - $quantity WHERE id = $component_id");

        // Log RFID scan for this transaction
        $scan_stmt = $db->prepare("INSERT INTO rfid_scans (rfid_tag, component_id, scan_type, scanned_by, scan_location) VALUES (?, ?, 'sale', ?, 'POS')");
        $scan_stmt->bind_param("sii", $rfid_tag, $component_id, $_SESSION['user']['id']);
        $scan_stmt->execute();

        $db->commit();

        setFlash('success', "Payment successful! Transaction: $transaction_code | RFID: $rfid_tag");
        redirect('payment.php?success=1&code=' . $transaction_code . '&rfid=' . $rfid_tag);
    } catch (Exception $e) {
        $db->rollback();
        setFlash('error', 'Payment failed: ' . $e->getMessage());
        redirect('payment.php');
    }
}

// Get recent transactions with RFID
$trans_result = $db->query("SELECT t.*, c.name as component_name, c.rfid_tag 
                            FROM transactions t 
                            JOIN components c ON t.component_id = c.id 
                            ORDER BY t.id DESC LIMIT 10");
$transactions = [];
while ($row = $trans_result->fetch_assoc()) {
    $transactions[] = $row;
}

$pageTitle = 'Payment';
require 'header.php';

$preselected = isset($_GET['component']) ? intval($_GET['component']) : 0;
?>

<?php if (isset($_GET['success'])): ?>
<div class="flash-message flash-success">
  <i class="fas fa-check-circle"></i>
  <div>
    <strong>Payment completed!</strong><br>
    Transaction: <?php echo sanitize($_GET['code'] ?? ''); ?><br>
    RFID: <?php echo sanitize($_GET['rfid'] ?? ''); ?>
  </div>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
  <!-- Payment Form -->
  <div class="card">
    <div class="card-header">
      <h2><i class="fas fa-credit-card"></i> Process Payment</h2>
    </div>
    <div class="card-body">
      <form method="POST" action="">
        <div class="form-group">
          <label>Select Component *</label>
          <select name="component_id" class="form-control" required>
            <option value="">Choose a component...</option>
            <?php while ($comp = $components->fetch_assoc()): ?>
            <option value="<?php echo $comp['id']; ?>" <?php echo $preselected == $comp['id'] ? 'selected' : ''; ?>>
              <?php echo sanitize($comp['name']); ?> 
              [<?php echo sanitize($comp['rfid_tag']); ?>] - 
              <?php echo formatPrice($comp['price']); ?> 
              [Stock: <?php echo $comp['stock_quantity']; ?>]
            </option>
            <?php endwhile; ?>
          </select>
          <small style="color: #666;">RFID tag shown in brackets</small>
        </div>

        <div class="form-group">
          <label>Quantity *</label>
          <input type="number" name="quantity" class="form-control" value="1" min="1" required>
        </div>

        <div class="form-group">
          <label>Customer Name *</label>
          <input type="text" name="customer_name" class="form-control" required>
        </div>

        <div class="form-group">
          <label>Email *</label>
          <input type="email" name="customer_email" class="form-control" required>
        </div>

        <div class="form-group">
          <label>Phone</label>
          <input type="tel" name="customer_phone" class="form-control">
        </div>

        <div class="form-group">
          <label>Payment Method *</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <label style="border: 2px solid #e0e0e0; border-radius: 12px; padding: 1rem; text-align: center; cursor: pointer;">
              <input type="radio" name="payment_method" value="credit_card" required style="margin-bottom: 0.5rem;">
              <div><i class="fas fa-credit-card" style="font-size: 1.5rem; color: #666;"></i></div>
              <div style="font-size: 0.9rem; margin-top: 0.5rem;">Credit Card</div>
            </label>
            <label style="border: 2px solid #e0e0e0; border-radius: 12px; padding: 1rem; text-align: center; cursor: pointer;">
              <input type="radio" name="payment_method" value="debit_card" style="margin-bottom: 0.5rem;">
              <div><i class="fas fa-credit-card" style="font-size: 1.5rem; color: #666;"></i></div>
              <div style="font-size: 0.9rem; margin-top: 0.5rem;">Debit Card</div>
            </label>
            <label style="border: 2px solid #e0e0e0; border-radius: 12px; padding: 1rem; text-align: center; cursor: pointer;">
              <input type="radio" name="payment_method" value="digital_wallet" style="margin-bottom: 0.5rem;">
              <div><i class="fas fa-wallet" style="font-size: 1.5rem; color: #666;"></i></div>
              <div style="font-size: 0.9rem; margin-top: 0.5rem;">Digital Wallet</div>
            </label>
            <label style="border: 2px solid #e0e0e0; border-radius: 12px; padding: 1rem; text-align: center; cursor: pointer;">
              <input type="radio" name="payment_method" value="bank_transfer" style="margin-bottom: 0.5rem;">
              <div><i class="fas fa-university" style="font-size: 1.5rem; color: #666;"></i></div>
              <div style="font-size: 0.9rem; margin-top: 0.5rem;">Bank Transfer</div>
            </label>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
          <i class="fas fa-lock"></i> Process Payment
        </button>
      </form>
    </div>
  </div>

  <!-- Recent Transactions -->
  <div>
    <h2 style="color: var(--primary); margin-bottom: 1rem;"><i class="fas fa-history"></i> Recent Transactions</h2>
    <?php if (empty($transactions)): ?>
    <div class="card" style="padding: 3rem; text-align: center;">
      <i class="fas fa-receipt" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
      <p style="color: #999;">No transactions yet</p>
    </div>
    <?php else: ?>
      <?php foreach ($transactions as $t): ?>
      <div class="card" style="margin-bottom: 1rem; padding: 1rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <div>
            <h4 style="color: #333; margin-bottom: 0.25rem;"><?php echo sanitize($t['component_name']); ?></h4>
            <p style="color: #666; font-size: 0.9rem;">
              <?php echo sanitize($t['customer_name']); ?> • 
              <?php echo date('M d, Y', strtotime($t['created_at'])); ?>
            </p>
            <p style="color: #999; font-size: 0.8rem; margin-top: 0.25rem;">
              <i class="fas fa-wifi"></i> <?php echo sanitize($t['rfid_tag']); ?> • 
              <?php echo $t['transaction_code']; ?>
            </p>
          </div>
          <div style="text-align: right;">
            <div style="font-size: 1.3rem; font-weight: 700; color: var(--primary);">
              <?php echo formatPrice($t['total_amount']); ?>
            </div>
            <span class="badge badge-<?php echo $t['payment_status'] == 'completed' ? 'success' : 'warning'; ?>">
              <?php echo $t['payment_status']; ?>
            </span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php require 'footer.php'; ?>
