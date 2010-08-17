<?php
// No HTTP GZIP must be here!
require_once "overall.php";

$tables = null;
$id = @$_GET['id']? @$_GET['id'] : @$_POST['item']['id'];

if (!empty($_POST['doDelete'])) {
	$DB->update("DELETE FROM item WHERE id=?", $id);
	redirect("index.php", "Item deleted.");
}

if (!empty($_POST['doSave']) || !empty($_POST['doTest']) || !empty($_POST['doRecalc'])) {
	try {
		$DB->beginTransaction();
		$item = validateItem($_POST['item']);
		if (!$id) {
			$DB->update(
				"INSERT INTO item(id, name, sql, dsn_id, recalculatable, created, modified, relative_to) VALUES(?, ?, ?, ?, ?, ?, ?, ?)",
				$id = $DB->getSeq(), $item['name'], $item['sql'], $item['dsn_id'], $item['recalculatable'], time(), time(), $item['relative_to']
			);
 		} else {
			$DB->update(
				"UPDATE item SET name=?, sql=?, dsn_id=?, recalculatable=?, modified=?, relative_to=? WHERE id=?",
				$item['name'], $item['sql'], $item['dsn_id'], $item['recalculatable'], time(), $item['relative_to'], $id
			);
 		}
 		if (!empty($_POST['doSave'])) {
 			$DB->commit();
	 		redirect("index.php#$id", "Data is saved.");
		} else if (!empty($_POST['doTest']) || !empty($_POST['doRecalc'])) {
			list ($to, $back, $period) = parseToBackPeriod($_POST);
			$periods = strlen($period)? array($period) : array_keys(getPeriods()); 
			$tables = array();
			try {
				echo '<div id="log">';
				foreach ($periods as $period) {
					recalcItemRow($id, $to, $back, $period);
					$data = generateTableData($to + 1, $back, $period, $id);
					$periods = getPeriods();
					$tables[$periods[$period]] = generateHtmlTableFromData($data);
				}
				echo '</div><script type="text/javascript">document.getElementById("log").style.display="none"</script>';
			} catch (Exception $e) {
				echo '</div><br/><style>#log { color: red; border: 3px solid red; padding: 4px; }</style>';
				throw $e;
			}
			if (!empty($_POST['doTest'])) {
				$DB->rollBack();
			} else {
				$DB->commit();
				$_POST['item']['id'] = $id;
			}
		}
	} catch (Exception $e) {
		$DB->rollBack();
		addMessage($e->getMessage());
	}
} else {
	$_POST['item'] = array();
	if ($id) {
		$_POST['item'] = $DB->selectRow("SELECT * FROM item WHERE id=?", $id);
	} else if (@$_GET['clone']) {
		$_POST['item'] = $DB->selectRow("SELECT * FROM item WHERE id=?", $_GET['clone']);
		unset($_POST['item']['id']); // very important!
	} else {
		$_POST['item']['sql'] = "SELECT COUNT(*)\nFROM some_table\nWHERE created BETWEEN \$FROM AND \$TO\n";
	}
}

$SELECT_DSNS = array();
foreach ($DB->select("SELECT id, name FROM dsn ORDER BY name") as $row) {
	$SELECT_DSNS[$row['id']] = $row['name'];
}

$SELECT_ITEMS = array();
foreach ($DB->select("SELECT id, name FROM item ORDER BY name") as $row) {
	$SELECT_ITEMS[$row['id']] = $row['name'];
}

$SELECT_PERIODS = getPeriods();

if (!$tables && $id) {
	$to = $DB->selectCell("SELECT MAX(created) FROM data WHERE item_id=?", $id);
	if (!$to) $to = time();
	foreach ($SELECT_PERIODS as $period => $periodName) {
		$data = generateTableData($to, 30, $period, $id);
		$tables[$periodName] = generateHtmlTableFromData($data);
	}
}

$title = $id? 'Edit item <a href="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '">' . htmlspecialchars($_POST['item']['name']) . '</a>' : "Add a new item";
template(
	"item", 
	array(
		"titleHtml"  => $title,
		"title" => strip_tags($title),
		"tables" => $tables,
	)              
);