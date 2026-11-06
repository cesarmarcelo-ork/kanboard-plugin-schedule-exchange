<?php
require __DIR__ . '/../Exporter/ProjectXmlExporter.php';
use Kanboard\Plugin\ScheduleExchange\Exporter\ProjectXmlExporter;

function requireOk($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
function mustReject($callback, $message) {
    try { $callback(); } catch (InvalidArgumentException $expected) { return; }
    throw new RuntimeException('Not rejected: '.$message);
}
function dep($ft,$fc,$fs,$tt,$tc,$ts,$type,$lag=0) {
    return array('project_id'=>7,'from_entity_type'=>$ft,'from_task_id'=>$fc,
        'from_subtask_id'=>$fs,'to_entity_type'=>$tt,'to_task_id'=>$tc,
        'to_subtask_id'=>$ts,'relation_type'=>$type,'lag_days'=>$lag);
}
$base = strtotime('2026-11-09 08:00 UTC');
$task = function ($id,$title,$position) use ($base) {
    return array('id'=>$id,'project_id'=>7,'title'=>$title,'position'=>$position,
        'date_started'=>$base,'date_due'=>$base+86400,'time_estimated'=>8,'date_completed'=>0);
};
$tasks=array($task(20,'Preparar',1),$task(30,'Montar',2));
$subs=array(20=>array(array('id'=>200,'task_id'=>20,'title'=>'Separar')),
            30=>array(array('id'=>300,'task_id'=>30,'title'=>'Validar')));
$rels=array(
    dep('task',20,null,'task',30,null,'FS',0),
    dep('task',20,null,'subtask',30,300,'SS',1),
    dep('subtask',20,200,'task',30,null,'FF',2),
    dep('subtask',20,200,'subtask',30,300,'SF',-1),
);
$builder=new ProjectXmlExporter('UTC');
$project=array('id'=>7,'name'=>'Teste de rede');
$xml=$builder->generate($project,$tasks,array(),'2026-11-09',$subs,true,array(),array(),$rels);
preg_match_all('~<Task>(.*?)</Task>~s',$xml,$matches);
$rows=array();
foreach ($matches[1] as $row) {
    if (!preg_match('~<UID>([0-9]+)</UID>~',$row,$uid)) continue;
    $rows[(int)$uid[1]]=$row;
}
requireOk(count($rows)===7,'Two cards, two subtasks, two period rows, one project row expected');
requireOk(count($rows)===count(array_unique(array_keys($rows))),'UIDs must be unique');
$findUid=function($marker) use($rows){
    foreach ($rows as $id=>$row) if (strpos($row,$marker)!==false) return $id;
    throw new RuntimeException('Missing marker '.$marker);
};
$uidA=$findUid('Source: kanboard-card-period:7:20');
$uidB=$findUid('Source: kanboard-card-period:7:30');
$uidSa=$findUid('Source: kanboard-subtask:20:200');
$uidSb=$findUid('Source: kanboard-subtask:30:300');
$check=function($targetUid,$sourceUid,$type,$lag)use($rows){
    $fragment='<PredecessorUID>'.$sourceUid.'</PredecessorUID>';
    $start=strpos($rows[$targetUid],$fragment);
    requireOk($start!==false,'Missing predecessor link '.$sourceUid.' -> '.$targetUid);
    $end=strpos($rows[$targetUid],'</PredecessorLink>',$start);
    requireOk($end!==false,'Malformed predecessor link');
    $part=substr($rows[$targetUid],$start,$end-$start);
    requireOk(strpos($part,'<Type>'.$type.'</Type>')!==false,'Wrong MSPDI relationship');
    requireOk(strpos($part,'<LinkLag>'.($lag*14400).'</LinkLag>')!==false,'Wrong MSPDI lag');
};
$check($uidB,$uidA,1,0);
$check($uidSb,$uidA,3,1);
$check($uidB,$uidSa,0,2);
$check($uidSb,$uidSa,2,-1);
requireOk(strpos($rows[20],'<PredecessorLink>')===false,'Summary card must not receive activity predecessor links');
requireOk(strpos($rows[30],'<PredecessorLink>')===false,'Summary card must not receive activity predecessor links');

$cycle=array(dep('subtask',20,200,'subtask',30,300,'FS'),dep('subtask',30,300,'subtask',20,200,'FS'));
mustReject(function()use($builder,$project,$tasks,$subs,$cycle){
    $builder->generate($project,$tasks,array(),'2026-11-09',$subs,true,array(),array(),$cycle);
},'subtask cycle');
$bad=array(dep('subtask',20,300,'task',30,null,'FS'));
mustReject(function()use($builder,$project,$tasks,$subs,$bad){
    $builder->generate($project,$tasks,array(),'2026-11-09',$subs,true,array(),array(),$bad);
},'wrong subtask parent');
mustReject(function()use($builder,$project,$tasks,$subs,$rels){
    $builder->generate($project,$tasks,array(),'2026-11-09',$subs,false,array(),array(),$rels);
},'legacy export must not silently omit subtask links');
$missingStart=$tasks;
$missingStart[0]['date_started']=0;
mustReject(function()use($builder,$project,$missingStart,$subs,$rels){
    $builder->generate($project,$missingStart,array(),'2026-11-09',$subs,true,array(),array(),$rels);
},'unscheduled summary-card endpoint');
echo "PASS: four MSPDI dependency links, summary UID mapping, cycle and ownership checks\n";
