<?php
/**
 * Regression: raw timestamps on the same day, persisted native-subtask plans,
 * description-based periods, and valid MSPDI exports remain distinct.
 */
require __DIR__ . '/../Exporter/ProjectXmlExporter.php';

use Kanboard\Plugin\ScheduleExchange\Exporter\ExportDateValidationException;
use Kanboard\Plugin\ScheduleExchange\Exporter\ProjectXmlExporter;

function ensureDate($ok, $message)
{
    if (!$ok) throw new RuntimeException($message);
}
$builder = new ProjectXmlExporter('UTC');
$project = array('id' => 71, 'name' => 'Date validation test');
$base = strtotime('2026-11-16 16:00:00 UTC');
$card = function ($id, $name, $start, $finish) {
    return array(
        'id' => $id, 'project_id' => 71, 'title' => $name, 'position' => $id,
        'date_started' => $start, 'date_due' => $finish, 'description' => '',
        'time_estimated' => 8, 'date_completed' => 0
    );
};
$tasks = array(
    $card(10, 'Cartão A <script>alert(1)</script>', $base, $base - 3600),
    $card(11, 'Cartão B', $base + 86400, $base),
    $card(12, 'Sem datas', 0, 0),
    $card(13, 'Marco com datas iguais', $base, $base)
);
$tasks[0]['description'] = '[SE_SUBTASK_PERIOD id=501 start=2026-11-17T16:00:00 end=2026-11-17T15:00:00]';
$subs = array(
    10 => array(
        array('id' => 500, 'task_id' => 10, 'title' => 'Planejamento persistido'),
        array('id' => 501, 'task_id' => 10, 'title' => 'Período na descrição')
    )
);
$plans = array(
    500 => array('task_id' => 10, 'project_id' => 71,
        'planned_start' => '2026-11-16T14:00:00',
        'planned_end' => '2026-11-16T12:00:00')
);
$badXmlReturned = false;
try {
    $badXmlReturned = $builder->generate($project, $tasks, array(), '2026-11-16', $subs, true, $plans);
} catch (ExportDateValidationException $e) {
    $issues = $e->getIssues();
    ensureDate(count($issues) === 4, 'Four card/subtask date inconsistencies must be reported together: ' . count($issues));
    $details = implode("\n", $issues);
    foreach (array('Cartão #10', 'Cartão #11', 'Subtarefa #500', 'Subtarefa #501',
        'Planejamento persistido', 'Período na descrição',
        '16/11/2026 16:00:00', '16/11/2026 15:00:00',
        '2026-11-16T14:00:00', '2026-11-17T15:00:00') as $expected) {
        // The source labels retain their case, but stored/described period text is not translated.
        ensureDate(stripos($details, $expected) !== false, 'Missing actionable issue detail: ' . $expected);
    }
}
ensureDate($badXmlReturned === false, 'Inconsistent dates must never produce an XML document');

$tasks[0]['date_due'] = $base + 3600;
$tasks[1]['date_due'] = $base + 2 * 86400;
$tasks[0]['description'] = '[SE_SUBTASK_PERIOD id=501 start=2026-11-17T15:00:00 end=2026-11-17T16:00:00]';
$plans[500]['planned_end'] = '2026-11-16T16:00:00';
$xml = $builder->generate($project, $tasks, array(), '2026-11-16', $subs, true, $plans);
ensureDate(strpos($xml, '<Project xmlns=') !== false, 'Corrected dates should export XML');
ensureDate(strpos($xml, 'Source: kanboard-subtask:10:500') !== false, 'Persisted subtask schedule preserved');
ensureDate(strpos($xml, 'Source: kanboard-subtask:10:501') !== false, 'Declared subtask schedule preserved');

$tasks[0]['date_started'] = 0;
$tasks[0]['date_due'] = 0;
$xml = $builder->generate($project, $tasks, array(), '2026-11-16', $subs, true, $plans);
ensureDate(strpos($xml, 'SEM DATAS') !== false, 'Undated cards remain valid');

$plans[500]['planned_end'] = '2026-11-16T14:00:00';
try {
    $builder->generate($project, $tasks, array(), '2026-11-16', $subs, true, $plans);
    throw new RuntimeException('Equal subtask endpoints must be rejected');
} catch (ExportDateValidationException $e) {
    ensureDate(count($e->getIssues()) === 1, 'Equal endpoints must identify the single invalid subtask');
}

echo "PASS: aggregated ID/title/timestamp date errors, same-day inversion, XML preserved after corrections\n";
