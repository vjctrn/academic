<?php
require "db.php";
require "crud_manager.php";
$crud = new CrudManager($pdo);

// Handle optional filter inputs for the filtered transactions section
$status = $_GET["status"] ?? "";
$dateFrom = $_GET["date_from"] ?? "";
$dateTo = $_GET["date_to"] ?? "";
$sortBy = $_GET["sortBy"] ?? "";
$sortDirection = $_GET["sortDirection"] ?? "";

// Fetch all data for the dashboard blocks simultaneously
$inventoryRows = $crud->getEquipmentInventory();
$filteredRows = $crud->getFilteredTransactions(
  $status,
  $dateFrom,
  $dateTo,
  $sortBy,
  $sortDirection,
);
$borrowerRows = $crud->getBorrowerTypeSummary();
$statusRows = $crud->getTransactionStatusSummary();
$groupedRows = $crud->getGroupedBorrowingAnalysis();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Week 6 thingies</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-[#24273a] text-[#cad3f5] min-h-screen py-8 px-4">
  <div class="max-w-7xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <a href="index.php" class="text-sm text-[#8aadf4] hover:underline">&larr; Back to CRUD</a>
        <h1 class="text-2xl font-bold mt-1 text-[#cad3f5]">Week 6 thingies</h1>
      </div>
    </div>

    <div class="flex flex-col gap-4">

      <!-- Filtered thingie -->
      <div class="mt-6 bg-[#1e2030] border border-[#363a4f] p-5"> <h2 class="text-lg font-semibold mb-3 text-[#8aadf4]">Filtered Transactions</h2>

        <!-- Filter Form -->
        <form method="GET" class="mb-4 flex flex-wrap gap-4 items-end">
          <div>
            <label class="block text-xs text-[#a5adcb] mb-1">Status</label>
            <select name="status" class="bg-[#363a4f] border border-[#494d64] text-sm px-3 py-2 text-[#cad3f5]">
              <option value="">All Statuses</option>
              <option value="Borrowed" <?= $status === "Borrowed"
                ? "selected"
                : "" ?>>Borrowed</option>
              <option value="Returned" <?= $status === "Returned"
                ? "selected"
                : "" ?>>Returned</option>
              <option value="Overdue" <?= $status === "Overdue"
                ? "selected"
                : "" ?>>Overdue</option>
              <option value="Death Penalty" <?= $status === "Death Penalty"
                ? "selected"
                : "" ?>>Death Penalty</option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-[#a5adcb] mb-1">From Date</label>
            <input type="date" name="date_from" value="<?= htmlspecialchars(
              $dateFrom,
            ) ?>" class="bg-[#363a4f] border border-[#494d64] text-sm px-3 py-2 text-[#cad3f5]">
          </div>
          <div>
            <label class="block text-xs text-[#a5adcb] mb-1">To Date</label>
            <input type="date" name="date_to" value="<?= htmlspecialchars(
              $dateTo,
            ) ?>" class="bg-[#363a4f] border border-[#494d64] text-sm px-3 py-2 text-[#cad3f5]">
          </div>
          <div>
            <label class="block text-xs text-[#a5adcb] mb-1">Sort By</label>
            <select name="sortBy" class="bg-[#363a4f] border border-[#494d64] text-sm px-3 py-2 text-[#cad3f5]">
              <option value="">Columns</option>
              <option value="t.borrow_id" <?= $sortBy === "t.borrow_id"
                ? "selected"
                : "" ?>>Borrow ID</option>
              <option value="b.full_name" <?= $sortBy === "b.full_name"
                ? "selected"
                : "" ?>>Borrower Name</option>
              <option value="t.borrow_date" <?= $sortBy === "t.borrow_date"
                ? "selected"
                : "" ?>>Borrow Date</option>
              <option value="t.due_date" <?= $sortBy === "t.due_date"
                ? "selected"
                : "" ?>>Due Date</option>
              <option value="t.status" <?= $sortBy === "t.status"
                ? "selected"
                : "" ?>>Status</option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-[#a5adcb] mb-1">Sort Direction</label>
            <select name="sortDirection" class="bg-[#363a4f] border border-[#494d64] text-sm px-3 py-2 text-[#cad3f5]">
              <option value="ASC" <?= $sortDirection === "ASC"
                ? "selected"
                : "" ?>>Ascending</option>
              <option value="DESC" <?= $sortDirection === "DESC"
                ? "selected"
                : "" ?>>Descending</option>
            </select>
          </div>

          <button type="submit" class="bg-[#8aadf4] hover:bg-[#7dc4e4] text-[#24273a] text-sm font-medium px-4 py-2">Filter</button>
          <a href="reports.php" class="text-sm text-[#a5adcb] hover:underline pb-2">Reset</a>
        </form>

        <div class="overflow-x-auto">
          <table class="w-full text-sm border-collapse">
            <thead>
              <tr class="text-left text-xs uppercase text-[#a5adcb] border-b border-[#363a4f]">
                <th class="py-2 pr-3">Borrow ID</th>
                <th class="py-2 pr-3">Borrower Name</th>
                <th class="py-2 pr-3">Borrow Date</th>
                <th class="py-2 pr-3">Due Date</th>
                <th class="py-2 pr-3">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($filteredRows)): ?>
              <tr><td colspan="5" class="text-center text-[#6e738d] py-4">No records found.</td></tr>
              <?php endif; ?>
              <?php foreach ($filteredRows as $row): ?>
              <tr class="border-b border-[#363a4f] last:border-0">
                <td class="py-2 pr-3 text-[#cad3f5]"><?= $row[
                  "borrow_id"
                ] ?></td>
                <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                  $row["full_name"],
                ) ?></td>
                <td class="py-2 pr-3 text-[#cad3f5]"><?= $row[
                  "borrow_date"
                ] ?></td>
                <td class="py-2 pr-3 text-[#cad3f5]"><?= $row[
                  "due_date"
                ] ?></td>
                <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                  $row["status"],
                ) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">

        <!-- Card 1: Equipment Inventory -->
        <div class="bg-[#1e2030] border border-[#363a4f] p-5 flex flex-col">
          <h2 class="text-lg font-semibold mb-3 text-[#8aadf4]">Equipment Inventory</h2>
          <div class="overflow-x-auto flex-1">
            <table class="w-full text-sm border-collapse">
              <thead>
                <tr class="text-left text-xs uppercase text-[#a5adcb] border-b border-[#363a4f]">
                  <th class="py-2 pr-3">Equipment Name</th>
                  <th class="py-2 pr-3">Property No.</th>
                  <th class="py-2 pr-3">Available Qty</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($inventoryRows)): ?>
                <tr><td colspan="3" class="text-center text-[#6e738d] py-4">No records found.</td></tr>
                <?php endif; ?>
                <?php foreach ($inventoryRows as $row): ?>
                <tr class="border-b border-[#363a4f] last:border-0">
                  <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                    $row["equipment_name"],
                  ) ?></td>
                  <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                    $row["property_no"],
                  ) ?></td>
                  <td class="py-2 pr-3 text-[#cad3f5]"><?= $row[
                    "quantity_available"
                  ] ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Card 2: Borrower Summary -->
        <div class="bg-[#1e2030] border border-[#363a4f] p-5 flex flex-col">
          <h2 class="text-lg font-semibold mb-3 text-[#8aadf4]">Borrower Type Summary</h2>
          <div class="overflow-x-auto flex-1">
            <table class="w-full text-sm border-collapse">
              <thead>
                <tr class="text-left text-xs uppercase text-[#a5adcb] border-b border-[#363a4f]">
                  <th class="py-2 pr-3">Borrower Category</th>
                  <th class="py-2 pr-3">Number of Borrowers</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($borrowerRows)): ?>
                <tr><td colspan="2" class="text-center text-[#6e738d] py-4">No records found.</td></tr>
                <?php endif; ?>
                <?php foreach ($borrowerRows as $row): ?>
                <tr class="border-b border-[#363a4f] last:border-0">
                  <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                    $row["borrower_type"],
                  ) ?></td>
                  <td class="py-2 pr-3 text-[#cad3f5]"><?= $row[
                    "total_borrowers"
                  ] ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Card 3: Status Summary -->
        <div class="bg-[#1e2030] border border-[#363a4f] p-5 flex flex-col">
          <h2 class="text-lg font-semibold mb-3 text-[#8aadf4]">Transaction Status Summary</h2>
          <div class="overflow-x-auto flex-1">
            <table class="w-full text-sm border-collapse">
              <thead>
                <tr class="text-left text-xs uppercase text-[#a5adcb] border-b border-[#363a4f]">
                  <th class="py-2 pr-3">Borrowing Status</th>
                  <th class="py-2 pr-3">Total Transactions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($statusRows)): ?>
                <tr><td colspan="2" class="text-center text-[#6e738d] py-4">No records found.</td></tr>
                <?php endif; ?>
                <?php foreach ($statusRows as $row): ?>
                <tr class="border-b border-[#363a4f] last:border-0">
                  <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                    $row["status"],
                  ) ?></td>
                  <td class="py-2 pr-3 text-[#cad3f5]"><?= $row[
                    "total_transactions"
                  ] ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Card 4: Grouped Borrowing Analysis -->
        <div class="bg-[#1e2030] border border-[#363a4f] p-5 flex flex-col">
          <h2 class="text-lg font-semibold mb-3 text-[#8aadf4]">Grouped Analysis</h2>
          <div class="overflow-x-auto flex-1">
            <table class="w-full text-sm border-collapse">
              <thead>
                <tr class="text-left text-xs uppercase text-[#a5adcb] border-b border-[#363a4f]">
                  <th class="py-2 pr-3">Borrower Name</th>
                  <th class="py-2 pr-3">Total Transactions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($groupedRows)): ?>
                <tr><td colspan="2" class="text-center text-[#6e738d] py-4">No records found.</td></tr>
                <?php endif; ?>
                <?php foreach ($groupedRows as $row): ?>
                <tr class="border-b border-[#363a4f] last:border-0">
                  <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                    $row["full_name"],
                  ) ?></td>
                  <td class="py-2 pr-3 text-[#cad3f5]"><?= $row[
                    "transaction_count"
                  ] ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</body>
</html>
