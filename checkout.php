<?php
// Memulai session
session_start();

// Memanggil file koneksi
include 'koneksi.php';

// Process order if form is submitted
$order_success = false;
$order_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $customer_name = mysqli_real_escape_string($koneksi, $_POST['customer_name']);
    $customer_phone = mysqli_real_escape_string($koneksi, $_POST['customer_phone']);
    $customer_address = mysqli_real_escape_string($koneksi, $_POST['customer_address']);
    $order_date = date('Y-m-d');
    
    // Get cart data from POST (passed as JSON)
    $cart_data = json_decode($_POST['cart_data'], true);
    
    if (!empty($cart_data)) {
        // Insert each item into penjualan table and update inventory if product exists
        $all_success = true;
        $koneksi->begin_transaction();
        
        foreach ($cart_data as $item) {
            $product_name = mysqli_real_escape_string($koneksi, $item['nama']);
            $quantity = intval($item['qty']);
            $price = intval($item['harga']);
            $total = $quantity * $price;
            
            // Get product image (if exists)
            $img_query = $koneksi->query("SELECT gambar_produk FROM penjualan WHERE nama_produk = '$product_name' LIMIT 1");
            $img_row = $img_query->fetch_assoc();
            $gambar = $img_row['gambar_produk'] ?? '';

            // Update inventory stock for matching product
            $inventory_query = $koneksi->query("SELECT id_barang, jumlah_stok FROM tb_barang WHERE nama_barang = '$product_name' LIMIT 1");
            if ($inventory_query && $inventory_query->num_rows > 0) {
                $inventory_row = $inventory_query->fetch_assoc();
                $id_barang = intval($inventory_row['id_barang']);
                $stok_saat_ini = intval($inventory_row['jumlah_stok']);

                if ($stok_saat_ini < $quantity) {
                    $all_success = false;
                    $order_message = "Stok untuk produk '$product_name' tidak mencukupi. Stok tersedia: $stok_saat_ini.";
                    break;
                }

                $update_stock_query = "UPDATE tb_barang SET jumlah_stok = jumlah_stok - $quantity WHERE id_barang = $id_barang";
                if (!$koneksi->query($update_stock_query)) {
                    $all_success = false;
                    break;
                }

                $insert_transaksi_query = "INSERT INTO tb_transaksi_stok (id_barang, jenis_transaksi, jumlah_perubahan, tanggal_transaksi, catatan) VALUES ($id_barang, 'KELUAR', $quantity, NOW(), 'Penjualan checkout')";
                if (!$koneksi->query($insert_transaksi_query)) {
                    $all_success = false;
                    break;
                }
            }
            
            // Insert sale record
            $insert_query = "INSERT INTO penjualan (tanggal, nama_produk, jumlah_terjual, harga_satuan, total_penjualan, gambar_produk) 
                           VALUES ('$order_date', '$product_name', $quantity, $price, $total, '$gambar')";
            
            if (!$koneksi->query($insert_query)) {
                $all_success = false;
                break;
            }
        }
        
        if ($all_success) {
            $koneksi->commit();
            $order_success = true;
            $order_message = "Pesanan Anda berhasil disimpan! Terima kasih telah berbelanja di Sweet Bakery.";
        } else {
            $koneksi->rollback();
            if (empty($order_message)) {
                $order_message = "Terjadi kesalahan saat menyimpan pesanan: " . $koneksi->error;
            }
        }
    } else {
        $order_message = "Keranjang Anda kosong!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Sweet Bakery</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --color-cream: #FFF0F5;
            --color-vanilla: #FFFFFF;
            --color-coffee: #8B6D74;
            --color-brown: #FFB7C5;
            --color-gold: #FF6B8B;
            --color-dark-brown: #4A2E35;
            --color-peach: #FFE4E1;
            --color-light-gold: #FFC0CB;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--color-cream);
            color: var(--color-dark-brown);
        }

        .navbar {
            background: linear-gradient(135deg, var(--color-vanilla) 0%, var(--color-cream) 100%);
            box-shadow: 0 4px 20px rgba(107, 83, 68, 0.08);
            padding: 15px 0;
        }

        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--color-dark-brown);
        }

        .navbar-brand .logo-icon {
            color: var(--color-gold);
            margin-right: 0.5rem;
        }

        .checkout-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .checkout-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .checkout-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--color-dark-brown);
            margin-bottom: 10px;
        }

        .checkout-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .checkout-form {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(107, 83, 68, 0.1);
        }

        .checkout-summary {
            background: linear-gradient(135deg, var(--color-vanilla) 0%, var(--color-peach) 100%);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(107, 83, 68, 0.1);
        }

        .form-label {
            font-weight: 600;
            color: var(--color-dark-brown);
            margin-bottom: 8px;
        }

        .form-control {
            border: 2px solid var(--color-light-gold);
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            color: var(--color-dark-brown);
        }

        .form-control:focus {
            border-color: var(--color-gold);
            box-shadow: 0 0 0 0.2rem rgba(212, 165, 116, 0.25);
        }

        .form-control::placeholder {
            color: var(--color-coffee);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .summary-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-dark-brown);
            margin-bottom: 20px;
        }

        .summary-item {
            background: white;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid var(--color-gold);
        }

        .summary-item-name {
            font-weight: 600;
            color: var(--color-dark-brown);
        }

        .summary-item-price {
            color: var(--color-gold);
            font-weight: 700;
        }

        .summary-total {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
            border: 2px solid var(--color-gold);
        }

        .summary-total-label {
            font-size: 1rem;
            color: var(--color-coffee);
            margin-bottom: 8px;
        }

        .summary-total-value {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--color-gold);
        }

        .btn-order {
            background: linear-gradient(135deg, var(--color-coffee) 0%, var(--color-brown) 100%);
            color: white;
            border: none;
            padding: 14px 30px;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 50px;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            box-shadow: 0 8px 20px rgba(139, 111, 71, 0.3);
        }

        .btn-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(139, 111, 71, 0.4);
        }

        .btn-order:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .back-link {
            color: var(--color-gold);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 30px;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: var(--color-coffee);
        }

        .success-message {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
        }

        .error-message {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
        }

        .empty-cart-message {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(107, 83, 68, 0.1);
        }

        .empty-cart-message i {
            font-size: 3rem;
            color: var(--color-gold);
            margin-bottom: 20px;
        }

        .empty-cart-message p {
            color: var(--color-coffee);
            font-size: 1.1rem;
            margin-bottom: 20px;
        }

        .btn-back-home {
            background: linear-gradient(135deg, var(--color-gold) 0%, var(--color-light-gold) 100%);
            color: var(--color-dark-brown);
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-back-home:hover {
            transform: translateY(-2px);
            color: var(--color-dark-brown);
        }

        footer {
            background: var(--color-dark-brown);
            color: var(--color-vanilla);
            padding: 30px 20px 20px;
            text-align: center;
            margin-top: 60px;
        }

        @media (max-width: 768px) {
            .checkout-content {
                grid-template-columns: 1fr;
            }

            .checkout-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>

    <!-- ==================== NAVBAR ==================== -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="home.php">
                <i class="bi bi-cup-hot-fill logo-icon"></i> Sweet Bakery
            </a>
        </div>
    </nav>

    <!-- ==================== CHECKOUT CONTENT ==================== -->
    <div class="checkout-container">
        <a href="home.php" class="back-link">
            <i class="bi bi-arrow-left"></i> Kembali ke Toko
        </a>

        <div class="checkout-header">
            <h1>✓ Checkout Pesanan</h1>
            <p style="color: var(--color-coffee);">Lengkapi data dan konfirmasi pesanan Anda</p>
        </div>

        <?php if ($order_success): ?>
            <script>
                localStorage.removeItem('sweetBakeryCart');
            </script>
            <div class="success-message">
                <i class="bi bi-check-circle"></i> <?php echo $order_message; ?>
            </div>
            <div class="empty-cart-message">
                <p>Pesanan Anda sedang diproses. Silakan hubungi kami untuk informasi lebih lanjut.</p>
                <a href="home.php" class="btn-back-home">
                    <i class="bi bi-shop"></i> Belanja Lagi
                </a>
            </div>
        <?php else: ?>
            <div id="checkoutContent">
                <!-- Form akan dimuat via JavaScript -->
            </div>
        <?php endif; ?>
    </div>

    <!-- ==================== FOOTER ==================== -->
    <footer>
        <p>&copy; 2024 Sweet Bakery. Semua hak dilindungi.</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Get cart from localStorage
        const cart = JSON.parse(localStorage.getItem('sweetBakeryCart')) || [];

        function renderCheckoutForm() {
            const checkoutContent = document.getElementById('checkoutContent');

            if (cart.length === 0) {
                checkoutContent.innerHTML = `
                    <div class="empty-cart-message">
                        <i class="bi bi-bag-x"></i>
                        <p>Keranjang Anda kosong. Silakan tambahkan produk terlebih dahulu.</p>
                        <a href="home.php" class="btn-back-home">
                            <i class="bi bi-shop"></i> Kembali ke Toko
                        </a>
                    </div>
                `;
                return;
            }

            let cartHTML = '<div class="checkout-content">';

            // Form section
            cartHTML += `
                <div class="checkout-form">
                    <h3 style="font-family: 'Playfair Display', serif; color: var(--color-dark-brown); margin-bottom: 20px;">
                        Data Pemesan
                    </h3>
                    <form id="orderForm" onsubmit="submitOrder(event)">
                        <div class="form-group">
                            <label for="customerName" class="form-label">Nama Lengkap *</label>
                            <input type="text" class="form-control" id="customerName" name="customer_name" placeholder="Masukkan nama Anda" required>
                        </div>

                        <div class="form-group">
                            <label for="customerPhone" class="form-label">Nomor Telepon *</label>
                            <input type="tel" class="form-control" id="customerPhone" name="customer_phone" placeholder="081234567890" required pattern="[0-9]{10,}">
                        </div>

                        <div class="form-group">
                            <label for="customerAddress" class="form-label">Alamat Pengiriman *</label>
                            <textarea class="form-control" id="customerAddress" name="customer_address" rows="4" placeholder="Masukkan alamat lengkap" required></textarea>
                        </div>

                        <input type="hidden" id="cartDataInput" name="cart_data">

                        <button type="submit" class="btn-order">
                            <i class="bi bi-check-circle"></i> Konfirmasi Pesanan
                        </button>
                    </form>
                </div>

                <div class="checkout-summary">
                    <h3 class="summary-title">Ringkasan Pesanan</h3>
            `;

            let total = 0;
            cart.forEach((item) => {
                const subtotal = item.harga * item.qty;
                total += subtotal;
                
                cartHTML += `
                    <div class="summary-item">
                        <div>
                            <div class="summary-item-name">${item.nama}</div>
                            <small style="color: var(--color-coffee);">Qty: ${item.qty} × Rp ${item.harga.toLocaleString('id-ID')}</small>
                        </div>
                        <div class="summary-item-price">
                            Rp ${subtotal.toLocaleString('id-ID')}
                        </div>
                    </div>
                `;
            });

            cartHTML += `
                    <div class="summary-total">
                        <div class="summary-total-label">Total Pembayaran</div>
                        <div class="summary-total-value">Rp ${total.toLocaleString('id-ID')}</div>
                    </div>

                    <div style="background: var(--color-peach); padding: 15px; border-radius: 10px; margin-top: 20px; text-align: center;">
                        <small style="color: var(--color-coffee);">
                            <i class="bi bi-info-circle"></i> Pembayaran dapat dilakukan saat barang tiba (COD) atau transfer bank.
                        </small>
                    </div>
                </div>
            </div>
            `;

            checkoutContent.innerHTML = cartHTML;
        }

        function submitOrder(event) {
            event.preventDefault();

            // Get form data
            const customerName = document.getElementById('customerName').value;
            const customerPhone = document.getElementById('customerPhone').value;
            const customerAddress = document.getElementById('customerAddress').value;

            // Validate
            if (!customerName || !customerPhone || !customerAddress) {
                alert('Semua field harus diisi!');
                return;
            }

            // Prepare form data
            const formData = new FormData();
            formData.append('customer_name', customerName);
            formData.append('customer_phone', customerPhone);
            formData.append('customer_address', customerAddress);
            formData.append('cart_data', JSON.stringify(cart));

            // Submit to server
            fetch('checkout.php', {
                method: 'POST',
                body: formData
            }).then(response => response.text())
              .then(html => {
                  // Clear localStorage if order is successful
                  if (html.includes('success-message')) {
                      localStorage.removeItem('sweetBakeryCart');
                  }
                  // Render the new HTML structure completely and execute scripts
                  document.open();
                  document.write(html);
                  document.close();
              })
              .catch(error => {
                  console.error('Error:', error);
                  alert('Terjadi kesalahan saat memproses pesanan!');
              });
        }

        // Load checkout form on page load
        renderCheckoutForm();
    </script>

</body>
</html>
