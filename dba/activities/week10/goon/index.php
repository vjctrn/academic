<?php
require "db.php";
require "crud_manager.php";

$crud = new CrudManager($pdo);
$message = "";
$error = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $table = $_POST["table"] ?? "";
  $action = $_POST["action"] ?? "";

  try {
    if ($action === "create") {
      $newId = $crud->create($table, $_POST);
      $message = "Added new row (#$newId).";
    } elseif ($action === "update") {
      $id = (int) ($_POST["id"] ?? 0);
      $crud->update($table, $id, $_POST);
      $message = "Updated row #$id.";
    } elseif ($action === "delete") {
      $id = (int) ($_POST["id"] ?? 0);
      $crud->delete($table, $id);
      $message = "Deleted row #$id.";
    }
  } catch (Exception $e) {
    $message = "Error: " . $e->getMessage();
    $error = true;
  }
}

$currentTable = $_GET["table"] ?? "borrowers";
if (!in_array($currentTable, $crud->getTables())) {
  $currentTable = "borrowers";
}
$meta = $crud->getMeta($currentTable);
$rows = $crud->readAll($currentTable);

$editRow = null;
if (isset($_GET["edit"])) {
  $editRow = $crud->readOne($currentTable, (int) $_GET["edit"]);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Basic CRUD - <?= htmlspecialchars($currentTable) ?></title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="bg-[#24273a] text-[#cad3f5] min-h-screen py-8 px-4">
  <div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-bold mb-4 text-[#cad3f5]">Basic CRUD Thingie</h1>

    <!--table selector & reports link-->
    <div class="flex flex-wrap gap-2 mb-6">
      <?php foreach ($crud->getTables() as $t): ?>
        <a href="?table=<?= urlencode(
                          $t,
                        ) ?>" class="px-4 py-2 border text-sm <?= $currentTable === $t
                                                ? "bg-[#8aadf4] border-[#8aadf4] text-[#24273a] font-medium"
                                                : "bg-[#1e2030] border-[#494d64] text-[#cad3f5] hover:bg-[#363a4f]" ?>">
          <?= htmlspecialchars($t) ?>
        </a>
      <?php endforeach; ?>
      <a href="reports.php" class="px-4 py-2 border text-sm bg-[#1e2030] border-[#494d64] text-[#cad3f5] hover:bg-[#363a4f]">
        Week 6 Reports
      </a>
      <a href="week7_reports.php" class="px-4 py-2 border text-sm bg-[#1e2030] border-[#494d64] text-[#cad3f5] hover:bg-[#363a4f]">
        Week 7 Reports
      </a>
      <a href="week10.php" class="px-4 py-2 border text-sm bg-[#1e2030] border-[#494d64] text-[#cad3f5] hover:bg-[#363a4f]">
        Week 10 thingies
      </a>
      <a href="checkout.php" class="px-4 py-2 border text-sm bg-[#1e2030] border-[#494d64] text-[#cad3f5] hover:bg-[#363a4f]">
        New Checkout (Transaction)
      </a>
    </div>

    <?php if ($message): ?>
      <div class="bg-[#1e2030] border
        <?= !$error
          ? "border-[#a6da95] text-[#a6da95]"
          : "border-[#ed8796] text-[#ed8796]" ?>
        px-4 py-3 mb-6">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <!--add/edit-->
    <div class="bg-[#1e2030] border border-[#363a4f] p-6 mb-6">
      <h2 class="text-lg font-semibold mb-4 text-[#cad3f5]">
        <?= $editRow
          ? "Edit row #" . $editRow[$meta["pk"]]
          : "Add new " . htmlspecialchars($currentTable) ?>
      </h2>
      <form method="POST">
        <input type="hidden" name="table" value="<?= htmlspecialchars(
                                                    $currentTable,
                                                  ) ?>">
        <input type="hidden" name="action" value="<?= $editRow
                                                    ? "update"
                                                    : "create" ?>">
        <?php if ($editRow): ?>
          <input type="hidden" name="id" value="<?= $editRow[$meta["pk"]] ?>">
        <?php endif; ?>

        <?php foreach ($meta["columns"] as $i => $col): ?>
          <label class="block mb-4 text-sm text-[#a5adcb]">
            <?= htmlspecialchars($meta["labels"][$i]) ?>
            <input type="text" name="<?= htmlspecialchars($col) ?>"
              value="<?= htmlspecialchars($editRow[$col] ?? "") ?>"
              required class="block w-full mt-1 px-3 py-2 bg-[#363a4f] border border-[#494d64] text-sm text-[#cad3f5] placeholder-[#6e738d] focus:outline-none focus:ring-2 focus:ring-[#8aadf4] focus:border-[#8aadf4]">
          </label>
        <?php endforeach; ?>

        <div class="flex items-center gap-4 mt-2">
          <button type="submit" class="bg-[#8aadf4] hover:bg-[#7dc4e4] text-[#24273a] text-sm font-medium px-5 py-2">
            <?= $editRow ? "Save Changes" : "Add" ?>
          </button>
          <?php if ($editRow): ?>
            <a href="?table=<?= urlencode(
                              $currentTable,
                            ) ?>" class="text-sm text-[#a5adcb] hover:text-[#cad3f5]">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!--current table-->
    <div class="bg-[#1e2030] border border-[#363a4f] p-6 overflow-x-auto">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr class="text-left text-xs uppercase text-[#a5adcb] border-b border-[#363a4f]">
            <th class="py-2 pr-3">#</th>
            <?php foreach ($meta["labels"] as $label): ?>
              <th class="py-2 pr-3"><?= htmlspecialchars($label) ?></th>
            <?php endforeach; ?>
            <th class="py-2 pr-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="<?= count($meta["columns"]) +
                              2 ?>" class="text-center text-[#6e738d] py-6">
                No rows yet, baka. Add one above.
              </td>
            </tr>
          <?php endif; ?>

          <?php foreach ($rows as $row): ?>
            <tr class="border-b border-[#363a4f] last:border-0">
              <td class="py-2 pr-3 text-[#cad3f5]"><?= $row[$meta["pk"]] ?></td>
              <?php foreach ($meta["columns"] as $col): ?>
                <td class="py-2 pr-3 text-[#cad3f5]"><?= htmlspecialchars(
                                                        $row[$col] ?? "",
                                                      ) ?></td>
              <?php endforeach; ?>
              <td class="py-2 pr-3">
                <div class="flex items-center gap-3 whitespace-nowrap">
                  <a href="?table=<?= urlencode(
                                    $currentTable,
                                  ) ?>&edit=<?= $row[$meta["pk"]] ?>"
                    class="text-[#8aadf4] hover:text-[#b7bdf8]">Edit</a>
                  <form method="POST" onsubmit="return confirm('Delete row #<?= $row[$meta["pk"]] ?>?');">
                    <input type="hidden" name="table" value="<?= htmlspecialchars(
                                                                $currentTable,
                                                              ) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $row[$meta["pk"]] ?>">
                    <button type="submit" class="bg-[#ed8796] hover:bg-[#ee99a0] text-[#24273a] text-xs font-medium px-3 py-1.5">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>

</html>