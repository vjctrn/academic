<?php
class CrudManager {
  private PDO $pdo;

  // don't you love hardcoding the tables
  private array $tables = [
    "borrowers" => [
      "pk" => "borrower_id",
      "columns" => ["borrower_no", "full_name", "borrower_type"],
      "labels" => ["Borrower No.", "Full Name", "Type"],
    ],
    "equipment" => [
      "pk" => "equipment_id",
      "columns" => ["property_no", "equipment_name", "quantity_available"],
      "labels" => ["Property No.", "Equipment Name", "Qty Available"],
    ],
    "borrow_transactions" => [
      "pk" => "borrow_id",
      "columns" => ["borrower_id", "borrow_date", "due_date", "status"],
      "labels" => ["Borrower ID", "Borrow Date", "Due Date", "Status"],
    ],
    "borrow_items" => [
      "pk" => "borrow_item_id",
      "columns" => ["borrow_id", "equipment_id", "quantity"],
      "labels" => ["Borrow ID", "Equipment ID", "Quantity"],
    ],
  ];

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function getTables(): array {
    return array_keys($this->tables);
  }

  public function getMeta(string $table): array {
    if (!array_key_exists($table, $this->tables)) {
      throw new InvalidArgumentException("unknown table: $table bru.");
    }
    return $this->tables[$table];
  }

  // ---------- CREATE ----------
  public function create(string $table, array $data): int {
    $meta = $this->getMeta($table);
    $data = array_intersect_key($data, array_flip($meta["columns"]));

    $columns = array_keys($data);
    $placeholders = array_map(fn($c) => ":$c", $columns);

    $sql = sprintf(
      "INSERT INTO %s (%s) VALUES (%s) RETURNING %s",
      $table,
      implode(", ", $columns),
      implode(", ", $placeholders),
      $meta["pk"],
    );

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($data);
    return (int) $stmt->fetchColumn();
  }

  // ---------- READ ----------
  public function readAll(string $table): array {
    $this->getMeta($table); // just validates it exists
    return $this->pdo->query("SELECT * FROM $table ORDER BY 1")->fetchAll();
  }

  public function readOne(string $table, int $id): array|false {
    $meta = $this->getMeta($table);
    $stmt = $this->pdo->prepare(
      "SELECT * FROM $table WHERE {$meta["pk"]} = :id",
    );
    $stmt->execute([":id" => $id]);
    return $stmt->fetch();
  }

  // ---------- UPDATE ----------
  public function update(string $table, int $id, array $data): bool {
    $meta = $this->getMeta($table);
    $data = array_intersect_key($data, array_flip($meta["columns"]));

    $setParts = array_map(fn($c) => "$c = :$c", array_keys($data));
    $sql = sprintf(
      "UPDATE %s SET %s WHERE %s = :pk_id",
      $table,
      implode(", ", $setParts),
      $meta["pk"],
    );

    $data[":pk_id"] = $id;
    $params = [];
    foreach ($data as $k => $v) {
      $params[$k === ":pk_id" ? ":pk_id" : ":$k"] = $v;
    }

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount() > 0;
  }

  // ---------- DELETE ----------
  public function delete(string $table, int $id): bool {
    $meta = $this->getMeta($table);
    $stmt = $this->pdo->prepare("DELETE FROM $table WHERE {$meta["pk"]} = :id");
    $stmt->execute([":id" => $id]);
    return $stmt->rowCount() > 0;
  }

  // ---------- Week 6 stuff ----------
  public function getEquipmentInventory(): array {
    $sql = "SELECT DISTINCT
                INITCAP(equipment_name) AS equipment_name,
                property_no,
                quantity_available
            FROM equipment
            ORDER BY quantity_available DESC, equipment_name ASC";
    return $this->pdo->query($sql)->fetchAll();
  }

  public function getFilteredTransactions(
    string $status = "",
    string $dateFrom = "",
    string $dateTo = "",
    string $sortBy = "",
    string $sortDirection = "",
  ): array {
    $sql = "SELECT t.borrow_id, b.full_name, t.borrow_date, t.due_date, t.status
              FROM borrow_transactions t
              JOIN borrowers b ON t.borrower_id = b.borrower_id
              WHERE 1=1";
    $params = [];

    if (!empty($status)) {
      $sql .= " AND t.status = :status";
      $params[":status"] = $status;
    }

    if (!empty($dateFrom) && !empty($dateTo)) {
      $sql .= " AND t.borrow_date BETWEEN :date_from AND :date_to";
      $params[":date_from"] = $dateFrom;
      $params[":date_to"] = $dateTo;
    }

    if (!empty($sortBy) && !empty($sortDirection)) {
      $sql .= " ORDER BY {$sortBy} {$sortDirection}";
    } else {
      $sql .= " ORDER BY t.borrow_date DESC";
    }

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
  }

  public function getBorrowerTypeSummary(): array {
    $sql = "SELECT borrower_type, COUNT(*) AS total_borrowers
              FROM borrowers
              GROUP BY borrower_type
              ORDER BY total_borrowers DESC";
    return $this->pdo->query($sql)->fetchAll();
  }

  public function getTransactionStatusSummary(): array {
    $sql = "SELECT status, COUNT(*) AS total_transactions
              FROM borrow_transactions
              GROUP BY status
              ORDER BY total_transactions DESC";
    return $this->pdo->query($sql)->fetchAll();
  }

  public function getGroupedBorrowingAnalysis(): array {
    $sql = "SELECT b.full_name, COUNT(t.borrow_id) AS transaction_count
              FROM borrow_transactions t
              JOIN borrowers b ON t.borrower_id = b.borrower_id
              GROUP BY b.borrower_id, b.full_name
              HAVING COUNT(t.borrow_id) >= 2
              ORDER BY transaction_count DESC";
    return $this->pdo->query($sql)->fetchAll();
  }

