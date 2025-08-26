<?php
// ---------------------------
// Inventory (Enhanced)
// - Add / Edit / Delete Product
// - Category support
// - Search + Pagination
// - Low-stock alert styling
// - Stock logs (stock_logs table)
// ---------------------------

// Start session
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// DB connection
include 'db.php';

// ---- CONFIG ----
$LOW_STOCK_THRESHOLD = 10;         // you can tweak
$PAGE_SIZE = 20;                    // results per page

// Helper: sanitize numeric
function to_int($v) { return (int)filter_var($v, FILTER_SANITIZE_NUMBER_INT); }
function to_float($v) { return (float)filter_var($v, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION); }

// Helper: log stock changes
function log_stock_change(mysqli $conn, int $product_id, int $change, string $note = ''): void {
    $stmt = $conn->prepare("INSERT INTO stock_logs (product_id, change_qty, note, changed_at, changed_by) VALUES (?,?,?,?,?)");
    $now = date('Y-m-d H:i:s');
    $user = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
    $stmt->bind_param('iisss', $product_id, $change, $note, $now, $user);
    $stmt->execute();
    $stmt->close();
}

// Ensure tables/columns exist (lightweight guard; suppress errors if no rights)
$conn->query("CREATE TABLE IF NOT EXISTS stock_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  change_qty INT NOT NULL,
  note VARCHAR(255) DEFAULT NULL,
  changed_at DATETIME NOT NULL,
  changed_by VARCHAR(100) DEFAULT NULL,
  INDEX (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS category VARCHAR(100) NULL AFTER product_name");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS barcode VARCHAR(100) NULL AFTER price");

// ---------------------------
// ACTION HANDLERS
// ---------------------------
$flash = [ 'type' => null, 'msg' => null ];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['product_name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $stock = to_int($_POST['stock'] ?? 0);
        $price = to_float($_POST['price'] ?? 0);
        $barcode = trim($_POST['barcode'] ?? '');

        if ($name === '') {
            $flash = ['type' => 'danger', 'msg' => 'Product name is required'];
        } else {
            $stmt = $conn->prepare("INSERT INTO products (product_name, category, stock, price, barcode) VALUES (?,?,?,?,?)");
            $stmt->bind_param('ssids', $name, $category, $stock, $price, $barcode);
            if ($stmt->execute()) {
                $newId = $stmt->insert_id;
                if ($stock !== 0) log_stock_change($conn, $newId, $stock, 'Initial stock');
                $flash = ['type' => 'success', 'msg' => 'Product added successfully'];
            } else {
                $flash = ['type' => 'danger', 'msg' => 'Failed to add product: ' . $conn->error];
            }
            $stmt->close();
        }
    }

    if ($action === 'edit') {
        $id = to_int($_POST['id'] ?? 0);
        $name = trim($_POST['product_name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $stock = to_int($_POST['stock'] ?? 0);
        $price = to_float($_POST['price'] ?? 0);
        $barcode = trim($_POST['barcode'] ?? '');

        if ($id <= 0) { $flash = ['type' => 'danger', 'msg' => 'Invalid product ID']; }
        else {
            // get existing stock
            $cur = $conn->prepare("SELECT stock FROM products WHERE id=?");
            $cur->bind_param('i', $id);
            $cur->execute();
            $res = $cur->get_result();
            $old = $res->fetch_assoc();
            $cur->close();
            if (!$old) {
                $flash = ['type' => 'danger', 'msg' => 'Product not found'];
            } else {
                $stmt = $conn->prepare("UPDATE products SET product_name=?, category=?, stock=?, price=?, barcode=? WHERE id=?");
                $stmt->bind_param('ssidsi', $name, $category, $stock, $price, $barcode, $id);
                if ($stmt->execute()) {
                    $delta = $stock - (int)$old['stock'];
                    if ($delta !== 0) log_stock_change($conn, $id, $delta, 'Edit product');
                    $flash = ['type' => 'success', 'msg' => 'Product updated'];
                } else {
                    $flash = ['type' => 'danger', 'msg' => 'Failed to update: ' . $conn->error];
                }
                $stmt->close();
            }
        }
    }

    if ($action === 'delete') {
        $id = to_int($_POST['id'] ?? 0);
        if ($id <= 0) { $flash = ['type' => 'danger', 'msg' => 'Invalid product ID']; }
        else {
            // Optionally log remaining stock as negative
            $cur = $conn->prepare("SELECT stock FROM products WHERE id=?");
            $cur->bind_param('i', $id);
            $cur->execute();
            $res = $cur->get_result();
            $row = $res->fetch_assoc();
            $cur->close();

            $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                if ($row) {
                    $remain = (int)$row['stock'];
                    if ($remain !== 0) log_stock_change($conn, $id, -$remain, 'Product deleted');
                }
                $flash = ['type' => 'success', 'msg' => 'Product deleted'];
            } else {
                $flash = ['type' => 'danger', 'msg' => 'Failed to delete: ' . $conn->error];
            }
            $stmt->close();
        }
    }
}

// ---------------------------
// SEARCH + PAGINATION
// ---------------------------
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $PAGE_SIZE;

$where = '';
$params = [];
$types = '';

if ($q !== '') {
    $where = "WHERE product_name LIKE ? OR category LIKE ? OR barcode LIKE ?";
    $like = "%$q%";
    $params = [$like, $like, $like];
    $types = 'sss';
}

// Count
if ($where) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM products $where");
    $stmt->bind_param($types, ...$params);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM products");
}
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

