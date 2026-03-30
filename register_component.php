<?php
require_once 'config.php';
requireAuth();

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

    $db = getDB();

    // Check for duplicate component_id
    $check = $db->prepare("SELECT id FROM components WHERE component_id = ?");
    $check->bind_param("s", $component_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        setFlash('error', 'Component ID already exists');
    } 
    // Check for duplicate RFID if provided
    elseif (!empty($rfid_tag)) {
        $check_rfid = $db->prepare("SELECT id FROM components WHERE rfid_tag = ? OR rfid_epc = ?");
        $check_rfid->bind_param("ss", $rfid_tag, $rfid_epc);
        $check_rfid->execute();
        if ($check_rfid->get_result()->num_rows > 0) {
            setFlash('error', 'RFID tag or EPC already exists');
        } else {
            $stmt = $db->prepare("INSERT INTO components (component_id, rfid_tag, rfid_epc, name, brand, price, stock_quantity, specs, compatibility, location, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssdsssss", $component_id, $rfid_tag, $rfid_epc, $name, $brand, $price, $stock, $specs, $compatibility, $location, $image_url);

            if ($stmt->execute()) {
                // Log RFID scan if RFID is provided
                if (!empty($rfid_tag)) {
                    logRFIDScan($rfid_tag, $stmt->insert_id, 'check_in', $_SESSION['user']['id'], $location, 'Initial registration');
                }
                setFlash('success', 'Component registered successfully');
                redirect('components.php');
            } else {
                setFlash('error', 'Failed to register component');
            }
        }
    } else {
        $stmt = $db->prepare("INSERT INTO components (component_id, rfid_tag, rfid_epc, name, brand, price, stock_quantity, specs, compatibility, location, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssdsssss", $component_id, $rfid_tag, $rfid_epc, $name, $brand, $price, $stock, $specs, $compatibility, $location, $image_url);

        if ($stmt->execute()) {
            setFlash('success', 'Component registered successfully');
            redirect('components.php');
        } else {
            setFlash('error', 'Failed to register component');
        }
    }
}

$pageTitle = 'Register Component';
require 'header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">
  <div class="card">
    <div class="card-header">
      <h2><i class="fas fa-plus-circle"></i> Register New Component</h2>
    </div>
    <div class="card-body">
      <form method="POST" action="">

        <!-- RFID Section -->
        <div style="background: #e3f2fd; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;">
          <h3 style="color: #1976d2; margin-bottom: 1rem;"><i class="fas fa-wifi"></i> RFID Information</h3>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
              <label>RFID Tag Number</label>
              <input type="text" name="rfid_tag" class="form-control" placeholder="e.g., RFID-001-ABC" 
                     value="<?php echo generateRFID(); ?>">
              <small style="color: #666;">Unique identifier for RFID scanner</small>
            </div>
            <div class="form-group">
              <label>RFID EPC (Electronic Product Code)</label>
              <input type="text" name="rfid_epc" class="form-control" placeholder="e.g., EPC-1234567890">
              <small style="color: #666;">Electronic Product Code from RFID tag</small>
            </div>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
          <div class="form-group">
            <label>Component ID *</label>
            <input type="text" name="component_id" class="form-control" placeholder="e.g., ENG-001" required>
          </div>
          <div class="form-group">
            <label>Name *</label>
            <input type="text" name="name" class="form-control" placeholder="Component name" required>
          </div>
          <div class="form-group">
            <label>Brand *</label>
            <input type="text" name="brand" class="form-control" placeholder="Brand name" required>
          </div>
          <div class="form-group">
            <label>Price (₱) *</label>
            <input type="number" name="price" step="0.01" class="form-control" placeholder="0.00" required>
          </div>
          <div class="form-group">
            <label>Stock Quantity *</label>
            <input type="number" name="stock_quantity" class="form-control" placeholder="0" required>
          </div>
          <div class="form-group">
            <label>Storage Location</label>
            <input type="text" name="location" class="form-control" placeholder="e.g., Warehouse A-Shelf-1">
          </div>
          <div class="form-group">
            <label>Compatibility</label>
            <input type="text" name="compatibility" class="form-control" placeholder="e.g., Honda Civic 2016-2021">
          </div>
          <div class="form-group">
            <label>Image URL</label>
            <input type="url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg">
          </div>
          <div class="form-group" style="grid-column: 1 / -1;">
            <label>Specifications</label>
            <textarea name="specs" class="form-control" rows="4" placeholder="Technical specifications..."></textarea>
          </div>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #eee;">
          <button type="submit" class="btn btn-primary" style="flex: 1;">
            <i class="fas fa-save"></i> Save Component
          </button>
          <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require 'footer.php'; ?>