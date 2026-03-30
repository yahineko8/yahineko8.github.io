<?php
require_once 'config.php';
$pageTitle = 'Components';
require 'header.php';

$db = getDB();

// Handle delete
if (isset($_GET['delete']) && isLoggedIn()) {
    $id = intval($_GET['delete']);
    $db->query("DELETE FROM components WHERE id = $id");
    setFlash('success', 'Component deleted successfully');
    redirect('components.php');
}

// Search and filter
$search = $_GET['search'] ?? '';
$brand = $_GET['brand'] ?? '';
$stock = $_GET['stock'] ?? '';
$rfid_filter = $_GET['rfid'] ?? '';

// Build query
$where = [];
if ($search) {
    $search = $db->real_escape_string($search);
    $where[] = "(name LIKE '%$search%' OR brand LIKE '%$search%' OR specs LIKE '%$search%' OR rfid_tag LIKE '%$search%' OR component_id LIKE '%$search%')";
}
if ($brand) {
    $brand = $db->real_escape_string($brand);
    $where[] = "brand = '$brand'";
}
if ($stock) {
    switch($stock) {
        case 'in': $where[] = "stock_quantity > 10"; break;
        case 'low': $where[] = "stock_quantity > 0 AND stock_quantity <= 10"; break;
        case 'out': $where[] = "stock_quantity = 0"; break;
    }
}
if ($rfid_filter) {
    if ($rfid_filter == 'yes') $where[] = "rfid_tag IS NOT NULL AND rfid_tag != ''";
    if ($rfid_filter == 'no') $where[] = "(rfid_tag IS NULL OR rfid_tag = '')";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Get total count
$countResult = $db->query("SELECT COUNT(*) FROM components $whereClause");
$totalItems = $countResult->fetch_row()[0];
$totalPages = ceil($totalItems / $perPage);

// Get components
$result = $db->query("SELECT * FROM components $whereClause ORDER BY id DESC LIMIT $offset, $perPage");
$components = [];
while ($row = $result->fetch_assoc()) {
    $components[] = $row;
}

// Get brands for filter
$brands = $db->query("SELECT DISTINCT brand FROM components ORDER BY brand");
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
  <h1 style="color: var(--primary);"><i class="fas fa-boxes"></i> Component Catalog</h1>
  <a href="register_component.php" class="btn btn-primary">
    <i class="fas fa-plus"></i> Add Component
  </a>
</div>

<!-- Search and Filter -->
<div class="card" style="margin-bottom: 2rem;">
  <div class="card-body">
    <form method="GET" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: end;">
      <div style="flex: 1; min-width: 200px;">
        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Search</label>
        <input type="text" name="search" class="form-control" placeholder="Search by name, brand, ID, or RFID..." 
               value="<?php echo sanitize($search); ?>">
      </div>
      <div style="min-width: 150px;">
        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Brand</label>
        <select name="brand" class="form-control">
          <option value="">All Brands</option>
          <?php while ($b = $brands->fetch_assoc()): ?>
          <option value="<?php echo $b['brand']; ?>" <?php echo $brand == $b['brand'] ? 'selected' : ''; ?>>
            <?php echo $b['brand']; ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div style="min-width: 150px;">
        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Stock</label>
        <select name="stock" class="form-control">
          <option value="">All</option>
          <option value="in" <?php echo $stock == 'in' ? 'selected' : ''; ?>>In Stock</option>
          <option value="low" <?php echo $stock == 'low' ? 'selected' : ''; ?>>Low Stock</option>
          <option value="out" <?php echo $stock == 'out' ? 'selected' : ''; ?>>Out of Stock</option>
        </select>
      </div>
      <div style="min-width: 150px;">
        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">RFID</label>
        <select name="rfid" class="form-control">
          <option value="">All</option>
          <option value="yes" <?php echo $rfid_filter == 'yes' ? 'selected' : ''; ?>>With RFID</option>
          <option value="no" <?php echo $rfid_filter == 'no' ? 'selected' : ''; ?>>Without RFID</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-filter"></i> Filter
      </button>
      <?php if ($search || $brand || $stock || $rfid_filter): ?>
      <a href="components.php" class="btn btn-secondary">Clear</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Components Grid -->
<?php if (empty($components)): ?>
<div class="card" style="padding: 4rem; text-align: center;">
  <i class="fas fa-search" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
  <h3 style="color: #666; margin-bottom: 0.5rem;">No components found</h3>
  <p style="color: #999;">Try adjusting your search or filters</p>
</div>
<?php else: ?>
<div class="grid grid-3" style="margin-bottom: 2rem;">
  <?php foreach ($components as $comp): 
    $stockClass = $comp['stock_quantity'] > 10 ? 'badge-success' : ($comp['stock_quantity'] > 0 ? 'badge-warning' : 'badge-danger');
    $stockText = $comp['stock_quantity'] > 10 ? 'In Stock' : ($comp['stock_quantity'] > 0 ? 'Low Stock' : 'Out of Stock');
    $hasRFID = !empty($comp['rfid_tag']);
  ?>
  <div class="card" style="transition: all 0.3s;">
    <div style="position: relative;">
      <img src="<?php echo $comp['image_url'] ?: 'https://via.placeholder.com/300x200?text=No+Image'; ?>" 
           alt="<?php echo sanitize($comp['name']); ?>" 
           style="width: 100%; height: 200px; object-fit: cover;">
      <?php if ($hasRFID): ?>
      <div style="position: absolute; top: 10px; right: 10px; background: #1976d2; color: white; 
                  padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
        <i class="fas fa-wifi"></i> RFID
      </div>
      <?php endif; ?>
    </div>
    <div style="padding: 1.5rem;">
      <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
        <div>
          <h3 style="font-size: 1.2rem; color: var(--primary); margin-bottom: 0.25rem;">
            <?php echo sanitize($comp['name']); ?>
          </h3>
          <p style="color: #666; font-size: 0.9rem;"><?php echo sanitize($comp['brand']); ?></p>
        </div>
        <span style="font-size: 1.3rem; color: var(--secondary); font-weight: 700;">
          <?php echo formatPrice($comp['price']); ?>
        </span>
      </div>

      <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem; line-height: 1.5;">
        <?php echo substr(sanitize($comp['specs']), 0, 100) . '...'; ?>
      </p>

      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <span class="badge <?php echo $stockClass; ?>">
          <?php echo $stockText; ?> (<?php echo $comp['stock_quantity']; ?>)
        </span>
        <?php if ($hasRFID): ?>
        <span style="font-family: monospace; font-size: 0.8rem; color: #1976d2;">
          <?php echo sanitize($comp['rfid_tag']); ?>
        </span>
        <?php endif; ?>
      </div>

      <div style="display: flex; gap: 0.5rem;">
        <a href="view_component.php?id=<?php echo $comp['id']; ?>" class="btn btn-sm btn-secondary" title="View">
          <i class="fas fa-eye"></i>
        </a>
        <?php if (isLoggedIn()): ?>
        <a href="edit_component.php?id=<?php echo $comp['id']; ?>" class="btn btn-sm" 
           style="background: #fff3e0; color: #f57c00;" title="Edit">
          <i class="fas fa-edit"></i>
        </a>
        <a href="components.php?delete=<?php echo $comp['id']; ?>" 
           onclick="return confirm('Are you sure you want to delete this component?')" 
           class="btn btn-sm" style="background: #ffebee; color: #c62828;" title="Delete">
          <i class="fas fa-trash"></i>
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div style="display: flex; justify-content: center; gap: 0.5rem;">
  <?php if ($page > 1): ?>
  <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&brand=<?php echo urlencode($brand); ?>&stock=<?php echo $stock; ?>&rfid=<?php echo $rfid_filter; ?>" 
     class="btn btn-secondary">← Prev</a>
  <?php endif; ?>

  <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
  <?php if ($i == $page): ?>
  <span class="btn btn-primary"><?php echo $i; ?></span>
  <?php else: ?>
  <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&brand=<?php echo urlencode($brand); ?>&stock=<?php echo $stock; ?>&rfid=<?php echo $rfid_filter; ?>" 
     class="btn btn-secondary"><?php echo $i; ?></a>
  <?php endif; ?>
  <?php endfor; ?>

  <?php if ($page < $totalPages): ?>
  <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&brand=<?php echo urlencode($brand); ?>&stock=<?php echo $stock; ?>&rfid=<?php echo $rfid_filter; ?>" 
     class="btn btn-secondary">Next →</a>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<?php require 'footer.php'; ?>