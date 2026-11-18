<?php
require "db.php";
require "crud_manager.php";
$crud = new CrudManager($pdo);

// Fetch data specifically for views and audit logs
$borrowingDetailsRows = $crud->getBorrowingDetailsView();
$equipmentUsageRows = $crud->getEquipmentUsageView();
$auditRows = $crud->getAuditTableRows();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Database Views & Audit Logs</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-[#24273a] text-[#cad3f5] min-h-screen py-8 px-4">
  <div class="max-w-7xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <div class="flex gap-4 text-sm text-[#8aadf4]">
          <a href="index.php" class="hover:underline">&larr; Back to CRUD</a>
        </div>
        <h1 class="text-2xl font-bold mt-1 text-[#cad3f5]">Database Views & Audit Logs</h1>
      </div>
    </div>

    <div class="flex flex-col gap-6">

      <!-- Grid for the Two Database Views -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- View 1: vw_borrowing_details -->
        <div class="bg-[#1e2030] border border-[#363a4f] p-5 flex flex-col">
          <h2 class="text-lg font-semibold mb-1 text-[#8aadf4]">View: vw_borrowing_details</h2>
          <p class="text-xs text-[#a5adcb] mb-3">Comprehensive breakdown of active borrowing activities.</p>
          <div class="overflow-x-auto flex-1 max-h-96">
            <table class="w-full text-sm border-collapse">
              <thead>
                <tr class="text-left text-xs uppercase text-[#a5adcb] border-b border-[#363a4f] sticky top-0 bg-[#1e2030]">
                  <th class="py-2 pr-3">Borrow ID</th>
                  <th class="py-2 pr-3">Borrower</th>
                  <th class="py-2 pr-3">Equipment</th>
                  <th class="py-2 pr-3">Qty</th>
                  <th class="py-2 pr-3">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($borrowingDetailsRows)): ?>
                <tr><td colspan="5" class="text-center text-[#6e738d] py-4">No records found.</td></tr>
                <?php endif; ?>
                <?php foreach ($borrowingDetailsRows as $row): ?>
                <tr class="border-b border-[#363a4f] last:border-0">
                  <td class="py-2 pr-3 text-[#cad3f5]"><?= $row['borrow_id'] ?? '' ?></td>
                  <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars($row['full_name'] ?? '') ?></td>
                  <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars($row['equipment_name'] ?? '') ?></td>
                  <td class="py-2 pr-3 text-[#cad3f5]"><?= $row['quantity'] ?? '' ?></td>
                  <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars($row['status'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- View 2: vw_equipment_usage -->
        <div class="bg-[#1e2030] border border-[#363a4f] p-5 flex flex-col">
          <h2 class="text-lg font-semibold mb-1 text-[#8aadf4]">View: vw_equipment_usage</h2>
          <p class="text-xs text-[#a5adcb] mb-3">Aggregated utilization statistics per equipment item.</p>
          <div class="overflow-x-auto flex-1 max-h-96">
            <table class="w-full text-sm border-collapse">
              <thead>
                <tr class="text-left text-xs uppercase text-[#a5adcb] border-b border-[#363a4f] sticky top-0 bg-[#1e2030]">
                  <th class="py-2 pr-3">Equipment Name</th>
                  <th class="py-2 pr-3">Property No.</th>
                  <th class="py-2 pr-3">Total Borrowed Count</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($equipmentUsageRows)): ?>
                <tr><td colspan="3" class="text-center text-[#6e738d] py-4">No records found.</td></tr>
                <?php endif; ?>
                <?php foreach ($equipmentUsageRows as $row): ?>
                <tr class="border-b border-[#363a4f] last:border-0">
                  <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars($row['equipment_name'] ?? '') ?></td>
                  <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars($row['property_no'] ?? '') ?></td>
                  <td class="py-2 pr-3 text-[#cad3f5]"><?= $row['total_times_borrowed'] ?? 0 ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- Full Width Card: Audit Table Log -->
      <div class="bg-[#1e2030] border border-[#363a4f] p-5">
        <h2 class="text-lg font-semibold mb-1 text-[#8aadf4]">Audit Table Log (borrow_items_audit)</h2>
        <p class="text-xs text-[#a5adcb] mb-4">Database-side trigger event logs tracking item mutations.</p>
        <div class="overflow-x-auto max-h-96">
          <table class="w-full text-sm border-collapse">
            <thead>
              <tr class="text-left text-xs uppercase text-[#a5adcb] border-b border-[#363a4f] sticky top-0 bg-[#1e2030]">
                <th class="py-2 pr-3">Audit ID</th>
                <th class="py-2 pr-3">Borrow ID</th>
                <th class="py-2 pr-3">Equipment ID</th>
                <th class="py-2 pr-3">Action Type</th>
                <th class="py-2 pr-3">Changed At</th>
                <th class="py-2 pr-3">Details</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($auditRows)): ?>
              <tr><td colspan="6" class="text-center text-[#6e738d] py-4">No audit logs found.</td></tr>
              <?php endif; ?>
              <?php foreach ($auditRows as $row): ?>
              <tr class="border-b border-[#363a4f] last:border-0">
                <td class="py-2 pr-3 text-[#cad3f5]"><?= $row['audit_id'] ?? '' ?></td>
                <td class="py-2 pr-3 text-[#cad3f5]"><?= $row['borrow_id'] ?? '' ?></td>
                <td class="py-2 pr-3 text-[#cad3f5]"><?= $row['equipment_id'] ?? '' ?></td>
                <td class="py-2 pr-3 text-[#cad3f5]">
                  <span class="px-2 py-0.5 rounded text-xs bg-[#363a4f] text-[#8aadf4]"><?= htmlspecialchars($row['action_type'] ?? 'LOG') ?></span>
                </td>
                <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars($row['changed_at'] ?? '') ?></td>
                <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars($row['details'] ?? '') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</body>
</html>