  // ---------- Week 7 stuff ----------
  public function getCompleteBorrowingReport(): array {
    $sql = "SELECT b.borrower_no, b.full_name, e.equipment_name, bi.quantity,
                     TO_CHAR(t.borrow_date, 'YYYY-MM-DD') AS borrow_date,
                     TO_CHAR(t.due_date, 'YYYY-MM-DD') AS due_date,
                     INITCAP(t.status) AS status
              FROM borrow_transactions t
              JOIN borrowers b ON t.borrower_id = b.borrower_id
              JOIN borrow_items bi ON t.borrow_id = bi.borrow_id
              JOIN equipment e ON bi.equipment_id = e.equipment_id
              ORDER BY t.borrow_date DESC";
    return $this->pdo->query($sql)->fetchAll();
  }

  public function getEquipmentBorrowingSummary(): array {
    $sql = "SELECT e.equipment_name, e.property_no,
                     COALESCE(SUM(bi.quantity), 0) AS total_qty_borrowed,
                     COUNT(DISTINCT t.borrow_id) AS transaction_count,
                     ROUND(COALESCE(AVG(bi.quantity), 0), 2) AS avg_qty_per_transaction
              FROM equipment e
              LEFT JOIN borrow_items bi ON e.equipment_id = bi.equipment_id
              LEFT JOIN borrow_transactions t ON bi.borrow_id = t.borrow_id
              GROUP BY e.equipment_id, e.equipment_name, e.property_no
              ORDER BY total_qty_borrowed DESC";
    return $this->pdo->query($sql)->fetchAll();
  }

  public function getBorrowersWithoutTransactions(): array {
    $sql = "SELECT b.borrower_no, b.full_name, b.borrower_type
              FROM borrowers b
              LEFT JOIN borrow_transactions t ON b.borrower_id = t.borrower_id
              WHERE t.borrow_id IS NULL
              ORDER BY b.full_name ASC";
    return $this->pdo->query($sql)->fetchAll();
  }

  public function getEquipmentWithoutHistory(): array {
    $sql = "SELECT e.property_no, e.equipment_name, e.quantity_available
              FROM equipment e
              LEFT JOIN borrow_items bi ON e.equipment_id = bi.equipment_id
              WHERE bi.borrow_item_id IS NULL
              ORDER BY e.equipment_name ASC";
    return $this->pdo->query($sql)->fetchAll();
  }

  public function getCombinedBorrowerActivity(): array {
    $sql = "(SELECT b.full_name, 'Staff/Faculty' AS category, t.status
               FROM borrowers b
               JOIN borrow_transactions t ON b.borrower_id = t.borrower_id
               WHERE b.borrower_type IN ('Staff', 'Faculty'))
              UNION
              (SELECT b.full_name, 'Student' AS category, t.status
               FROM borrowers b
               JOIN borrow_transactions t ON b.borrower_id = t.borrower_id
               WHERE b.borrower_type = 'Student')
              ORDER BY full_name ASC";
    return $this->pdo->query($sql)->fetchAll();
  }

  public function getMonthlyBorrowingReport(): array {
    $sql = "SELECT TO_CHAR(borrow_date, 'YYYY-MM') AS borrow_month,
                     COUNT(*) AS total_transactions
              FROM borrow_transactions
              GROUP BY TO_CHAR(borrow_date, 'YYYY-MM')
              ORDER BY borrow_month DESC";
    return $this->pdo->query($sql)->fetchAll();
  }

  // ---------- scuff transaction ----------
  public function checkoutTransaction(
    int $borrowerId,
    string $dueDate,
    array $items,
  ): int {
    $this->pdo->beginTransaction();
    try {
      // 1. Insert transaction header
      $stmt = $this->pdo->prepare(
        "INSERT INTO borrow_transactions (borrower_id, due_date, status) VALUES (?, ?, 'Borrowed') RETURNING borrow_id",
      );
      $stmt->execute([$borrowerId, $dueDate]);
      $borrowId = (int) $stmt->fetchColumn();

      // 2. Insert items and decrement available equipment stock
      $itemStmt = $this->pdo->prepare(
        "INSERT INTO borrow_items (borrow_id, equipment_id, quantity) VALUES (?, ?, ?)",
      );
      $stockStmt = $this->pdo->prepare(
        "UPDATE equipment SET quantity_available = quantity_available - ? WHERE equipment_id = ? AND quantity_available >= ?",
      );

      foreach ($items as $item) {
        $eqId = (int) $item["equipment_id"];
        $qty = (int) $item["quantity"];

        if ($qty <= 0) {
          continue;
        }

        $itemStmt->execute([$borrowId, $eqId, $qty]);

        $stockStmt->execute([$qty, $eqId, $qty]);
        if ($stockStmt->rowCount() === 0) {
          throw new Exception(
            "Stock insufficient or invalid equipment ID $eqId.",
          );
        }
      }

      $this->pdo->commit();
      return $borrowId;
    } catch (Exception $e) {
      $this->pdo->rollBack();
      throw $e;
    }
  }

  public function getBorrowingDetailsView() {
      $stmt = $this->pdo->query("SELECT * FROM vw_borrowing_details");
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getEquipmentUsageView() {
      $stmt = $this->pdo->query("SELECT * FROM vw_equipment_usage");
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getAuditTableRows() {
        $stmt = $this->pdo->query("SELECT * FROM borrow_items_audit ORDER BY changed_at DESC LIMIT 50");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
