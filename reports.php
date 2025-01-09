<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require 'includes/db.php';

$user_id = $_SESSION['user_id'];

// Fetch monthly data for the current year
$current_year = date('Y');
$stmt = $conn->prepare("SELECT DATE_FORMAT(date, '%m') AS month, 
           SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS income, 
           SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS expense 
    FROM transactions 
    WHERE user_id = :user_id AND YEAR(date) = :year 
    GROUP BY month");
$stmt->execute(['user_id' => $user_id, 'year' => $current_year]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for Chart.js
$months = [];
$incomes = [];
$expenses = [];

foreach ($data as $row) {
    $months[] = date('F', mktime(0, 0, 0, $row['month'], 10));
    $incomes[] = $row['income'];
    $expenses[] = $row['expense'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Laporan</title>
</head>
<body class="bg-gray-100">
    <div class="flex flex-col lg:flex-row h-full lg:h-screen">
        <!-- Sidebar -->
        <div class="lg:w-1/4 w-full">
            <?php include 'includes/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-4 md:p-6 bg-gray-100">
            <h1 class="text-2xl font-bold mb-6 text-gray-800">Laporan Keuangan</h1>

            <!-- Chart -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <canvas id="financialChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('financialChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($months) ?>,
                datasets: [
                    {
                        label: 'Pendapatan',
                        data: <?= json_encode($incomes) ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Pengeluaran',
                        data: <?= json_encode($expenses) ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>
