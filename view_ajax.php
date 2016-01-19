<?php
if(ROLE != 'admin') die('权限不足');
global $m;
//参数替换
function getgs($gs){
	$data = str_ireplace('{百度ID}','(.*)',$gs);
	$data = str_ireplace('{百度BDUSS}','([0-9a-zA-Z-]+)',$data);
	return $data; 
}
//导入BDUSS
if (isset($_GET['new'])) {
  $import_str = !empty($_POST['import_str']) ? $_POST['import_str'] : '';
  if(empty($import_str)) ReDirect(SYSTEM_URL.'index.php?mod=admin:setplug&plug=xy_import&error_msg=导入文本不能为空！');
  $import_str = preg_replace('/[\r\n]+/', PHP_EOL, $import_str);
  $arr = explode(PHP_EOL,$import_str);
  $total = count($arr);
  $gs = option::get('xy_import_gs');
  $hs=$cf=$ok=$err=$up=$sx=0;
  for($i=0;$i<$total;$i++){
	preg_match('/'.getgs($gs).'/',$arr[$i], $re);
	if (!empty($re[2])) {
	  $hs++;
	  $x = $m->once_fetch_array("SELECT COUNT(*) AS bduss FROM `".DB_NAME."`.`".DB_PREFIX."baiduid` where `bduss` = '".$re[2]."';");
	  if ($x['bduss'] > 0) {
		$cf++;
	  } else {
		$baidu_name = sqladds(getBaiduId($re[2]));
		if(empty($baidu_name)){
		  $sx++;
		} else {
		  $z = $m->once_fetch_array("SELECT COUNT(*) AS bdname FROM `".DB_NAME."`.`".DB_PREFIX."baiduid` where `name` = '".$baidu_name."';");
		  if ($z['bdname'] > 0) {
			$sql = "UPDATE `".DB_NAME."`.`".DB_PREFIX."baiduid` SET `uid`='".UID."', `bduss`='".$re[2]."' where `name`='".$baidu_name."';";
			$m->query($sql) ? $up++ : $err++;
		  } else {
			$sql = "INSERT INTO `".DB_NAME."`.`".DB_PREFIX."baiduid` (`uid`, `bduss`, `name`) VALUES ('".UID."', '".$re[2]."', '".$baidu_name."');";
			$m->query($sql) ? $ok++ : $err++;
		  }
		}
	  }
	}
  }
  $ok > 0 ? $info = ',"status":"success"' : $info='';
  die('{"info":"批量导入完成。<br/><br/>匹配行数：['.$hs.']<br/>导入成功：['.$ok.']<br/>导入失败：['.$err.']<br/>更新记录：['.$up.']<br/>失效数量：['.$sx.']<br/>已存在数：['.$cf.']<br/>"'.$info.'}');
}
//基本设置
if (isset($_GET['set'])) {
	if(!empty($_POST['gs'])) {
		$gs = ' '.$_POST['gs'];
		if(!stristr($gs,'{百度ID}')) {
			die('{"type":"error","emsg":"缺少参数 <strong>{百度ID}</strong> ！"}');
		} elseif (!stristr($gs,'{百度BDUSS}')) {
			die('{"type":"error","emsg":"缺少参数 <strong>{百度BDUSS}</strong> ！"}');
		}
		option::set('xy_import_gs',$_POST['gs']);
		die('{"type":"success","regular":"'.getgs($_POST['gs']).'"}');
	} else {
		die('{"type":"error","emsg":"导入格式不能为空！"}');
	}
}
