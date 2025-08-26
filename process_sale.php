<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "admin");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process the sale
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantities = $_POST['quantities'];
    $paymentAmount = floatval($_POST['paymentAmount']);
    
    // Calculate total and update stock
    $total = 0;
    $saleItems = [];
    
    foreach ($quantities as $productId => $quantity) {
        $quantity = intval($quantity);
        if ($quantity > 0) {
            // Get product details
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($product && $product['quantity'] >= $quantity) {
                $subtotal = $product['price'] * $quantity;
                $total += $subtotal;
                
                $saleItems[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $product['price'],
                    'subtotal' => $subtotal,
                    'product_name' => $product['product_name']
                ];
            }
        }
    }
    
    if ($total > 0 && $paymentAmount >= $total) {
        // Insert sale record
        $changeAmount = $paymentAmount - $total;
        $stmt = $conn->prepare("INSERT INTO sales (total, payment, change_amt) VALUES (?, ?, ?)");
        $stmt->bind_param("ddd", $total, $paymentAmount, $changeAmount);
        $stmt->execute();
        $saleId = $stmt->insert_id;
        $stmt->close();
        
        // Insert sale items and update product quantities
        foreach ($saleItems as $item) {
            // Insert sale item
            $stmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $saleId, $item['product_id'], $item['quantity'], $item['price']);
            $stmt->execute();
            $stmt->close();
            
            // Update product quantity
            $stmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
            $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
            $stmt->execute();
            $stmt->close();
        }
        
        // Redirect to receipt page
        header("Location: print_receipt.php?sale_id=" . $saleId);
        exit();
    } else {
        // Handle error
        echo "Error processing sale. Please try again.";
    }
}

$conn->close();
?>