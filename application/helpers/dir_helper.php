<?php  
function recursive_check_add_dir($dir,$path_delimiter,$currentdir = null){
	$dirs = explode($path_delimiter, $dir);
	$cdir = explode($path_delimiter, $currentdir);
	if(strlen($cdir[0]) < 1) unset($cdir[0]);
	
	if(!isset($dirs[0]) || strlen($dirs[0]) < 1) return;

	if(!file_exists($currentdir.$dirs[0]))
		mkdir($currentdir.$dirs[0]);

	array_push($cdir, $dirs[0]);
	unset($dirs[0]);

	recursive_check_add_dir(implode($path_delimiter, $dirs),$path_delimiter,implode($path_delimiter, $cdir).'/');
}
?>