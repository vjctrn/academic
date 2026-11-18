<?php
declare(strict_types=1);

session_start();

$config = [
	'host' => getenv('DB_HOST') ?: '127.0.0.1',
	'port' => getenv('DB_PORT') ?: '5432',
	'name' => getenv('DB_NAME') ?: 'borrow_db',
	'user' => getenv('DB_USER') ?: 'postgres',
	'pass' => getenv('DB_PASS') ?: '',
];

$tables = [
	'borrowers' => [
		'label' => 'Borrowers',
		'singular' => 'Borrower',
		'pk' => 'borrower_id',
		'columns' => [
			'borrower_no' => ['label' => 'Borrower No.', 'type' => 'text', 'required' => true],
			'full_name' => ['label' => 'Full name', 'type' => 'text', 'required' => true],
			'borrower_type' => ['label' => 'Type', 'type' => 'select', 'options' => ['Student', 'Staff', 'Faculty'], 'required' => true],
		],
	],
	'equipment' => [
		'label' => 'Equipment',
		'singular' => 'Equipment item',
		'pk' => 'equipment_id',
		'columns' => [
			'property_no' => ['label' => 'Property No.', 'type' => 'text', 'required' => true],
			'equipment_name' => ['label' => 'Equipment name', 'type' => 'text', 'required' => true],
			'quantity_available' => ['label' => 'Quantity available', 'type' => 'number', 'required' => true],
		],
	],
	'borrow_transactions' => [
		'label' => 'Transactions',
		'singular' => 'Transaction',
		'pk' => 'borrow_id',
		'columns' => [
			'borrower_id' => ['label' => 'Borrower', 'type' => 'borrower', 'required' => true],
			'borrow_date' => ['label' => 'Borrow date', 'type' => 'date', 'required' => true],
			'due_date' => ['label' => 'Due date', 'type' => 'date', 'required' => true],
			'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['Borrowed', 'Returned', 'Overdue', 'Cancelled'], 'required' => true],
		],
	],
	'borrow_items' => [
		'label' => 'Borrowed items',
		'singular' => 'Borrowed item',
		'pk' => 'borrow_item_id',
		'columns' => [
			'borrow_id' => ['label' => 'Transaction', 'type' => 'transaction', 'required' => true],
			'equipment_id' => ['label' => 'Equipment', 'type' => 'equipment', 'required' => true],
			'quantity' => ['label' => 'Quantity', 'type' => 'number', 'required' => true],
		],
	],
];

