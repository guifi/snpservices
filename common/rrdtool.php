<?php

function executeEx($cmd,&$retstr) {
 $retval=0;
 exec($cmd,$retstr,$retval);
 return $retval==0;
}

function rrd_last_compat($fname) {
 global $rrdtool_path;

 $cmd=$rrdtool_path." last ".$fname;

 if (!executeEx($cmd,$retstr))
  return 0;

 return $retstr[0];
}

function rrd_fetch_compat($fname,$opts,$nopts) {
 global $rrdtool_path;

 $result['start']=0;
 $result['end']=0;
 $result['step']=0;
 $result['ds_cnt']=0;
 $result['ds_namv']=array();
 $result['data']=array();
 $cmd=$rrdtool_path." fetch ".$fname." ".implode(" ",$opts);

 if (!executeEx($cmd,$retstr))
  return $result;

 $tmp=preg_replace('/\s\s+/', ' ',$retstr[0]);
 $tmp=preg_replace('/^\s+/', '',$tmp);
 $tmp=preg_replace('/\s+$/', '',$tmp);

 $result['ds_namv']=explode(' ',$tmp);
 $result['ds_cnt']=count($result['ds_namv']);
 unset($retstr[0]);
 unset($retstr[1]);

 foreach($retstr as $line) {
  list($ltime,$lvalues)=explode(': ',$line);
  if ($result['start']!=0 && $result['step']==0)
   $result['step']=$ltime-$result['start'];
  if ($result['start']==0)
   $result['start']=$ltime;
  $result['end']=$ltime;
  foreach(explode(' ',$lvalues) as $value)
   $result['data'][]=$value;
 }

 return $result;
}

?>
