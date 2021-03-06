<?php

# Collectd APC UPS plugin
require_once 'type/Default.class.php';
require_once 'modules/collectd.inc.php';

## LAYOUT
# apcups/
# apcups/charge.rrd
# apcups/frequency-input.rrd
# apcups/percent-load.rrd
# apcups/temperature.rrd
# apcups/timeleft.rrd
# apcups/voltage-battery.rrd
# apcups/voltage-input.rrd
# apcups/voltage-output.rrd

$obj = new Type_Default($CONFIG);

switch($obj->args['type']) {
	case 'cache_ratio' :
		//$obj -> data_sources = array('value');
		$obj -> rrd_title = sprintf('Cache Ratio%s', !empty($obj -> args['pinstance']) ? ' (' . $obj -> args['pinstance'] . ')' : '');
		$obj -> rrd_vertical = 'Hits';
	break;
	case 'connections' :
		//$obj -> data_sources = array('value');
		$obj -> rrd_title = sprintf('Connections%s', !empty($obj -> args['pinstance']) ? ' (' . $obj -> args['pinstance'] . ')' : '');
		$obj -> rrd_vertical = 'Numbers';
	break;
	case 'memory' :
		require_once 'type/GenericStacked.class.php';
		$obj = new Type_GenericStacked($CONFIG);

		//$obj -> data_sources = array('value');
		$obj -> rrd_title = sprintf('Memory%s', !empty($obj -> args['pinstance']) ? ' (' . $obj -> args['pinstance'] . ')' : '');
		$obj -> rrd_vertical = 'MB';
	break;
	case 'counter' :
		require_once 'type/GenericStacked.class.php';
		$obj = new Type_GenericStacked($CONFIG);
		//$obj -> data_sources = array('value');
		$obj -> rrd_title = sprintf('Counter%s', !empty($obj -> args['pinstance']) ? ' (' . $obj -> args['pinstance'] . ')' : '');
		$obj -> rrd_vertical = 'Number';
	break;
	case 'percent' :
		//$obj -> data_sources = array('value');
		$obj -> rrd_title = sprintf('Lock Ratio Time%s', !empty($obj -> args['pinstance']) ? ' (' . $obj -> args['pinstance'] . ')' : '');
		$obj -> rrd_vertical = '%';
	break;
	case 'file_size' :
		require_once 'type/GenericStacked.class.php';
		$obj = new Type_GenericStacked($CONFIG);
		$obj -> data_sources = array('bytes');
		$obj -> rrd_title = sprintf('File Size%s', !empty($obj -> args['pinstance']) ? ' (' . $obj -> args['pinstance'] . ')' : '');
		$obj -> rrd_vertical = 'Bytes';
	break;
	case 'total_operations' :
		require_once 'type/GenericStacked.class.php';
		$obj = new Type_GenericStacked($CONFIG);
		$obj -> rrd_title = sprintf('Total Operation%s', !empty($obj -> args['pinstance']) ? ' (' . $obj -> args['pinstance'] . ')' : '');
		$obj -> rrd_vertical = 'Operation';
	break;
	default:
		error_image('Unknown graph type :'.PHP_EOL.str_replace('&',PHP_EOL,$_SERVER['QUERY_STRING']));
	break;
}
$obj -> rrd_format = '%5.1lf%s';

# backwards compatibility
if (preg_replace('/[^0-9\.]/','',$CONFIG['version']) < 5 && in_array($obj -> args['type'], array('frequency', 'percent', 'timeleft'))) {

	$obj -> data_sources = array($obj -> args['type']);

	$obj -> ds_names[$obj -> args['type']] = $obj -> ds_names['value'];
	unset($obj -> ds_names['value']);

	$obj -> colors[$obj -> args['type']] = $obj -> colors['value'];
	unset($obj -> colors['value']);
}

collectd_flush($obj -> identifiers);
$obj -> rrd_graph();
?>
