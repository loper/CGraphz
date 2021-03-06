<?php
$f_id_config_dynamic_dashboard=filter_input(INPUT_GET,'f_id_config_dynamic_dashboard',FILTER_SANITIZE_NUMBER_INT);
$s_id_user=filter_var($_SESSION['S_ID_USER'],FILTER_SANITIZE_NUMBER_INT);

if (isset($_GET['f_id_config_dynamic_dashboard'])) {
   include(DIR_FSROOT.'/html/menu/time_selector.php');
}

echo '<div id="dashboard">';

if ($_GET['f_id_config_dynamic_dashboard']) {

   
   $lib='SELECT 
         cddg.* 
      FROM 
         config_dynamic_dashboard cdd
      LEFT JOIN config_dynamic_dashboard_group cddg
         ON cdd.id_config_dynamic_dashboard=cddg.id_config_dynamic_dashboard
      LEFT JOIN config_dynamic_dashboard_content cddc
         ON cdd.id_config_dynamic_dashboard=cddc.id_config_dynamic_dashboard
      LEFT JOIN auth_user_group aug
         ON cddg.id_auth_group=aug.id_auth_group
      LEFT JOIN auth_user au
         ON aug.id_auth_user=au.id_auth_user
      WHERE aug.id_auth_user=:s_id_user
      AND cdd.id_config_dynamic_dashboard=:f_id_config_dynamic_dashboard
      ORDER BY dash_ordering';
   
   $connSQL=new DB();
   $connSQL->bind('s_id_user',$s_id_user);
   $connSQL->bind('f_id_config_dynamic_dashboard',$f_id_config_dynamic_dashboard);
   $cur_dashboard=$connSQL->row($lib);
   
   if ($cur_dashboard->id_auth_group) {
      $lib='SELECT
            cddc.*
         FROM
            config_dynamic_dashboard_content cddc
         WHERE
            cddc.id_config_dynamic_dashboard=:f_id_config_dynamic_dashboard
         ORDER BY dash_ordering';
            
      $connSQL=new DB();
      $connSQL->bind('f_id_config_dynamic_dashboard',$f_id_config_dynamic_dashboard);
      $all_content=$connSQL->query($lib);
      $cpt_content=count($all_content);
         
      
      for ($i=0; $i<$cpt_content; $i++) {
         $cpt_p=0;
         
         $lib='
            SELECT 
               cs.id_config_server, 
               cs.server_name,
               COALESCE(cs.collectd_version,"'.COLLECTD_DEFAULT_VERSION.'") as collectd_version
            FROM config_server cs
               LEFT JOIN config_server_project csp 
                  ON cs.id_config_server=csp.id_config_server
               LEFT JOIN perm_project_group ppg 
                  ON ppg.id_config_project=csp.id_config_project
               LEFT JOIN auth_group ag 
                  ON ag.id_auth_group=ppg.id_auth_group
               LEFT JOIN auth_user_group aug 
                  ON aug.id_auth_group=ag.id_auth_group
            WHERE aug.id_auth_user=:s_id_user
               AND cs.server_name REGEXP :regex_srv
            GROUP BY id_config_server, server_name
            ORDER BY server_name';

         $connSQL=new DB();
         $connSQL->bind('s_id_user',$s_id_user);
         $connSQL->bind('regex_srv',$all_content[$i]->regex_srv);
         $all_server=$connSQL->query($lib);
         $cpt_server=count($all_server);         
         
         if (isset($time_start) && isset($time_end)) {
             $zoom='ondblclick="Show_Popup($(this).attr(\'src\').split(\'?\')[1],\'\',\''.$time_start.'\',\''.$time_end.'\')"';
         } else {
             $zoom='ondblclick="Show_Popup($(this).attr(\'src\').split(\'?\')[1],\''.$time_range.'\',\'\',\'\')"';
         }

         for ($j=0; $j<$cpt_server; $j++) {
            
            if (is_dir($CONFIG['datadir'].'/'.$all_server[$j]->server_name.'/')) {
               $myregex='#^('.$CONFIG['datadir'].'/'.$all_server[$j]->server_name.'/)('.$all_content[$i]->regex_p_filter.')(?:\-('.$all_content[$i]->regex_pi_filter.'))?/('.$all_content[$i]->regex_t_filter.')(?:\-('.$all_content[$i]->regex_ti_filter.'))?\.rrd#';

               $plugins = preg_find($myregex, $CONFIG['datadir'].'/'.$all_server[$j]->server_name, PREG_FIND_RECURSIVE|PREG_FIND_FULLPATH|PREG_FIND_SORTBASENAME);
               
               foreach ($plugins as $plugin) {
                  $plugin_array[$cpt_p]['servername']=$all_server[$j]->server_name;
                  $plugin_array[$cpt_p]['collectd_version']=$all_server[$j]->collectd_version;

                  preg_match($myregex, $plugin, $matches);
                  
                  if (isset($matches[2])) {
                     $plugin_array[$cpt_p]['p']=$matches[2];
                  } else {
                     $plugin_array[$cpt_p]['p']=null;
                  }
                  if (!is_blank($matches[3])) {
                     $plugin_array[$cpt_p]['pi']=$matches[3];
                     $plugin_array[$cpt_p]['pc']=null;
                     if (substr_count($plugin_array[$cpt_p]['pi'], '-') >= 1 && preg_match($CONFIG['plugin_pcategory'], $plugin_array[$cpt_p]['p'])) {
                        $tmp=explode('-',$plugin_array[$cpt_p]['pi']);
                        $plugin_array[$cpt_p]['pc']=$tmp[0];
                        $plugin_array[$cpt_p]['pi']=implode('-',array_slice($tmp,1));
                     } else if (preg_match($CONFIG['plugin_pcategory'], $plugin_array[$cpt_p]['p'])) {
                        $plugin_array[$cpt_p]['pc']=$plugin_array[$cpt_p]['pi'];
                        $plugin_array[$cpt_p]['pi']=null;
		     }
                  } else {
                     $plugin_array[$cpt_p]['pc']=null;
                     $plugin_array[$cpt_p]['pi']=null;
                  }                  
                  if (isset($matches[4])) {
                     $plugin_array[$cpt_p]['t']=$matches[4];
                  } else {
                     $plugin_array[$cpt_p]['t']=null;
                  }
                  if (!is_blank($matches[5])) {
                     $plugin_array[$cpt_p]['ti']=$matches[5];
                     $plugin_array[$cpt_p]['tc']=null;
                     if (substr_count($plugin_array[$cpt_p]['ti'], '-') >= 1 && preg_match($CONFIG['plugin_tcategory'], $plugin_array[$cpt_p]['p'])) {
                        $tmp=explode('-',$plugin_array[$cpt_p]['ti']);
                        $plugin_array[$cpt_p]['tc']=$tmp[0];
                     } else if (preg_match($CONFIG['plugin_tcategory'], $plugin_array[$cpt_p]['p'])) {
                        $plugin_array[$cpt_p]['tc']=$plugin_array[$cpt_p]['ti'];
                        $plugin_array[$cpt_p]['ti']=null;
                     }
                  } else {
                     $plugin_array[$cpt_p]['tc']=null;
                     $plugin_array[$cpt_p]['ti']=null;
                  }
                  
                  $cpt_p++;
               }
            }
         }

         if ($all_content[$i]->rrd_ordering=='S') {
            $plugin_array = sortArray($plugin_array, array('servername', 'p', 'pc', 'pi', 't', 'tc', 'ti'));
         } else if ($all_content[$i]->rrd_ordering=='P') {
            $plugin_array = sortArray($plugin_array, array('p', 'pc', 'servername', 'pi', 't', 'tc', 'ti'));
         } else if ($all_content[$i]->rrd_ordering=='PI') {
            $plugin_array = sortArray($plugin_array, array('pi', 'p', 'pc', 'servername', 't', 'tc', 'ti'));
         } else if ($all_content[$i]->rrd_ordering=='T') {
            $plugin_array = sortArray($plugin_array, array('t','servername', 'p','pc', 'pi', 'tc', 'ti'));
         } else if ($all_content[$i]->rrd_ordering=='TI') {
            $plugin_array = sortArray($plugin_array, array('ti','servername', 'p', 'pc', 'pi', 't', 'tc'));
         }

         $old_t=null;
         $old_tc=null;
         $old_ti=null;
         $old_pc=null;
         $old_pi=null;
         $old_p=null;
         $old_servername=null;
         
         //$final_array=array_merge_recursive($plugin_array, $final_array);
         foreach ($plugin_array as $plugin) {
            if (! isset(${$plugin['servername'].$plugin['p'].$plugin['pc'].$plugin['pi'].$plugin['t'].$plugin['tc'].$plugin['ti']}) ) {
               ${$plugin['servername'].$plugin['p'].$plugin['pc'].$plugin['pi'].$plugin['t'].$plugin['tc'].$plugin['ti']}=true;
               
               if ($all_content[$i]->rrd_ordering=='S') {
                  if ($old_servername!=$plugin['servername']) {
                     echo '<h1>'.$plugin['servername'].'</h1>';
                     $old_p=null;
                  }
                  if ($old_p!=$plugin['p']) {
                     echo '<h2>'.$plugin['p'].'</h2>';
                  }
               } else if ($all_content[$i]->rrd_ordering=='P') {
                  if ($old_p!=$plugin['p']) {
                     echo '<h1>'.$plugin['p'].'</h1>';
                  }
                  if ($old_pc!=$plugin['pc']) {
                     echo '<h2>'.$plugin['pc'].'</h2>';
                  }
               } else if ($all_content[$i]->rrd_ordering=='PI') {
                  if ($old_pi!=$plugin['pi']) {
                     echo '<h1>'.$plugin['p'].' '.$plugin['pi'].'</h1>';
                  } else if (!$plugin['pi'] && $old_p!=$plugin['p']) {
                     echo '<h1>'.$plugin['p'].'</h1>';
                  }
               } else if ($all_content[$i]->rrd_ordering=='T') {
                  if ($old_t!=$plugin['t']) {
                     echo '<h1>'.$plugin['t'].'</h1>';
                  }
               } else if ($all_content[$i]->rrd_ordering=='TI') {
                  if ($old_ti!=$plugin['ti']) {
                     echo '<h1>'.$plugin['ti'].'</h1>';
                  } else if (!$plugin['ti'] && $old_t!=$plugin['t']) {
                     echo '<h1>'.$plugin['t'].'</h1>';
                  }
               }
            
               if (!preg_match('/^(df|interface|oracle)$/', $plugin['p']) || 
				  (((preg_replace('/[^0-9\.]/','',$plugin['collectd_version']) >= 5)
				  && (preg_replace('/[^a-zA-Z]/','',$plugin['collectd_version']) == 'Collectd') 
				  && $p!='oracle' && $t!='df'))
				  || (preg_replace('/[^a-zA-Z]/','',$plugin['collectd_version']) == 'SSC')
			   ) {
                  $plugin['ti']=null;
                  if ($old_t!=$plugin['t'] or $old_pi!=$plugin['pi'] or $old_pc!=$plugin['pc'] or $plugin['servername']!=$old_servername or $old_tc!=$plugin['tc']) {
                      if ($CONFIG['graph_type'] == 'canvas') {
                         $_GET['h'] = $plugin['servername'];
                         $_GET['p'] = $plugin['p'];
                         $_GET['pc'] = $plugin['pc'];
                         $_GET['pi'] = $plugin['pi'];
                         $_GET['t'] = $plugin['t'];
                         $_GET['tc'] = $plugin['tc'];
                         $_GET['ti'] = $plugin['ti'];
                           
                         chdir(DIR_FSROOT);
                         include DIR_FSROOT.'/plugin/'.$plugin['p'].'.php';
                      } else {
                         if ($time_range!=null) {
                            echo '<img class="imggraph" '.$zoom.' title="'.DBL_CLICK_ZOOM.'" src='.DIR_WEBROOT.'/graph.php?h='.$plugin['servername'].'&amp;p='.$plugin['p'].'&amp;pc='.$plugin['pc'].'&amp;pi='.$plugin['pi'].'&amp;t='.$plugin['t'].'&amp;tc='.$plugin['tc'].'&amp;ti='.$plugin['ti'].'&amp;s='.$time_range.' />'."\n";
                         } else {
                            echo '<img class="imggraph" '.$zoom.' title="'.DBL_CLICK_ZOOM.'" src='.DIR_WEBROOT.'/graph.php?h='.$plugin['servername'].'&amp;p='.$plugin['p'].'&amp;pc='.$plugin['pc'].'&amp;pi='.$plugin['pi'].'&amp;t='.$plugin['t'].'&amp;tc='.$plugin['tc'].'&amp;ti='.$plugin['ti'].'&amp;s='.$time_start.'&amp;e='.$time_end.' />'."\n";
                         }
                      }
                  }
               } else {
                  if ($CONFIG['graph_type'] == 'canvas') {
                     $_GET['h'] = $plugin['servername'];
                     $_GET['p'] = $plugin['p'];
                     $_GET['pc'] = $plugin['pc'];
                     $_GET['pi'] = $plugin['pi'];
                     $_GET['t'] = $plugin['t'];
                     $_GET['tc'] = $plugin['tc'];
                     $_GET['ti'] = $plugin['ti'];
                        
                     chdir(DIR_FSROOT);
                     include DIR_FSROOT.'/plugin/'.$plugin['p'].'.php';
                  } else {
                     if ($time_range!=null) {
                        echo '<img class="imggraph" '.$zoom.' title="'.DBL_CLICK_ZOOM.'" src='.DIR_WEBROOT.'/graph.php?h='.$plugin['servername'].'&amp;p='.$plugin['p'].'&amp;pc='.$plugin['pc'].'&amp;pi='.$plugin['pi'].'&amp;t='.$plugin['t'].'&amp;tc='.$plugin['tc'].'&amp;ti='.$plugin['ti'].'&amp;s='.$time_range.' />'."\n";
                     } else {
                        echo '<img class="imggraph" '.$zoom.' title="'.DBL_CLICK_ZOOM.'" src='.DIR_WEBROOT.'/graph.php?h='.$plugin['servername'].'&amp;p='.$plugin['p'].'&amp;pc='.$plugin['pc'].'&amp;pi='.$plugin['pi'].'&amp;t='.$plugin['t'].'&amp;tc='.$plugin['tc'].'&amp;ti='.$plugin['ti'].'&amp;s='.$time_start.'&amp;e='.$time_end.' />'."\n";
                     }
                  }
               }
        
               $old_t=$plugin['t'];
               $old_tc=$plugin['tc'];
               $old_ti=$plugin['ti'];
               $old_pc=$plugin['pc'];
               $old_pi=$plugin['pi'];
               $old_p=$plugin['p'];
               $old_servername=$plugin['servername'];
            }
         }
      }
   }
}


echo '</div>';
echo '<script type="text/javascript" src="'.DIR_WEBROOT.'/lib/javascriptrrd/CGP.js"></script>';

?>
