<?php
$pageTitle = 'Admin Panel — Authentic Belgian Waffle';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$foodCount  = fetchOne("SELECT COUNT(*) AS n FROM food_items")['n'];
$orderCount = fetchOne("SELECT COUNT(*) AS n FROM orders")['n'];
$userCount  = fetchOne("SELECT COUNT(*) AS n FROM users")['n'];
$msgCount   = fetchOne("SELECT COUNT(*) AS n FROM contact_messages WHERE is_read=0")['n'];
$foods      = fetchAll("SELECT f.*, c.slug AS cat_slug, c.name AS cat_name FROM food_items f JOIN categories c ON f.category_id=c.id ORDER BY f.id DESC");
$orders     = fetchAll("SELECT * FROM orders ORDER BY created_at DESC LIMIT 15");
$messages   = fetchAll("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 10");
$categories = fetchAll("SELECT * FROM categories");
$success = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_food'])) {
    $name     = clean($_POST['name'] ?? ''); $cat_id = (int)($_POST['category_id'] ?? 0);
    $price    = (float)($_POST['price'] ?? 0); $rating = (float)($_POST['rating'] ?? 4.5);
    $desc     = clean($_POST['description'] ?? '');
    if ($name && $cat_id && $price) {
        query("INSERT INTO food_items (category_id,name,description,price,rating) VALUES (?,?,?,?,?)", 'isddd', $cat_id, $name, $desc, $price, $rating);
        $success = "Food item '$name' added successfully!";
        header("Location: " . SITE_URL . "/admin/index.php?success=1"); exit;
    } else { $error = 'Please fill all required fields.'; }
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-hero"><div class="container"><h1>⚙️ Admin <span class="accent">Panel</span></h1><p>Manage menu, orders, users and messages</p></div></div>
<section class="section"><div class="container">
<?php if (isset($_GET['success'])): ?><div class="alert alert-success">Food item added successfully!</div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<!-- Stats -->
<div class="admin-stats">
  <div class="astat-card"><i class="fas fa-utensils"></i><div><strong><?= $foodCount ?></strong><small>Food Items</small></div></div>
  <div class="astat-card"><i class="fas fa-shopping-bag"></i><div><strong><?= $orderCount ?></strong><small>Total Orders</small></div></div>
  <div class="astat-card"><i class="fas fa-users"></i><div><strong><?= $userCount ?></strong><small>Registered Users</small></div></div>
  <div class="astat-card"><i class="fas fa-envelope"></i><div><strong><?= $msgCount ?></strong><small>Unread Messages</small></div></div>
</div>

<!-- Add Food -->
<div class="admin-block">
  <h3><i class="fas fa-plus-circle"></i> Add New Food Item</h3>
  <form method="POST" class="admin-form">
    <div class="form-row">
      <div class="form-group"><label>Item Name *</label><input type="text" name="name" placeholder="e.g. Lotus Waffle" required/></div>
      <div class="form-group"><label>Category *</label>
        <select name="category_id" required>
          <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['emoji'].' '.$c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Price (Rs.) *</label><input type="number" name="price" step="0.01" placeholder="199" required/></div>
      <div class="form-group"><label>Rating (0–5)</label><input type="number" name="rating" step="0.1" min="0" max="5" value="4.5"/></div>
    </div>
    <div class="form-group"><label>Description</label><input type="text" name="description" placeholder="Short, mouth-watering description..."/></div>
    <button type="submit" name="add_food" class="btn btn-gold btn-sm"><i class="fas fa-plus"></i> Add Item</button>
  </form>
</div>

<!-- Food Table -->
<div class="admin-block">
  <h3><i class="fas fa-list"></i> All Food Items (<?= $foodCount ?>)</h3>
  <div class="table-wrap"><table class="data-table">
    <thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Rating</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($foods as $f): ?>
    <tr id="row-<?= $f['id'] ?>">
      <td><?= $f['id'] ?></td>
      <td><?= htmlspecialchars($f['name']) ?></td>
      <td><span class="cat-pill pill-<?= $f['cat_slug'] ?>"><?= htmlspecialchars($f['cat_name']) ?></span></td>
      <td>Rs.<?= number_format($f['price'],2) ?></td>
      <td>⭐ <?= $f['rating'] ?></td>
      <td><span class="status-badge <?= $f['is_available'] ? 'status-active' : 'status-cancelled' ?>"><?= $f['is_available'] ? 'Active' : 'Hidden' ?></span></td>
      <td style="display:flex;gap:.5rem">
        <button class="btn btn-sm btn-outline" onclick="toggleAvail(<?= $f['id'] ?>,<?= $f['is_available'] ?>)" style="font-size:.75rem;padding:.3rem .7rem"><i class="fas fa-eye<?= $f['is_available'] ? '-slash' : '' ?>"></i></button>
        <button class="btn btn-sm btn-danger" onclick="deleteFood(<?= $f['id'] ?>)" style="font-size:.75rem;padding:.3rem .7rem"><i class="fas fa-trash"></i></button>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<!-- Orders -->
<div class="admin-block">
  <h3><i class="fas fa-shopping-bag"></i> Recent Orders</h3>
  <div class="table-wrap"><table class="data-table">
    <thead><tr><th>ID</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
    <tr>
      <td>#<?= $o['id'] ?></td>
      <td><?= htmlspecialchars($o['customer_name']) ?></td>
      <td>Rs.<?= number_format($o['total'],2) ?></td>
      <td><?= strtoupper(htmlspecialchars($o['payment_method'])) ?></td>
      <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
      <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<!-- Messages -->
<div class="admin-block">
  <h3><i class="fas fa-envelope"></i> Contact Messages</h3>
  <?php if (empty($messages)): ?><p style="color:var(--text3)">No messages yet.</p>
  <?php else: foreach ($messages as $m): ?>
    <div style="border:1px solid var(--border);border-radius:var(--r);padding:1rem;margin-bottom:.75rem">
      <strong><?= htmlspecialchars($m['name']) ?></strong> &lt;<?= htmlspecialchars($m['email']) ?>&gt;
      <p style="color:var(--text2);font-size:.85rem;margin:.3rem 0"><?= nl2br(htmlspecialchars($m['message'])) ?></p>
      <small style="color:var(--text3)"><?= date('d M Y H:i', strtotime($m['created_at'])) ?></small>
    </div>
  <?php endforeach; endif; ?>
</div>

</div></section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>const BASE_URL = '<?= SITE_URL ?>';</script>