function h(mixed $value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirectTo(string $table): never
{
	header('Location: ?table=' . rawurlencode($table));
	exit;
}

if (!isset($_SESSION['csrf'])) {
	$_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$table = $_GET['table'] ?? 'borrowers';
if (!isset($tables[$table])) {
	$table = 'borrowers';
}
$definition = $tables[$table];
$message = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$error = null;
$editRow = null;

try {
	$pdo = new PDO(
		"pgsql:host={$config['host']};port={$config['port']};dbname={$config['name']}",
		$config['user'],
		$config['pass'],
		[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
	);

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
			throw new RuntimeException('Invalid form token. Please try again.');
		}

		$action = $_POST['action'] ?? '';
		$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;

		if ($action === 'delete') {
			$statement = $pdo->prepare("DELETE FROM public.{$table} WHERE {$definition['pk']} = :id");
			$statement->execute(['id' => $id]);
			$_SESSION['flash'] = $definition['singular'] . ' deleted.';
		} elseif ($action === 'save') {
			$values = [];
			foreach ($definition['columns'] as $column => $field) {
				$value = trim((string) ($_POST[$column] ?? ''));
				if ($field['required'] && $value === '') {
					throw new RuntimeException($field['label'] . ' is required.');
				}
				if ($field['type'] === 'number' && (!ctype_digit($value) || (int) $value < 0)) {
					throw new RuntimeException($field['label'] . ' must be a non-negative whole number.');
				}
				if ($field['type'] === 'select' && !in_array($value, $field['options'], true)) {
					throw new RuntimeException('Invalid ' . strtolower($field['label']) . '.');
				}
				$values[$column] = $value;
			}

			if ($id) {
				$assignments = implode(', ', array_map(fn($column) => "$column = :$column", array_keys($values)));
				$values['id'] = $id;
				$statement = $pdo->prepare("UPDATE public.{$table} SET {$assignments} WHERE {$definition['pk']} = :id");
				$statement->execute($values);
				$_SESSION['flash'] = $definition['singular'] . ' updated.';
			} else {
				$columns = implode(', ', array_keys($values));
				$parameters = ':' . implode(', :', array_keys($values));
				$statement = $pdo->prepare("INSERT INTO public.{$table} ({$columns}) VALUES ({$parameters})");
				$statement->execute($values);
				$_SESSION['flash'] = $definition['singular'] . ' created.';
			}
		}
		redirectTo($table);
	}

	if (isset($_GET['edit']) && ctype_digit((string) $_GET['edit'])) {
		$statement = $pdo->prepare("SELECT * FROM public.{$table} WHERE {$definition['pk']} = :id");
		$statement->execute(['id' => (int) $_GET['edit']]);
		$editRow = $statement->fetch() ?: null;
	}

	$rows = $pdo->query("SELECT * FROM public.{$table} ORDER BY {$definition['pk']} DESC")->fetchAll();
	$lookups = [
		'borrower' => $pdo->query('SELECT borrower_id AS id, borrower_no || \' - \' || full_name AS label FROM public.borrowers ORDER BY full_name')->fetchAll(),
		'equipment' => $pdo->query('SELECT equipment_id AS id, property_no || \' - \' || equipment_name AS label FROM public.equipment ORDER BY equipment_name')->fetchAll(),
		'transaction' => $pdo->query('SELECT t.borrow_id AS id, \'#\' || t.borrow_id || \' - \' || b.full_name AS label FROM public.borrow_transactions t JOIN public.borrowers b ON b.borrower_id = t.borrower_id ORDER BY t.borrow_id DESC')->fetchAll(),
	];
} catch (Throwable $exception) {
	$error = $exception->getMessage();
	$rows = [];
	$lookups = ['borrower' => [], 'equipment' => [], 'transaction' => []];
}

function displayValue(mixed $value, string $column, array $lookups): string
{
	foreach (['borrower', 'equipment', 'transaction'] as $lookupType) {
		if ($column === $lookupType . '_id' || ($column === 'borrow_id' && $lookupType === 'transaction')) {
			foreach ($lookups[$lookupType] as $option) {
				if ((string) $option['id'] === (string) $value) {
					return (string) $option['label'];
				}
			}
		}
	}
	return (string) $value;
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Equipment Borrowing Admin</title>
	<style>
		:root { --ink:#18232b; --muted:#68757d; --line:#dce4e6; --paper:#f7faf8; --teal:#087f80; --teal-dark:#075c61; --coral:#d95d4c; }
		* { box-sizing:border-box; }
		body { margin:0; color:var(--ink); background:var(--paper); font:15px/1.5 Georgia, serif; }
		.shell { max-width:1180px; margin:auto; padding:34px 22px 60px; }
		header { display:flex; justify-content:space-between; align-items:end; gap:20px; margin-bottom:28px; }
		h1,h2,p { margin-top:0; } h1 { margin-bottom:5px; font-size:clamp(2rem,5vw,3.5rem); line-height:1; letter-spacing:-.04em; }
		h2 { font-size:1.45rem; margin-bottom:4px; } .eyebrow { color:var(--teal); font:700 12px/1.2 Arial,sans-serif; letter-spacing:.12em; text-transform:uppercase; }
		.subtitle { color:var(--muted); margin-bottom:0; } .layout { display:grid; grid-template-columns:210px 1fr; gap:28px; align-items:start; }
		nav { display:grid; gap:7px; } nav a { color:var(--ink); text-decoration:none; padding:11px 14px; border-left:3px solid transparent; font-weight:bold; } nav a:hover, nav a.active { color:var(--teal-dark); background:#e8f1ee; border-left-color:var(--teal); }
		.panel { background:white; border:1px solid var(--line); border-radius:7px; padding:22px; box-shadow:0 8px 26px #183f4510; }
		.panel-head { display:flex; justify-content:space-between; gap:18px; align-items:start; margin-bottom:18px; }
		form.editor { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; padding:17px; margin-bottom:22px; background:#f3f8f5; border:1px solid #d8e8e0; border-radius:5px; }
		label { display:grid; gap:5px; color:var(--muted); font:700 12px Arial,sans-serif; } label.full { grid-column:1/-1; }
		input,select { width:100%; border:1px solid #cbd7d9; border-radius:4px; background:white; color:var(--ink); padding:10px; font:15px Georgia,serif; } input:focus,select:focus { outline:2px solid #8dd0ca; border-color:var(--teal); }
		.actions { grid-column:1/-1; display:flex; gap:9px; align-items:center; } button,.button { border:0; border-radius:4px; padding:10px 15px; background:var(--teal); color:#fff; cursor:pointer; font:bold 13px Arial,sans-serif; text-decoration:none; } button:hover,.button:hover { background:var(--teal-dark); } .button.secondary { background:#e5eeee; color:var(--teal-dark); }
		table { width:100%; border-collapse:collapse; } th,td { border-bottom:1px solid var(--line); padding:12px 9px; text-align:left; vertical-align:top; } th { color:var(--muted); font:700 11px Arial,sans-serif; letter-spacing:.06em; text-transform:uppercase; } td { max-width:280px; overflow-wrap:anywhere; } .row-actions { white-space:nowrap; } .row-actions a { color:var(--teal-dark); font:700 12px Arial,sans-serif; text-decoration:none; margin-right:10px; } .row-actions button { background:none; color:var(--coral); padding:0; font:700 12px Arial,sans-serif; }
		.notice { border-radius:4px; padding:12px 14px; margin-bottom:18px; font-family:Arial,sans-serif; } .notice.error { background:#fff0ed; color:#963b30; } .notice.success { background:#e7f6ed; color:#17633b; }
		.empty { color:var(--muted); padding:26px 0; text-align:center; } .db-note { margin-top:25px; color:var(--muted); font:12px Arial,sans-serif; } @media (max-width:760px) { .layout { grid-template-columns:1fr; } nav { display:flex; overflow:auto; } nav a { white-space:nowrap; } header { display:block; } header .eyebrow { margin-top:18px; } form.editor { grid-template-columns:1fr; } .panel { padding:16px; overflow:auto; } table { min-width:650px; } }
	</style>
</head>
<body>
<main class="shell">
	<header>
		<div><div class="eyebrow">PostgreSQL / PDO</div><h1>Borrowing desk</h1><p class="subtitle">Manage people, equipment, and lending records.</p></div>
		<div class="eyebrow">Admin workspace</div>
	</header>
	<div class="layout">
		<nav aria-label="Database tables">
			<?php foreach ($tables as $name => $item): ?>
				<a class="<?= $name === $table ? 'active' : '' ?>" href="?table=<?= h($name) ?>"><?= h($item['label']) ?></a>
			<?php endforeach; ?>
		</nav>
		<section class="panel">
			<?php if ($error): ?><div class="notice error"><strong>Database error:</strong> <?= h($error) ?></div><?php endif; ?>
			<?php if ($message): ?><div class="notice success"><?= h($message) ?></div><?php endif; ?>
			<div class="panel-head"><div><div class="eyebrow">Records</div><h2><?= h($definition['label']) ?></h2></div></div>
			<form class="editor" method="post">
				<input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
				<input type="hidden" name="action" value="save">
				<?php if ($editRow): ?><input type="hidden" name="id" value="<?= h($editRow[$definition['pk']]) ?>"><?php endif; ?>
				<?php foreach ($definition['columns'] as $column => $field): ?>
					<label><?= h($field['label']) ?>
						<?php if (in_array($field['type'], ['borrower', 'equipment', 'transaction'], true)): ?>
							<select name="<?= h($column) ?>" required>
								<option value="">Choose...</option>
								<?php foreach ($lookups[$field['type']] as $option): ?><option value="<?= h($option['id']) ?>" <?= (string) ($editRow[$column] ?? '') === (string) $option['id'] ? 'selected' : '' ?>><?= h($option['label']) ?></option><?php endforeach; ?>
							</select>
						<?php elseif ($field['type'] === 'select'): ?>
							<select name="<?= h($column) ?>" required><option value="">Choose...</option><?php foreach ($field['options'] as $option): ?><option <?= ($editRow[$column] ?? '') === $option ? 'selected' : '' ?>><?= h($option) ?></option><?php endforeach; ?></select>
						<?php else: ?><input type="<?= h($field['type']) ?>" name="<?= h($column) ?>" value="<?= h($editRow[$column] ?? '') ?>" <?= $field['type'] === 'number' ? 'min="0"' : '' ?> required><?php endif; ?>
					</label>
				<?php endforeach; ?>
				<div class="actions"><button type="submit"><?= $editRow ? 'Update' : 'Create' ?></button><?php if ($editRow): ?><a class="button secondary" href="?table=<?= h($table) ?>">Cancel</a><?php endif; ?></div>
			</form>
			<?php if (!$rows): ?><div class="empty">No records found.</div><?php else: ?>
				<table><thead><tr><?php foreach (array_keys($definition['columns']) as $column): ?><th><?= h($definition['columns'][$column]['label']) ?></th><?php endforeach; ?><th>Actions</th></tr></thead>
				<tbody><?php foreach ($rows as $row): ?><tr><?php foreach (array_keys($definition['columns']) as $column): ?><td><?= h(displayValue($row[$column], $column, $lookups)) ?></td><?php endforeach; ?><td class="row-actions"><a href="?table=<?= h($table) ?>&edit=<?= h($row[$definition['pk']]) ?>">Edit</a><form method="post" style="display:inline" onsubmit="return confirm('Delete this record?')"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= h($row[$definition['pk']]) ?>"><button type="submit">Delete</button></form></td></tr><?php endforeach; ?></tbody></table>
			<?php endif; ?>
			<p class="db-note">Connection: <?= h($config['host']) ?>:<?= h($config['port']) ?> / <?= h($config['name']) ?></p>
		</section>
	</div>
</main>
</body>
</html>
