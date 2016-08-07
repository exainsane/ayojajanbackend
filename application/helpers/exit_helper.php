<?php  
function exitWithResult($array){
	exit(json_encode($array));
}
function exitInvalidToken(){
	$rarray = array(
				"success"=>false,
				"msg"=>"Invalid token");
	exitWithResult($rarray);
}
?>