<?php
require_once 'config.php';
$pageTitle = 'Dashboard';
require 'header.php';

// Get statistics
$db = getDB();
$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM components")->fetch_row()[0],
    'in_stock' => $db->query("SELECT COUNT(*) FROM components WHERE stock_quantity > 0")->fetch_row()[0],
    'transactions' => $db->query("SELECT COUNT(*) FROM transactions WHERE payment_status = 'completed'")->fetch_row()[0],
    'revenue' => $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM transactions WHERE payment_status = 'completed'")->fetch_row()[0]
];

// Get recent components
$recent = $db->query("SELECT * FROM components ORDER BY id DESC LIMIT 5");
$recentComponents = [];
while ($row = $recent->fetch_assoc()) {
    $recentComponents[] = $row;
}

// Get recent transactions
$trans = $db->query("SELECT t.*, c.name as component_name FROM transactions t 
                     JOIN components c ON t.component_id = c.id 
                     ORDER BY t.id DESC LIMIT 5");
$recentTransactions = [];
while ($row = $trans->fetch_assoc()) {
    $recentTransactions[] = $row;
}
?>

<!-- Hero Section -->
<div style="background: linear-gradient(135deg, var(--primary), var(--secondary)); 
            border-radius: 24px; padding: 3rem; color: white; margin-bottom: 2rem;
            position: relative; overflow: hidden;">
  <div style="position: relative; z-index: 1;">
    <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">
      Welcome<?php echo isLoggedIn() ? ', ' . $_SESSION['user']['username'] : ''; ?>!
    </h1>
    <p style="font-size: 1.1rem; opacity: 0.9; max-width: 600px;">
      Manage your motor components efficiently. Track inventory, process payments, and monitor sales all in one place.
    </p>
  </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-4 mb-4">
  <div class="card" style="padding: 1.5rem; display: flex; align-items: center; gap: 1rem;">
    <div style="width: 60px; height: 60px; background: #e3f2fd; border-radius: 12px; 
                display: flex; align-items: center; justify-content: center; color: #1976d2; font-size: 1.5rem;">
      <i class="fas fa-box"></i>
    </div>
    <div>
      <h3 style="font-size: 2rem; color: var(--primary);"><?php echo $stats['total']; ?></h3>
      <p style="color: #666;">Total Components</p>
    </div>
  </div>

  <div class="card" style="padding: 1.5rem; display: flex; align-items: center; gap: 1rem;">
    <div style="width: 60px; height: 60px; background: #e8f5e9; border-radius: 12px; 
                display: flex; align-items: center; justify-content: center; color: #388e3c; font-size: 1.5rem;">
      <i class="fas fa-check-circle"></i>
    </div>
    <div>
      <h3 style="font-size: 2rem; color: var(--primary);"><?php echo $stats['in_stock']; ?></h3>
      <p style="color: #666;">In Stock</p>
    </div>
  </div>

  <div class="card" style="padding: 1.5rem; display: flex; align-items: center; gap: 1rem;">
    <div style="width: 60px; height: 60px; background: #fff3e0; border-radius: 12px; 
                display: flex; align-items: center; justify-content: center; color: #f57c00; font-size: 1.5rem;">
      <i class="fas fa-shopping-cart"></i>
    </div>
    <div>
      <h3 style="font-size: 2rem; color: var(--primary);"><?php echo $stats['transactions']; ?></h3>
      <p style="color: #666;">Transactions</p>
    </div>
  </div>

  <div class="card" style="padding: 1.5rem; display: flex; align-items: center; gap: 1rem;">
    <div style="width: 60px; height: 60px; background: #f3e5f5; border-radius: 12px; 
                display: flex; align-items: center; justify-content: center; color: #7b1fa2; font-size: 1.5rem;">
      <i class="fas fa-peso-sign"></i>
    </div>
    <div>
      <h3 style="font-size: 2rem; color: var(--primary);"><?php echo formatPrice($stats['revenue']); ?></h3>
      <p style="color: #666;">Total Revenue</p>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-3 mb-4">
  <a href="register_component.php" style="text-decoration: none;">
    <div class="card" style="padding: 2rem; text-align: center; transition: all 0.3s; cursor: pointer;">
      <div style="width: 70px; height: 70px; background: #e3f2fd; border-radius: 16px; 
                  display: flex; align-items: center; justify-content: center; 
                  color: #1976d2; font-size: 2rem; margin: 0 auto 1rem;">
        <i class="fas fa-plus"></i>
      </div>
      <h3 style="color: var(--primary); margin-bottom: 0.5rem;">Register Component</h3>
      <p style="color: #666; font-size: 0.9rem;">Add new motor components to inventory</p>
    </div>
  </a>

  <a href="components.php" style="text-decoration: none;">
    <div class="card" style="padding: 2rem; text-align: center; transition: all 0.3s; cursor: pointer;">
      <div style="width: 70px; height: 70px; background: #e8f5e9; border-radius: 16px; 
                  display: flex; align-items: center; justify-content: center; 
                  color: #388e3c; font-size: 2rem; margin: 0 auto 1rem;">
        <i class="fas fa-search"></i>
      </div>
      <h3 style="color: var(--primary); margin-bottom: 0.5rem;">View Components</h3>
      <p style="color: #666; font-size: 0.9rem;">Browse and manage your catalog</p>
    </div>
  </a>

  <a href="payment.php" style="text-decoration: none;">
    <div class="card" style="padding: 2rem; text-align: center; transition: all 0.3s; cursor: pointer;">
      <div style="width: 70px; height: 70px; background: #fff3e0; border-radius: 16px; 
                  display: flex; align-items: center; justify-content: center; 
                  color: #f57c00; font-size: 2rem; margin: 0 auto 1rem;">
        <i class="fas fa-credit-card"></i>
      </div>
      <h3 style="color: var(--primary); margin-bottom: 0.5rem;">Process Payment</h3>
      <p style="color: #666; font-size: 0.9rem;">Handle cashless transactions</p>
    </div>
  </a>
</div>

<!-- Recent Activity -->
<div class="grid grid-2">
  <div class="card">
    <div class="card-header">
      <h2><i class="fas fa-box"></i> Recent Components</h2>
    </div>
    <div class="card-body">
      <?php if (empty($recentComponents)): ?>
        <p class="text-center" style="color: #999; padding: 2rem;">No components registered yet</p>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Brand</th>
              <th>Price</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentComponents as $comp): ?>
            <tr>
              <td><?php echo sanitize($comp['name']); ?></td>
              <td><?php echo sanitize($comp['brand']); ?></td>
              <td><?php echo formatPrice($comp['price']); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h2><i class="fas fa-history"></i> Recent Transactions</h2>
    </div>
    <div class="card-body">
      <?php if (empty($recentTransactions)): ?>
        <p class="text-center" style="color: #999; padding: 2rem;">No transactions yet</p>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Component</th>
              <th>Amount</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentTransactions as $t): ?>
            <tr>
              <td><?php echo sanitize($t['component_name']); ?></td>
              <td><?php echo formatPrice($t['total_amount']); ?></td>
              <td>
                <span class="badge badge-<?php echo $t['payment_status'] == 'completed' ? 'success' : 'warning'; ?>">
                  <?php echo $t['payment_status']; ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require 'footer.php'; ?>