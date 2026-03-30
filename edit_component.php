<?php
require_once 'config.php';
requireAuth();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) redirect('components.php');

$db = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $component_id = $_POST['component_id'] ?? '';
    $rfid_tag = $_POST['rfid_tag'] ?? '';
    $rfid_epc = $_POST['rfid_epc'] ?? '';
    $name = $_POST['name'] ?? '';
    $brand = $_POST['brand'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock_quantity'] ?? 0);
    $specs = $_POST['specs'] ?? '';
    $compatibility = $_POST['compatibility'] ?? '';
    $location = $_POST['location'] ?? '';
    $image_url = $_POST['image_url'] ?? '';

    $stmt = $db->prepare("UPDATE components SET component_id=?, rfid_tag=?, rfid_epc=?, name=?, brand=?, price=?, stock_quantity=?, specs=?, compatibility=?, location=?, image_url=? WHERE id=?");
    $stmt->bind_param("sssssdsssssi", $component_id, $rfid_tag, $rfid_epc, $name, $brand, $price, $stock, $specs, $compatibility, $location, $image_url, $id);

    if ($stmt->execute()) {
        setFlash('success', 'Component updated successfully');
        redirect('view_component.php?id=' . $id);
    } else {
        setFlash('error', 'Failed to update component');
    }
}

// Get component data
$result = $db->query("SELECT * FROM components WHERE id = $id");
if ($result->num_rows === 0) {
    setFlash('error', 'Component not found');
    redirect('components.php');
}
$comp = $result->fetch_assoc();

$pageTitle = 'Edit ' . $comp['name'];
require 'header.php';
?>

<a href="view_component.php?id=<?php echo $id; ?>" style="display: inline-flex; align-items: center; gap: 0.5rem; color: var(--secondary); margin-bottom: 1.5rem; text-decoration: none;">
  <i class="fas fa-arrow-left"></i> Back to Component
</a>

<div class="card" style="max-width: 900px; margin: 0 auto;">
  <div class="card-header">
    <h2><i class="fas fa-edit"></i> Edit Component</h2>
  </div>
  <div class="card-body">
    <form method="POST" action="">

      <!-- RFID Section -->
      <div style="background: #e3f2fd; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;">
        <h3 style="color: #1976d2; margin-bottom: 1rem;"><i class="fas fa-wifi"></i> RFID Information</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label>RFID Tag Number</label>
            <input type="text" name="rfid_tag" class="form-control" value="<?php echo sanitize($comp['rfid_tag']); ?>">
            <small style="color: #666;">Unique identifier for RFID scanner</small>
          </div>
          <div class="form-group">
            <label>RFID EPC (Electronic Product Code)</label>
            <input type="text" name="rfid_epc" class="form-control" value="<?php echo sanitize($comp['rfid_epc']); ?>">
            <small style="color: #666;">Electronic Product Code from RFID tag</small>
          </div>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        <div class="form-group">
          <label>Component ID *</label>
          <input type="text" name="component_id" class="form-control" value="<?php echo sanitize($comp['component_id']); ?>" required>
        </div>
        <div class="form-group">
          <label>Name *</label>
          <input type="text" name="name" class="form-control" value="<?php echo sanitize($comp['name']); ?>" required>
        </div>
        <div class="form-group">
          <label>Brand *</label>
          <input type="text" name="brand" class="form-control" value="<?php echo sanitize($comp['brand']); ?>" required>
        </div>
        <div class="form-group">
          <label>Price (₱) *</label>
          <input type="number" name="price" step="0.01" class="form-control" value="<?php echo $comp['price']; ?>" required>
        </div>
        <div class="form-group">
          <label>Stock Quantity *</label>
          <input type="number" name="stock_quantity" class="form-control" value="<?php echo $comp['stock_quantity']; ?>" required>
        </div>
        <div class="form-group">
          <label>Storage Location</label>
          <input type="text" name="location" class="form-control" value="<?php echo sanitize($comp['location']); ?>">
        </div>
        <div class="form-group">
          <label>Compatibility</label>
          <input type="text" name="compatibility" class="form-control" value="<?php echo sanitize($comp['compatibility']); ?>">
        </div>
        <div class="form-group">
          <label>Image URL</label>
          <input type="url" name="image_url" class="form-control" value="<?php echo sanitize($comp['image_url']); ?>">
        </div>
        <div class="form-group" style="grid-column: 1 / -1;">
          <label>Specifications</label>
          <textarea name="specs" class="form-control" rows="4"><?php echo sanitize($comp['specs']); ?></textarea>
        </div>
      </div>

      <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #eee;">
        <button type="submit" class="btn btn-primary" style="flex: 1;">
          <i class="fas fa-save"></i> Save Changes
        </button>
        <a href="view_component.php?id=<?php echo $id; ?>" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require 'footer.php'; ?>