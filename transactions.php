<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require 'includes/db.php';

$user_id = $_SESSION['user_id'];

// Ambil daftar transaksi
$type_filter = $_GET['type'] ?? null;

// Validasi filter
if ($type_filter && !in_array($type_filter, ['income', 'expense'])) {
    die("Filter tipe tidak valid.");
}

$query = "SELECT t.*, c.name AS category_name FROM transactions t 
          LEFT JOIN categories c ON t.category_id = c.id 
          WHERE t.user_id = :user_id";
$params = ['user_id' => $user_id];

if ($type_filter) {
    $query .= " AND t.type = :type";
    $params['type'] = $type_filter;
}
$query .= " ORDER BY t.date DESC";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error in transactions query: " . $e->getMessage());
    die("Terjadi kesalahan saat mengambil data transaksi.");
}

// Data bulanan
try {
    $month_query = "SELECT YEAR(date) AS year, MONTH(date) AS month, SUM(amount) AS total 
                    FROM transactions 
                    WHERE user_id = :user_id 
                    GROUP BY YEAR(date), MONTH(date)
                    ORDER BY year DESC, month DESC";
    $month_stmt = $conn->prepare($month_query);
    $month_stmt->execute(['user_id' => $user_id]);
    $monthly_data = $month_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error in monthly data query: " . $e->getMessage());
    die("Terjadi kesalahan saat mengambil data bulanan.");
}

// Data pendapatan terbesar dan terkecil
try {
    $income_query = "SELECT MAX(amount) AS max_income, MIN(amount) AS min_income 
                     FROM transactions 
                     WHERE user_id = :user_id AND type = 'income'";
    $income_stmt = $conn->prepare($income_query);
    $income_stmt->execute(['user_id' => $user_id]);
    $income_data = $income_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error in income query: " . $e->getMessage());
    die("Terjadi kesalahan saat mengambil data pendapatan.");
}

try {
    $expense_query = "SELECT MAX(amount) AS max_expense, MIN(amount) AS min_expense 
                      FROM transactions 
                      WHERE user_id = :user_id AND type = 'expense'";
    $expense_stmt = $conn->prepare($expense_query);
    $expense_stmt->execute(['user_id' => $user_id]);
    $expense_data = $expense_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error in expense query: " . $e->getMessage());
    die("Terjadi kesalahan saat mengambil data pengeluaran.");
}

// Debugging
// echo "<pre>";
// print_r($transactions);
// print_r($monthly_data);
// print_r($income_data);
// print_r($expense_data);
// echo "</pre>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Daftar Pendapatan & Pengeluaran</title>
</head>
<body class="bg-gray-100">
    <div class="flex flex-col lg:flex-row h-full lg:h-screen">
        <!-- Sidebar -->
        <div class="lg:w-1/4 w-full">
            <?php include 'includes/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-4 md:p-6 bg-gray-100">
            <h1 class="text-3xl font-bold mb-6">Daftar Pendapatan & Pengeluaran</h1>

            <!-- Filter -->
            <div class="mb-6 flex flex-wrap gap-4">
                <a href="transactions.php" class="px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-300">Semua</a>
                <a href="?type=income" class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-300">Pendapatan</a>
                <a href="?type=expense" class="px-6 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-300">Pengeluaran</a>
            </div>

            <!-- Transactions Table -->
            <div class="mt-6">
                <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
                    <h2 class="text-xl font-bold">Pendapatan & Pengeluaran</h2>
                    
                    <div class="flex flex-wrap gap-4">
                        <!-- Month Filter -->
                        <select id="monthFilter" class="border px-2 py-1 rounded-lg">
                            <option value="">Pilih Bulan</option>
                            <option value="01">Januari</option>
                            <option value="02">Februari</option>
                            <option value="03">Maret</option>
                            <option value="04">April</option>
                            <option value="05">Mei</option>
                            <option value="06">Juni</option>
                            <option value="07">Juli</option>
                            <option value="08">Agustus</option>
                            <option value="09">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                        
                        <!-- Year Filter -->
                        <select id="yearFilter" class="border px-2 py-1 rounded-lg">
                            <option value="">Pilih Tahun</option>
                            <?php
                            $currentYear = date('Y');
                            for ($year = $currentYear + 5; $year >= $currentYear - 10; $year--) {
                                echo "<option value='$year'>$year</option>";
                            }
                            ?>
                        </select>

                        <!-- Filter Button -->
                        <button onclick="filterTransactions()" class="bg-green-500 text-white px-4 py-2 rounded-lg shadow-md hover:bg-green-600 transition duration-300">
                            Filter
                        </button>

                        <!-- Print Button -->
                        <button onclick="printTransactions()" class="bg-blue-500 text-white px-4 py-2 rounded-lg shadow-md hover:bg-blue-600 transition duration-300">
                            <i class="fas fa-print mr-2"></i> Cetak
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table id="transactionsTable" class="w-full bg-white rounded-lg shadow-md">
                        <thead class="bg-gray-200 text-gray-600">
                            <tr>
                                <th class="px-4 py-2">Tanggal</th>
                                <th class="px-4 py-2">Deskripsi</th>
                                <th class="px-4 py-2">Jenis</th>
                                <th class="px-4 py-2">Jumlah</th>
                                <th class="px-4 py-2 print:hidden">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $totalAmount = 0; 
                                if (count($transactions) > 0): 
                                    foreach ($transactions as $transaction): 
                                        if ($transaction['type'] == 'income') {
                                            $totalAmount += $transaction['amount'];
                                        } else {
                                            $totalAmount -= $transaction['amount'];
                                        }
                            ?>
                                        <tr>
                                            <td class="border px-4 py-2" data-date="<?= $transaction['date'] ?>" data-amount="<?= $transaction['amount'] ?>" data-type="<?= $transaction['type'] ?>">
                                                <?= $transaction['date'] ?>
                                            </td>
                                            <td class="border px-4 py-2"><?= $transaction['description'] ?></td>
                                            <td class="border px-4 py-2 <?= $transaction['type'] == 'income' ? 'text-green-600' : 'text-red-600' ?>">
                                                <?= $transaction['type'] == 'income' ? 'Pendapatan' : 'Pengeluaran' ?>
                                            </td>
                                            <td class="border px-4 py-2 <?= $transaction['type'] == 'income' ? 'text-green-600' : 'text-red-600' ?>">
                                                Rp<?= number_format($transaction['amount'], 2) ?>
                                            </td>
                                            <td class="border px-4 py-2 flex space-x-4 justify-center print:hidden">
                                                <a href="detail_transaction.php?id=<?= $transaction['id'] ?>" class="text-blue-500">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_transaction.php?id=<?= $transaction['id'] ?>" class="text-yellow-500">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete_transaction.php?id=<?= $transaction['id'] ?>" class="text-red-500" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                            <?php 
                                    endforeach; 
                                else: 
                            ?>
                                    <tr>
                                        <td colspan="5" class="border px-4 py-2 text-center">Tidak ada data Pendapatan & Pengeluaran</td>
                                    </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Total Amount Section -->
                <div id="totalAmountSection" class="mt-4 text-right print:hidden">
                    <h3 class="text-lg font-bold">Total Jumlah: <span id="totalAmount" class="text-blue-600">Rp<?= number_format($totalAmount, 2) ?></span></h3>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


