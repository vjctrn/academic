<?php
require "db.php";
require "crud_manager.php";

$crud = new CrudManager($pdo);
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $borrowerId = (int) ($_POST["borrower_id"] ?? 0);
  $dueDate = $_POST["due_date"] ?? "";
  $equipmentId = (int) ($_POST["equipment_id"] ?? 0);
  $quantity = (int) ($_POST["quantity"] ?? 0);

  try {
    if (
      $borrowerId <= 0 ||
      empty($dueDate) ||
      $equipmentId <= 0 ||
      $quantity <= 0
    ) {
      throw new Exception(
        "All fields are required and quantity must be greater than zero.",
      );
    }

    // Pass as an array of items to support multi-item expansion later if needed
    $items = [["equipment_id" => $equipmentId, "quantity" => $quantity]];

    $borrowId = $crud->checkoutTransaction($borrowerId, $dueDate, $items);
    $message = "Successfully processed checkout transaction #$borrowId!";
  } catch (Exception $e) {
    $error = "Checkout Failed: " . $e->getMessage();
  }
}

// Fetch lists for dropdowns
$borrowers = $pdo
  ->query(
    "SELECT borrower_id, full_name, borrower_no FROM borrowers ORDER BY full_name",
  )
  ->fetchAll();
$equipment = $pdo
  ->query(
    "SELECT equipment_id, equipment_name, property_no, quantity_available FROM equipment WHERE quantity_available > 0 ORDER BY equipment_name",
  )
  ->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>New Checkout Transaction</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-[#24273a] text-[#cad3f5] min-h-screen py-8 px-4">
  <div class="max-w-xl mx-auto">
    <div class="mb-4">
      <a href="index.php" class="text-sm text-[#8aadf4] hover:underline">&larr; Back to CRUD</a>
    </div>

    <h1 class="text-2xl font-bold mb-4 text-[#cad3f5]">Process Equipment Checkout</h1>
    <p class="text-xs text-[#a5adcb] mb-6">Executes a secure database transaction (`BEGIN` / `COMMIT` / `ROLLBACK`).</p>

    <?php if ($message): ?>
    <div class="bg-[#1e2030] border border-[#a6da95] text-[#a6da95] px-4 py-3 mb-6 text-sm">
      <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="bg-[#1e2030] border border-[#ed8796] text-[#ed8796] px-4 py-3 mb-6 text-sm">
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="bg-[#1e2030] border border-[#363a4f] p-6">
      <form method="POST">
        <label class="block mb-4 text-sm text-[#a5adcb]">
          Select Borrower
          <select name="borrower_id" required class="block w-full mt-1 px-3 py-2 bg-[#363a4f] border border-[#494d64] text-sm text-[#cad3f5] focus:outline-none focus:ring-2 focus:ring-[#8aadf4]">
            <option value="">-- Choose Borrower --</option>
            <?php foreach ($borrowers as $b): ?>
              <option value="<?= $b["borrower_id"] ?>"><?= htmlspecialchars(
  $b["full_name"] . " (" . $b["borrower_no"] . ")",
) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="block mb-4 text-sm text-[#a5adcb]">
          Due Date
          <input type="date" name="due_date" required class="block w-full mt-1 px-3 py-2 bg-[#363a4f] border border-[#494d64] text-sm text-[#cad3f5] focus:outline-none focus:ring-2 focus:ring-[#8aadf4]">
        </label>

        <label class="block mb-4 text-sm text-[#a5adcb]">
          Equipment Item
          <select name="equipment_id" required class="block w-full mt-1 px-3 py-2 bg-[#363a4f] border border-[#494d64] text-sm text-[#cad3f5] focus:outline-none focus:ring-2 focus:ring-[#8aadf4]">
            <option value="">-- Choose Equipment --</option>
            <?php foreach ($equipment as $eq): ?>
              <option value="<?= $eq["equipment_id"] ?>"><?= htmlspecialchars(
  $eq["equipment_name"] . " [Available: " . $eq["quantity_available"] . "]",
) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="block mb-6 text-sm text-[#a5adcb]">
          Quantity to Borrow
          <input type="number" name="quantity" value="1" min="1" required class="block w-full mt-1 px-3 py-2 bg-[#363a4f] border border-[#494d64] text-sm text-[#cad3f5] focus:outline-none focus:ring-2 focus:ring-[#8aadf4]">
        </label>

        <button type="submit" class="bg-[#8aadf4] hover:bg-[#7dc4e4] text-[#24273a] text-sm font-medium px-5 py-2">
          Complete Secure Checkout
        </button>
      </form>
    </div>
  </div>
</body>
</html>
