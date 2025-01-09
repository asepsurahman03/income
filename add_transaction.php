<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require 'includes/db.php';

$user_id = $_SESSION['user_id'];

// Jika form di-submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $date = $_POST['date'];

    // Validasi input
    if ($type && $amount && $date) {
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description, date) VALUES (:user_id, :type, :amount, :description, :date)");
        $stmt->execute([
            'user_id' => $user_id,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'date' => $date
        ]);

        $_SESSION['success_message'] = "Transaksi berhasil ditambahkan!";
        header("Location: transactions.php"); // Alihkan ke halaman daftar transaksi jika berhasil
        exit();
    } else {
        $error = "Harap isi semua kolom!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Tambah Transaksi</title>
</head>
<body class="bg-gray-100">
    <div class="flex flex-col lg:flex-row h-full lg:h-screen">
        <!-- Sidebar -->
        <div class="lg:w-1/4 w-full">
            <?php include 'includes/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-4 md:p-6 bg-gray-100">
            <h1 class="text-2xl font-bold mb-6 text-gray-800">Tambah Transaksi</h1>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 text-red-700 p-4 mb-6 rounded shadow-md">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="bg-white p-6 rounded-lg shadow-md">
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Jenis Transaksi</label>
                    <select name="type" class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="income">Pendapatan</option>
                        <option value="expense">Pengeluaran</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Jumlah (Rp)</label>
                    <input type="number" name="amount" class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Deskripsi</label>
                    <textarea name="description" class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" rows="4"></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Tanggal</label>
                    <input type="date" name="date" class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                </div>

                <button type="submit" class="w-full py-3 bg-blue-600 text-white font-bold rounded-lg shadow-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all">
                    Tambah Transaksi
                </button>
            </form>
        </div>
    </div>
</body>
</html>
