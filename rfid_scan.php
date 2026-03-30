<?php
require_once 'config.php';
requireAuth();

$db = getDB();
$scanned_component = null;
$scan_result = null;

// Handle RFID scan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rfid_scan'])) {
    $rfid_input = trim($_POST['rfid_scan']);

    if (!empty($rfid_input)) {
        // Look up component by RFID
        $stmt = $db->prepare("SELECT * FROM components WHERE rfid_tag = ? OR rfid_epc = ? OR component_id = ?");
        $stmt->bind_param("sss", $rfid_input, $rfid_input, $rfid_input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $scanned_component = $result->fetch_assoc();

            // Log the scan
            $scan_type = $_POST['scan_type'] ?? 'inventory';
            $location = $_POST['location'] ?? '';
            $notes = $_POST['notes'] ?? '';

            logRFIDScan(
                $scanned_component['rfid_tag'] ?: $rfid_input,
                $scanned_component['id'],
                $scan_type,
                $_SESSION['user']['id'],
                $location,
                $notes
            );

            $scan_result = 'success';
            setFlash('success', 'Component found: ' . $scanned_component['name']);
        } else {
            $scan_result = 'not_found';
            setFlash('error', 'No component found with RFID: ' . sanitize($rfid_input));
        }
    }
}

// Get recent scans
$recent_scans = getRFIDScanHistory(null, 20);

$pageTitle = 'RFID Scanner';
require 'header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
  <h1 style="color: var(--primary); margin-bottom: 2rem;"><i class="fas fa-wifi"></i> RFID Scanner</h1>

  <!-- Scan Form -->
  <div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
      <h2><i class="fas fa-barcode"></i> Scan Component</h2>
    </div>
    <div class="card-body">
      <form method="POST" action="">
        <div class="form-group">
          <label>RFID Tag / EPC / Component ID</label>
          <input type="text" name="rfid_scan" class="form-control" 
                 placeholder="Scan or type RFID tag..." 
                 style="font-size: 1.2rem; padding: 1rem;" 
                 autofocus required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label>Scan Type</label>
            <select name="scan_type" class="form-control">
              <option value="inventory">Inventory Check</option>
              <option value="check_in">Check In (Stock In)</option>
              <option value="check_out">Check Out (Stock Out)</option>
              <option value="sale">Sale Transaction</option>
            </select>
          </div>
          <div class="form-group">
            <label>Location</label>
            <input type="text" name="location" class="form-control" placeholder="Current location">
          </div>
        </div>

        <div class="form-group">
          <label>Notes</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
          <i class="fas fa-search"></i> Scan / Lookup
        </button>
      </form>
    </div>
  </div>

  <!-- Scan Result -->
  <?php if ($scanned_component): ?>
  <div class="card" style="margin-bottom: 2rem; border: 2px solid #4caf50;">
    <div class="card-header" style="background: #4caf50;">
      <h2><i class="fas fa-check-circle"></i> Component Found</h2>
    </div>
    <div class="card-body">
      <div style="display: flex; gap: 1.5rem; align-items: start;">
        <img src="<?php echo $scanned_component['image_url'] ?: 'https://via.placeholder.com/150?text=No+Image'; ?>" 
             alt="<?php echo sanitize($scanned_component['name']); ?>"
             style="width: 150px; height: 150px; object-fit: cover; border-radius: 12px;">
        <div style="flex: 1;">
          <h3 style="color: var(--primary); margin-bottom: 0.5rem;"><?php echo sanitize($scanned_component['name']); ?></h3>
          <p style="color: #666; margin-bottom: 0.5rem;"><i class="fas fa-building"></i> <?php echo sanitize($scanned_component['brand']); ?></p>
          <p style="color: #666; margin-bottom: 0.5rem;"><i class="fas fa-tag"></i> <?php echo $scanned_component['component_id']; ?></p>
          <p style="color: #666; margin-bottom: 0.5rem;"><i class="fas fa-wifi"></i> <?php echo sanitize($scanned_component['rfid_tag']); ?></p>
          <p style="font-size: 1.5rem; color: var(--secondary); font-weight: 700; margin: 1rem 0;">
            <?php echo formatPrice($scanned_component['price']); ?>
          </p>
          <span class="badge <?php echo $scanned_component['stock_quantity'] > 10 ? 'badge-success' : ($scanned_component['stock_quantity'] > 0 ? 'badge-warning' : 'badge-danger'); ?>">
            Stock: <?php echo $scanned_component['stock_quantity']; ?>
          </span>
        </div>
      </div>
      <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
        <a href="view_component.php?id=<?php echo $scanned_component['id']; ?>" class="btn btn-primary">
          <i class="fas fa-eye"></i> View Details
        </a>
        <a href="payment.php?component=<?php echo $scanned_component['id']; ?>" class="btn btn-secondary">
          <i class="fas fa-credit-card"></i> Process Payment
        </a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Recent Scan History -->
  <div class="card">
    <div class="card-header">
      <h2><i class="fas fa-history"></i> Recent Scan History</h2>
    </div>
    <div class="card-body">
      <?php if (empty($recent_scans)): ?>
        <p class="text-center" style="color: #999; padding: 2rem;">No scans recorded yet</p>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Time</th>
              <th>RFID Tag</th>
              <th>Component</th>
              <th>Type</th>
              <th>Location</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent_scans as $scan): ?>
            <tr>
              <td><?php echo date('M d H:i', strtotime($scan['created_at'])); ?></td>
              <td style="font-family: monospace; font-size: 0.9rem;"><?php echo sanitize($scan['rfid_tag']); ?></td>
              <td><?php echo $scan['component_name'] ? sanitize($scan['component_name']) : '-'; ?></td>
              <td>
                <span class="badge badge-<?php 
                  echo $scan['scan_type'] == 'check_in' ? 'success' : ($scan['scan_type'] == 'check_out' ? 'warning' : 'info'); 
                ?>">
                  <?php echo ucwords(str_replace('_', ' ', $scan['scan_type'])); ?>
                </span>
              </td>
              <td><?php echo $scan['location'] ? sanitize($scan['location']) : '-'; ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require 'footer.php'; ?>