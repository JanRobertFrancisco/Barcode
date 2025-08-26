    <?php
    include 'db.php';

    $error = "";
    $success = "";

    if (!isset($_GET['id'])) {
        header("Location: sales_management.php");
        exit();
    }

    $sale_id = intval($_GET['id']);

    // Fetch sale info
    $sale_res = $conn->query("SELECT * FROM sales WHERE id=$sale_id");
    if ($sale_res->num_rows == 0) {
        header("Location: sales_management.php");
        exit();
    }
    $sale = $sale_res->fetch_assoc();

    // Fetch sale items
    $items_res = $conn->query("
        SELECT si.*, p.product_name, p.quantity AS stock
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        WHERE si.sale_id=$sale_id
    ");

    // Fetch all products
    $products_res = $conn->query("SELECT * FROM products ORDER BY product_name ASC");

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $product_ids = $_POST['product_id'];
        $quantities = $_POST['quantity'];
        $payment = floatval($_POST['payment']);
        
        if (empty($product_ids)) {
            $error = "At least one product is required.";
        } else {
            $total = 0;

            // Fetch old sale items to restore stock
            $old_items = [];
            $res = $conn->query("SELECT product_id, quantity FROM sale_items WHERE sale_id=$sale_id");
            while($row = $res->fetch_assoc()) {
                $old_items[$row['product_id']] = $row['quantity'];
            }

            // Validate stock
            foreach ($product_ids as $i => $pid) {
                $stmt = $conn->prepare("SELECT price, quantity FROM products WHERE id=?");
                $stmt->bind_param("i", $pid);
                $stmt->execute();
                $p = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                $available_stock = $p['quantity'] + ($old_items[$pid] ?? 0);
                if ($quantities[$i] > $available_stock) {
                    $error = "Not enough stock for product ID $pid. Available: $available_stock";
                    break;
                }

                $total += $p['price'] * $quantities[$i];
            }

            if (!$error) {
                if ($payment < $total) {
                    $error = "Payment amount is less than total. Please enter a valid payment.";
                } else {
                    $change = $payment - $total;

                    // Update sales table
                    $stmt = $conn->prepare("UPDATE sales SET total_payment=?, payment=?, change_amt=? WHERE id=?");
                    $stmt->bind_param("dddi", $total, $payment, $change, $sale_id);
                    $stmt->execute();
                    $stmt->close();

                    // Delete old sale items
                    $conn->query("DELETE FROM sale_items WHERE sale_id=$sale_id");

                    // Insert new sale items and update stock
                    foreach ($product_ids as $i => $pid) {
                        $stmt = $conn->prepare("SELECT price, quantity FROM products WHERE id=?");
                        $stmt->bind_param("i", $pid);
                        $stmt->execute();
                        $p = $stmt->get_result()->fetch_assoc();
                        $stmt->close();

                        // Insert new sale item
                        $stmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("iiid", $sale_id, $pid, $quantities[$i], $p['price']);
                        $stmt->execute();
                        $stmt->close();

                        // Update stock
                        $new_stock = $p['quantity'] + ($old_items[$pid] ?? 0) - $quantities[$i];
                        $stmt = $conn->prepare("UPDATE products SET quantity=? WHERE id=?");
                        $stmt->bind_param("ii", $new_stock, $pid);
                        $stmt->execute();
                        $stmt->close();
                    }

                    $success = "Sale updated successfully!";
                }
            }
        }
    }
    ?>

    <!DOCTYPE html>
    <html>
    <head>
        <title>Edit Sale</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            .main { margin-left: 230px; padding: 20px; }
        </style>
    </head>
    <body>
    <?php include 'sidebar.php'; ?>
    <div class="main">
        <h2>✏ Edit Sale #<?= $sale_id ?></h2>

        <!-- Message Box -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" id="msgBox">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" id="msgBox">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <script>
                setTimeout(() => {
                    window.location.href = 'sales_management.php';
                }, 2000);
            </script>
        <?php endif; ?>

        <form method="POST" id="editSaleForm">
            <div class="row mb-3">
                <div class="col-md-6">
                    <select id="product_id" class="form-select">
                        <option value="">-- Select Product --</option>
                        <?php while($p=$products_res->fetch_assoc()): ?>
                            <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>"><?= htmlspecialchars($p['product_name']) ?> (Stock: <?= $p['quantity'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="number" id="quantity" class="form-control" min="1" value="1">
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-primary w-100" onclick="addToCart()">Add Product</button>
                </div>
            </div>

            <h4>Sale Items</h4>
            <table class="table table-bordered" id="cart_table">
                <thead>
                    <tr><th>Product</th><th>Qty</th><th>Price/unit</th><th>Total</th><th>Action</th></tr>
                </thead>
                <tbody id="cart_body"></tbody>
            </table>

            <div class="mb-3">
                <label>Total (₱): </label> <span id="total">0.00</span>
            </div>

            <div class="mb-3">
                <label>Payment Amount (₱)</label>
                <input type="number" id="payment" name="payment" class="form-control" step="0.01" value="<?= $sale['payment'] ?>" required oninput="calculateChange()">
            </div>

            <div class="mb-3">
                <label>Change (₱): </label> <span id="change">0.00</span>
            </div>

            <button type="submit" class="btn btn-success">Update Sale</button>
            <a href="sales_management.php" class="btn btn-secondary">Back</a>
        </form>
    </div>

    <script>
    let cart = [];

    // Preload existing sale items
    <?php while($item=$items_res->fetch_assoc()): ?>
    cart.push({
        id: <?= $item['product_id'] ?>,
        name: "<?= addslashes($item['product_name']) ?>",
        qty: <?= $item['quantity'] ?>,
        price: <?= $item['price'] ?>
    });
    <?php endwhile; ?>

    renderCart();

    function addToCart() {
        const select = document.getElementById("product_id");
        const qty = parseInt(document.getElementById("quantity").value);
        const price = parseFloat(select.options[select.selectedIndex].dataset.price);
        const name = select.options[select.selectedIndex].text;
        const id = select.value;

        if (!id || qty <= 0) return alert("Select product and valid quantity");

        cart.push({id, name, qty, price});
        renderCart();
    }

    function renderCart() {
        let tbody = document.getElementById("cart_body");
        tbody.innerHTML = "";
        let total = 0;
        cart.forEach((item, index) => {
            total += item.price * item.qty;
            tbody.innerHTML += `<tr>
                <td>${item.name}</td>
                <td>${item.qty}</td>
                <td>₱${item.price.toFixed(2)}</td>
                <td>₱${(item.price*item.qty).toFixed(2)}</td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="removeItem(${index})">Remove</button></td>
                <input type="hidden" name="product_id[]" value="${item.id}">
                <input type="hidden" name="quantity[]" value="${item.qty}"></tr>`;
        });
        document.getElementById("total").innerText = total.toFixed(2);
        calculateChange();
    }

    function removeItem(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function calculateChange() {
        const payment = parseFloat(document.getElementById("payment").value || 0);
        const total = parseFloat(document.getElementById("total").innerText || 0);
        const change = payment - total >= 0 ? payment - total : 0;
        document.getElementById("change").innerText = change.toFixed(2);
    }

    // Auto-hide message box after 2 seconds
    window.addEventListener('DOMContentLoaded', () => {
        const msg = document.getElementById('msgBox');
        if (msg) {
            setTimeout(() => {
                msg.classList.remove('show');
                msg.classList.add('hide');
            }, 2000);
        }
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
