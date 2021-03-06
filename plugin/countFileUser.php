<?php

# Collectd count file user


require_once 'type/Default.class.php';
require_once 'modules/collectd.inc.php';


$obj = new Type_Default($CONFIG);
$obj->ds_names = array(
        'value' => 'Files',
);


$obj->rrd_title = sprintf('%s', $obj->args['pinstance']);
$obj->rrd_format = '%5.1lf%s';
$obj->rrd_vertical = 'Number of files';

collectd_flush($obj->identifiers);
$obj->rrd_graph();
?>
