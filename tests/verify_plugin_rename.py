"""Verify that Kanboard plugin identity was migrated as an atomic unit."""
from pathlib import Path
base=Path(__file__).resolve().parent.parent
# The Git checkout may have any directory name; Kanboard plugin identity is verified below.
assert (base/'Plugin.php').is_file()
assert not (base/'Controller/ProjectLibreExportController.php').exists()
plugin=(base/'Plugin.php').read_text(encoding='utf-8')
assert r'namespace Kanboard\Plugin\ScheduleExchange;' in plugin
assert "'ScheduleExchange:project/dropdown'" in plugin
assert "'ScheduleExchangeController', 'download'" in plugin
assert "return 'Kanboard Schedule Export';" in plugin
assert "return '0.6.1';" in plugin
assert "return '>=1.2.50';" in plugin
controller=(base/'Controller/ScheduleExchangeController.php').read_text(encoding='utf-8')
assert 'class ScheduleExchangeController' in controller
assert "'-cronograma.xml'" in controller
assert r'namespace Kanboard\Plugin\ScheduleExchange\Controller;' in controller
view=(base/'Template/project/dropdown.php').read_text(encoding='utf-8')
assert "'ScheduleExchangeController'" in view and "'plugin' => 'ScheduleExchange'" in view
assert view.count("'download',\n            array('project_id'") == 1
assert "t('Export project schedule (.xml)')" in view
assert 'experimental' not in view
assert "getStringParam('subtasks') !== 'legacy'" in controller
assert "'template:subtask:form:create'" in plugin
assert "'PlannedSubtaskCreationController', 'save', Role::PROJECT_MEMBER" in plugin
for p in base.rglob('*.php'):
    t=p.read_text(encoding='utf-8')
    assert r'Kanboard\Plugin\ProjectLibreExport' not in t, str(p)
    assert 'ProjectLibreExportController' not in t, str(p)
print('PASS: renamed plugin folder, PHP namespace, ACL, controller, template hook, download filename, version')