<script>
    function filterTransactions() {
        const month = document.getElementById('monthFilter').value;
        const year = document.getElementById('yearFilter').value;
        const rows = document.querySelectorAll('#transactionsTable tbody tr');

        let filteredTotal = 0;

        rows.forEach(row => {
            const date = row.querySelector('[data-date]').getAttribute('data-date');
            const amount = parseFloat(row.querySelector('[data-date]').getAttribute('data-amount'));
            const type = row.querySelector('[data-date]').getAttribute('data-type');
            const [rowYear, rowMonth] = date.split('-');

            if ((month && rowMonth !== month) || (year && rowYear !== year)) {
                row.style.display = 'none';
            } else {
                row.style.display = '';
                if (type === 'income') {
                    filteredTotal += amount;
                } else {
                    filteredTotal -= amount;
                }
            }
        });

        document.getElementById('totalAmount').textContent = `Rp${filteredTotal.toLocaleString('id-ID', { minimumFractionDigits: 2 })}`;
    }

    function printTransactions() {
        const month = document.getElementById('monthFilter').value;
        const year = document.getElementById('yearFilter').value;
        const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
        const monthName = month ? monthNames[parseInt(month, 10) - 1] : "";
        const description = `Pendapatan & Pengeluaran untuk bulan ${monthName} ${year}`;

        const visibleRows = Array.from(document.querySelectorAll('#transactionsTable tbody tr')).filter(row => row.style.display !== 'none');

        let printContents = `<h2 class='text-center text-lg mb-4'>${description}</h2>`;
        printContents += '<table class="w-full bg-white rounded-lg shadow-md">' +
                          document.querySelector('#transactionsTable thead').outerHTML;

        printContents += '<tbody>';
        visibleRows.forEach(row => {
            printContents += row.outerHTML;
        });
        printContents += '</tbody></table>';

        const filteredTotal = document.getElementById('totalAmount').textContent;
        printContents += `<div class='mt-4 text-right'><h3 class='text-lg font-bold'>Total Jumlah: <span class='text-blue-600'>${filteredTotal}</span></h3></div>`;

        const originalContents = document.body.innerHTML;

        document.body.innerHTML = `<h1 class="text-center text-xl font-bold mb-4">Laporan Pendapatan & Pengeluaran</h1>` + printContents;
        window.print();
        document.body.innerHTML = originalContents;
        window.location.reload();
    }
</script>



            <!-- Income and Expense Extremes -->
            <!-- <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
                <div class="bg-green-100 p-6 rounded-lg shadow-md border-t-4 border-green-600">
                    <h3 class="text-2xl font-semibold text-green-600 mb-4">Pendapatan Terbesar dan Terkecil</h3>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <i class="fas fa-arrow-up text-green-600 mr-2"></i>
                            <p class="text-gray-700">Pendapatan Terbesar: <span class="font-bold">Rp<?= number_format($income_data['max_income'], 2) ?></span></p>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-arrow-down text-red-600 mr-2"></i>
                            <p class="text-gray-700">Pendapatan Terkecil: <span class="font-bold">Rp<?= number_format($income_data['min_income'], 2) ?></span></p>
                        </div>
                    </div>
                </div> -->

                <!-- Largest and Smallest Expense -->
                <!-- <div class="bg-red-100 p-6 rounded-lg shadow-md border-t-4 border-red-600">
                    <h3 class="text-2xl font-semibold text-red-600 mb-4">Pengeluaran Terbesar dan Terkecil</h3>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <i class="fas fa-arrow-up text-red-600 mr-2"></i>
                            <p class="text-gray-700">Pengeluaran Terbesar: <span class="font-bold">Rp<?= number_format($expense_data['max_expense'], 2) ?></span></p>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-arrow-down text-green-600 mr-2"></i>
                            <p class="text-gray-700">Pengeluaran Terkecil: <span class="font-bold">Rp<?= number_format($expense_data['min_expense'], 2) ?></span></p>
                        </div>
                    </div>
                </div>
            </div> -->


        </div>
    </div>
</body>
</html>
