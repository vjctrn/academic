<?php
require "db.php";
require "crud_manager.php";
$crud = new CrudManager($pdo);

$activeTab = $_GET["report"] ?? "complete";

$rows = [];
if ($activeTab === "complete") {
  $rows = $crud->getCompleteBorrowingReport();
} elseif ($activeTab === "summary") {
  $rows = $crud->getEquipmentBorrowingSummary();
} elseif ($activeTab === "no_transactions") {
  $rows = $crud->getBorrowersWithoutTransactions();
} elseif ($activeTab === "no_equipment") {
  $rows = $crud->getEquipmentWithoutHistory();
} elseif ($activeTab === "combined") {
  $rows = $crud->getCombinedBorrowerActivity();
} elseif ($activeTab === "monthly") {
  $rows = $crud->getMonthlyBorrowingReport();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Week 7 Reports</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-[#24273a] text-[#cad3f5] min-h-screen py-8 px-4">
  <div class="max-w-5xl mx-auto">
    <div class="mb-4">
      <a href="index.php" class="text-sm text-[#8aadf4] hover:underline">&larr; Back to CRUD</a>
    </div>
    <h1 class="text-2xl font-bold mb-4 text-[#cad3f5]">Week 7 thingies</h1>

    <!-- Report Tabs -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-[#363a4f] pb-4">
      <a href="?report=complete" class="px-3 py-1.5 text-sm <?= $activeTab ===
      "complete"
        ? "bg-[#8aadf4] text-[#24273a] font-medium"
        : "bg-[#1e2030] text-[#cad3f5]" ?>">Complete Report</a>
      <a href="?report=summary" class="px-3 py-1.5 text-sm <?= $activeTab ===
      "summary"
        ? "bg-[#8aadf4] text-[#24273a] font-medium"
        : "bg-[#1e2030] text-[#cad3f5]" ?>">Equipment Summary</a>
      <a href="?report=no_transactions" class="px-3 py-1.5 text-sm <?= $activeTab ===
      "no_transactions"
        ? "bg-[#8aadf4] text-[#24273a] font-medium"
        : "bg-[#1e2030] text-[#cad3f5]" ?>">Borrowers w/o History</a>
      <a href="?report=no_equipment" class="px-3 py-1.5 text-sm <?= $activeTab ===
      "no_equipment"
        ? "bg-[#8aadf4] text-[#24273a] font-medium"
        : "bg-[#1e2030] text-[#cad3f5]" ?>">Equipment w/o History</a>
      <a href="?report=combined" class="px-3 py-1.5 text-sm <?= $activeTab ===
      "combined"
        ? "bg-[#8aadf4] text-[#24273a] font-medium"
        : "bg-[#1e2030] text-[#cad3f5]" ?>">Combined Activity (UNION)</a>
      <a href="?report=monthly" class="px-3 py-1.5 text-sm <?= $activeTab ===
      "monthly"
        ? "bg-[#8aadf4] text-[#24273a] font-medium"
        : "bg-[#1e2030] text-[#cad3f5]" ?>">Monthly Report</a>
    </div>

    <!-- Results Table -->
    <div class="bg-[#1e2030] border border-[#363a4f] p-6 overflow-x-auto">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr class="text-left text-xs uppercase text-[#a5adcb] border-b border-[#363a4f]">
            <?php if ($activeTab === "complete"): ?>
              <th class="py-2 pr-3">Borrower No</th>
              <th class="py-2 pr-3">Borrower Name</th>
              <th class="py-2 pr-3">Equipment</th>
              <th class="py-2 pr-3">Qty</th>
              <th class="py-2 pr-3">Borrow Date</th>
              <th class="py-2 pr-3">Due Date</th>
              <th class="py-2 pr-3">Status</th>
            <?php elseif ($activeTab === "summary"): ?>
              <th class="py-2 pr-3">Equipment Name</th>
              <th class="py-2 pr-3">Property No</th>
              <th class="py-2 pr-3">Total Borrowed Qty</th>
              <th class="py-2 pr-3">Transaction Count</th>
              <th class="py-2 pr-3">Avg Qty / Tx</th>
            <?php elseif ($activeTab === "no_transactions"): ?>
              <th class="py-2 pr-3">Borrower No</th>
              <th class="py-2 pr-3">Full Name</th>
              <th class="py-2 pr-3">Borrower Type</th>
            <?php elseif ($activeTab === "no_equipment"): ?>
              <th class="py-2 pr-3">Property No</th>
              <th class="py-2 pr-3">Equipment Name</th>
              <th class="py-2 pr-3">Available Qty</th>
            <?php elseif ($activeTab === "combined"): ?>
              <th class="py-2 pr-3">Full Name</th>
              <th class="py-2 pr-3">Category</th>
              <th class="py-2 pr-3">Status</th>
            <?php elseif ($activeTab === "monthly"): ?>
              <th class="py-2 pr-3">Borrow Month (YYYY-MM)</th>
              <th class="py-2 pr-3">Total Transactions</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="text-center text-[#6e738d] py-6">No records found.</td></tr>
          <?php endif; ?>

          <?php foreach ($rows as $row): ?>
          <tr class="border-b border-[#363a4f] last:border-0">
            <?php if ($activeTab === "complete"): ?>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                $row["borrower_no"],
              ) ?></td>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                $row["full_name"],
              ) ?></td>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                $row["equipment_name"],
              ) ?></td>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= $row["quantity"] ?></td>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= $row[
                "borrow_date"
              ] ?></td>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= $row["due_date"] ?></td>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                $row["status"],
              ) ?></td>
            <?php elseif ($activeTab === "summary"): ?>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                $row["equipment_name"],
              ) ?></td>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                $row["property_no"],
              ) ?></td>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= $row[
                "total_qty_borrowed"
              ] ?></td>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= $row[
                "transaction_count"
              ] ?></td>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= $row[
                "avg_qty_per_transaction"
              ] ?></td>
            <?php elseif ($activeTab === "no_transactions"): ?>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                $row["borrower_no"],
              ) ?></td>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                $row["full_name"],
              ) ?></td>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                $row["borrower_type"],
              ) ?></td>
            <?php elseif ($activeTab === "no_equipment"): ?>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                $row["property_no"],
              ) ?></td>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                $row["equipment_name"],
              ) ?></td>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= $row[
                "quantity_available"
              ] ?></td>
            <?php elseif ($activeTab === "combined"): ?>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                $row["full_name"],
              ) ?></td>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                $row["category"],
              ) ?></td>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                $row["status"],
              ) ?></td>
            <?php elseif ($activeTab === "monthly"): ?>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                $row["borrow_month"],
              ) ?></td>
              <td class="py-2 pr-3 text-[#cad3f5]"><?= $row[
                "total_transactions"
              ] ?></td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
