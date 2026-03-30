<?php
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) redirect('components.php');

$db = getDB();
$result = $db->query("SELECT * FROM components WHERE id = $id");

if ($result->num_rows === 0) {
    setFlash('error', 'Component not found');
    redirect('components.php');
}

$comp = $result->fetch_assoc();

// Get RFID scan history
$scan_history = getRFIDScanHistory($comp['rfid_tag'], 10);

$pageTitle = $comp['name'];
require 'header.php';

$stockClass = $comp['stock_quantity'] > 10 ? 'badge-success' : ($comp['stock_quantity'] > 0 ? 'badge-warning' : 'badge-danger');
$stockText = $comp['stock_quantity'] > 10 ? 'In Stock' : ($comp['stock_quantity'] > 0 ? 'Low Stock' : 'Out of Stock');
?>

<a href="components.php" style="display: inline-flex; align-items: center; gap: 0.5rem; color: var(--secondary); margin-bottom: 1.5rem; text-decoration: none;">
  <i class="fas fa-arrow-left"></i> Back to Components
</a>

<div class="card">
  <div style="display: grid; grid-template-columns: 1fr 1fr;">
    <img src="<?php echo $comp['image_url'] ?: 'https://via.placeholder.com/600x600?text=No+Image'; ?>" 
         alt="<?php echo sanitize($comp['name']); ?>" 
         style="width: 100%; height: 100%; min-height: 400px; object-fit: cover;">

    <div style="padding: 3rem;">
      <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
        <span class="badge badge-info"><?php echo $comp['component_id']; ?></span>
        <?php if (!empty($comp['rfid_tag'])): ?>
        <span class="badge" style="background: #e3f2fd; color: #1976d2;">
          <i class="fas fa-wifi"></i> RFID Enabled
        </span>
        <?php endif; ?>
      </div>

      <h1 style="font-size: 2rem; color: var(--primary); margin-bottom: 0.5rem;"><?php echo sanitize($comp['name']); ?></h1>
      <p style="color: #666; font-size: 1.1rem; margin-bottom: 1.5rem;">
        <i class="fas fa-building"></i> <?php echo sanitize($comp['brand']); ?>
      </p>

      <div style="font-size: 2.5rem; color: var(--secondary); font-weight: 700; margin-bottom: 2rem;">
        <?php echo formatPrice($comp['price']); ?>
      </div>

      <!-- RFID Information Section -->
      <?php if (!empty($comp['rfid_tag'])): ?>
      <div style="background: #e3f2fd; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
        <h3 style="color: #1976d2; margin-bottom: 1rem;"><i class="fas fa-wifi"></i> RFID Information</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div>
            <label style="color: #666; font-size: 0.85rem;">RFID Tag</label>
            <div style="font-weight: 600; color: #333; font-family: monospace;"><?php echo sanitize($comp['rfid_tag']); ?></div>
          </div>
          <?php if (!empty($comp['rfid_epc'])): ?>
          <div>
            <label style="color: #666; font-size: 0.85rem;">EPC Code</label>
            <div style="font-weight: 600; color: #333; font-family: monospace;"><?php echo sanitize($comp['rfid_epc']); ?></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <div style="background: #f8f9fa; border-radius: 16px; padding: 1.5rem; margin-bottom: 2rem;">
        <h3 style="color: var(--primary); margin-bottom: 1rem;"><i class="fas fa-info-circle"></i> Specifications</h3>
        <p style="color: #555; line-height: 1.6;"><?php echo nl2br(sanitize($comp['specs'])); ?></p>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
        <div style="background: #f8f9fa; padding: 1rem; border-radius: 12px;">
          <label style="display: block; color: #999; font-size: 0.85rem; margin-bottom: 0.25rem;">Compatibility</label>
          <span style="color: #333; font-weight: 600;"><?php echo $comp['compatibility'] ? sanitize($comp['compatibility']) : 'Universal'; ?></span>
        </div>
        <div style="background: #f8f9fa; padding: 1rem; border-radius: 12px;">
          <label style="display: block; color: #999; font-size: 0.85rem; margin-bottom: 0.25rem;">Stock Status</label>
          <span class="badge <?php echo $stockClass; ?>"><?php echo $stockText; ?> (<?php echo $comp['stock_quantity']; ?>)</span>
        </div>
        <?php if (!empty($comp['location'])): ?>
        <div style="background: #f8f9fa; padding: 1rem; border-radius: 12px;">
          <label style="display: block; color: #999; font-size: 0.85rem; margin-bottom: 0.25rem;">Storage Location</label>
          <span style="color: #333; font-weight: 600;"><i class="fas fa-map-marker-alt"></i> <?php echo sanitize($comp['location']); ?></span>
        </div>
        <?php endif; ?>
      </div>

      <div style="display: flex; gap: 1rem;">
        <?php if ($comp['stock_quantity'] > 0): ?>
        <a href="payment.php?component=<?php echo $comp['id']; ?>" class="btn btn-primary" style="flex: 1;">
          <i class="fas fa-credit-card"></i> Buy Now
        </a>
        <?php endif; ?>
        <?php if (isLoggedIn()): ?>
        <a href="edit_component.php?id=<?php echo $comp['id']; ?>" class="btn btn-secondary">
          <i class="fas fa-edit"></i> Edit
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- RFID Scan History -->
<?php if (!empty($comp['rfid_tag']) && !empty($scan_history)): ?>
<div class="card" style="margin-top: 2rem;">
  <div class="card-header">
    <h3><i class="fas fa-history"></i> RFID Scan History</h3>
  </div>
  <div class="card-body">
    <table class="table">
      <thead>
        <tr>
          <th>Date/Time</th>
          <th>Scan Type</th>
          <th>Location</th>
          <th>Scanned By</th>
          <th>Notes</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($scan_history as $scan): ?>
        <tr>
          <td><?php echo date('M d, Y H:i', strtotime($scan['created_at'])); ?></td>
          <td>
            <span class="badge badge-<?php 
              echo $scan['scan_type'] == 'check_in' ? 'success' : ($scan['scan_type'] == 'check_out' ? 'warning' : 'info'); 
            ?>">
              <?php echo ucwords(str_replace('_', ' ', $scan['scan_type'])); ?>
            </span>
          </td>
          <td><?php echo $scan['location'] ? sanitize($scan['location']) : '-'; ?></td>
          <td><?php echo $scan['scanner_name'] ? sanitize($scan['scanner_name']) : 'System'; ?></td>
          <td><?php echo $scan['notes'] ? sanitize($scan['notes']) : '-'; ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require 'footer.php'; ?>