// Fetch paged rows
if ($where) {
    $sql = "SELECT id, product_name, IFNULL(category,'') AS category, stock, price, IFNULL(barcode,'') AS barcode
            FROM products $where ORDER BY product_name ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    // add limit/offset
    $types2 = $types . 'ii';
    $params2 = array_merge($params, [$PAGE_SIZE, $offset]);
    $stmt->bind_param($types2, ...$params2);
} else {
    $sql = "SELECT id, product_name, IFNULL(category,'') AS category, stock, price, IFNULL(barcode,'') AS barcode
            FROM products ORDER BY product_name ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $PAGE_SIZE, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

$total_pages = max(1, (int)ceil($total / $PAGE_SIZE));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Inventory - Sari-Sari Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="sidebar.css">
    <style>
        body { background:#f3f5f9; }
        .main-content { margin-left: 230px; padding: 24px; }
        .page-header { font-size: 22px; font-weight: 700; color:#142883; }
        table { background:#fff; }
        th { background:#142883; color:#fff; font-weight:600; }
        .low-stock { color:#d93025; font-weight:700; }
        .card { border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .btn-primary { background:#142883; border-color:#142883; }
        .btn-primary:hover { background:#1d3ab3; border-color:#1d3ab3; }
        .badge-low { background:#ffe5e0; color:#d93025; }
        .search-group .form-control { border-top-right-radius: 0; border-bottom-right-radius: 0; }
        .search-group .btn { border-top-left-radius: 0; border-bottom-left-radius: 0; }
        .pagination .page-link { color:#142883; }
        .pagination .active .page-link { background:#142883; border-color:#142883; }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="page-header mb-0"><i class="fas fa-warehouse me-2"></i>Inventory</h1>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fa fa-plus me-2"></i>Add Product
            </button>
        </div>
    </div>

    <?php if ($flash['msg']): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flash['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <form class="row g-2 align-items-center" method="get">
                <div class="col-md-6">
                    <div class="input-group search-group">
                        <input type="text" name="q" value="<?= htmlspecialchars($q); ?>" class="form-control" placeholder="Search by name, category, or barcode" />
                        <button class="btn btn-outline-secondary" type="submit"><i class="fa fa-search"></i></button>
                    </div>
                </div>
                <div class="col-md-6 text-md-end small">
                    <span class="me-2">Total: <strong><?= number_format($total); ?></strong></span>
                    <span>Low-stock threshold: <span class="badge badge-low ms-1">&lt; <?= (int)$LOW_STOCK_THRESHOLD; ?></span></span>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width:70px;">ID</th>
                    <th>Product</th>
                    <th style="width:160px;">Category</th>
                    <th style="width:120px;">Stock</th>
                    <th style="width:140px;">Price</th>
                    <th style="width:160px;">Barcode</th>
                    <th style="width:170px;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$row['id']; ?></td>
                    <td><?= htmlspecialchars($row['product_name']); ?></td>
                    <td><?= htmlspecialchars($row['category']); ?></td>
                    <td class="<?= ((int)$row['stock'] < $LOW_STOCK_THRESHOLD) ? 'low-stock' : ''; ?>">
                        <?= (int)$row['stock']; ?>
                    </td>
                    <td>₱<?= number_format((float)$row['price'], 2); ?></td>
                    <td><?= htmlspecialchars($row['barcode']); ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-2 btn-edit"
                                data-id="<?= (int)$row['id']; ?>"
                                data-name='<?= htmlspecialchars($row['product_name'], ENT_QUOTES); ?>'
                                data-category='<?= htmlspecialchars($row['category'], ENT_QUOTES); ?>'
                                data-stock='<?= (int)$row['stock']; ?>'
                                data-price='<?= (float)$row['price']; ?>'
                                data-barcode='<?= htmlspecialchars($row['barcode'], ENT_QUOTES); ?>'
                                data-bs-toggle="modal" data-bs-target="#editModal">
                            <i class="fa fa-pen"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-delete"
                                data-id="<?= (int)$row['id']; ?>"
                                data-name='<?= htmlspecialchars($row['product_name'], ENT_QUOTES); ?>'
                                data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No products found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <nav aria-label="Page navigation" class="mt-3">
        <ul class="pagination">
            <?php
            // Build base query string preserving q
            $qs = $q !== '' ? '&q=' . urlencode($q) : '';
            $prev = max(1, $page - 1);
            $next = min($total_pages, $page + 1);
            ?>
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=1<?= $qs; ?>">&laquo; First</a>
            </li>
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $prev; ?><?= $qs; ?>">&lsaquo; Prev</a>
            </li>
            <li class="page-item active"><span class="page-link">Page <?= $page; ?> / <?= $total_pages; ?></span></li>
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $next; ?><?= $qs; ?>">Next &rsaquo;</a>
            </li>
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $total_pages; ?><?= $qs; ?>">Last &raquo;</a>
            </li>
        </ul>
    </nav>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="add">
        <div class="mb-3">
          <label class="form-label">Product Name</label>
          <input type="text" name="product_name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Category</label>
          <input type="text" name="category" class="form-control" placeholder="e.g. Snacks, Drinks">
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Stock</label>
            <input type="number" name="stock" class="form-control" value="0" min="0" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Price</label>
            <input type="number" step="0.01" name="price" class="form-control" value="0.00" min="0" required>
          </div>
        </div>
        <div class="mt-3">
          <label class="form-label">Barcode (optional)</label>
          <input type="text" name="barcode" class="form-control" placeholder="Scan or enter barcode">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit-id">
        <div class="mb-3">
          <label class="form-label">Product Name</label>
          <input type="text" name="product_name" id="edit-name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Category</label>
          <input type="text" name="category" id="edit-category" class="form-control">
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Stock</label>
            <input type="number" name="stock" id="edit-stock" class="form-control" min="0" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Price</label>
            <input type="number" step="0.01" name="price" id="edit-price" class="form-control" min="0" required>
          </div>
        </div>
        <div class="mt-3">
          <label class="form-label">Barcode (optional)</label>
          <input type="text" name="barcode" id="edit-barcode" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Update</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger"><i class="fa fa-triangle-exclamation me-2"></i>Delete Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete-id">
        <p class="mb-0">Are you sure you want to delete <strong id="delete-name"></strong>? This cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Populate Edit Modal
const editButtons = document.querySelectorAll('.btn-edit');
editButtons.forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('edit-id').value = btn.dataset.id;
    document.getElementById('edit-name').value = btn.dataset.name;
    document.getElementById('edit-category').value = btn.dataset.category || '';
    document.getElementById('edit-stock').value = btn.dataset.stock;
    document.getElementById('edit-price').value = btn.dataset.price;
    document.getElementById('edit-barcode').value = btn.dataset.barcode || '';
  });
});

// Populate Delete Modal
const delButtons = document.querySelectorAll('.btn-delete');
delButtons.forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('delete-id').value = btn.dataset.id;
    document.getElementById('delete-name').textContent = btn.dataset.name;
  });
});
</script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>