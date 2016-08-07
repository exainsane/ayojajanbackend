<?php
defined('BASEPATH') OR exit('No direct script access allowed');
define("MODE_ADD","add");
define("MODE_EDIT","edit");
define("MODE_DELETE","delete");
define("MODE_GET","get");

function getString($str){
	return $str == null? $str : null;
}
class App extends CI_Controller {
	function __construct(){
		parent::__construct();
		$this->load->helper("exit");
		$this->load->helper("url");
	}
	function index(){
		var_dump($this);
	}
	function tableList(){
		$tbs = $this->db->list_tables();

		foreach ($tbs as $t) {
			echo $t." > ".base64_encode($t)."\n";
		}
	}
	private function setLastLoginAndToken($id,$token){			
		$m_user_table = "m_user";
		$this->db
			->where("gcm_id",$token)
			->update($m_user_table,array("gcm_id"=>""));

		$dbu = array(
			"gcm_id"=>$token,
			"last_login"=>'CURRENT_TIMESTAMP');

		$this->db->where("id",$id)->update($m_user_table,$dbu);
			
	}
	function login(){
		$username = $this->input->post("u");
		$password = md5($this->input->post("p"));

		$this->db->select("id");
		$this->db->from("m_user");
		$this->db->where(
			array(
				"email"=>$username,
				"password"=>$password));

		$q = $this->db->get();
		if($q->num_rows() == 1)
		{
			$id = $q->result();
			$id = end($id);
			$id = $id->id;
			$this->setLastLoginAndToken($id,$this->input->post("fct"));
			
			$rarray = array(
			"success"=>true,
			"token"=>$this->getToken($q->result()));
		}
		else{
			$rarray = array(
			"success"=>false);
		}

		exit(json_encode($rarray));
	}
	private function getToken($objarray){
		$objarray = end($objarray);
		$id = $objarray->id;

		$strtkn = base64_encode($id).date("YYYY-MM-DD HH MM SS").rand(0,90);
		$tkn = base64_encode($strtkn);
		$this->db->delete("t_access_token",array("id_user"=>$id));

		$this->db->insert(
			"t_access_token",
			array(
				"id_user"=>$id,
				"token"=>$tkn));

		return $tkn;
	}
	function getData($id_table, $id_data = null, $mode = null){
		$table = ($id_table);

		if(!is_numeric(array_search($table, $this->db->list_tables())))
			return;

		if($id_data == MODE_ADD)
			$this->newRow($table);
		elseif($id_data != null && $mode == null)
			$this->getRow($table, $id_data);
		else if($id_data != null && $mode == MODE_EDIT)
			$this->editRow($table, $id_data);
		else if($id_data != null && $mode == MODE_DELETE)
			$this->deleteRow($table, $id_data);
		else
			$this->getRows($table);
	}
	private function getColumns($table){
		return $this->db->list_fields($table);
	}
	private function validateToken($token){
		$q = $this->db
				->select("id_user")
				->from("t_access_token")
				->where(array("token"=>$token))
				->get();

		if($q->num_rows() != 1){
			exitInvalidToken();
		}

		$uidt = $q->result();
		$uidt = end($uidt);
		$uid = $uidt->id_user;
		return $uid;
	}
	public function tokenValidation(){
		$token = $this->input->post("token");
		$firebaseToken = $this->input->post("fct");

		$uid = $this->validateToken($token);
		
		$this->setLastLoginAndToken($uid,$firebaseToken);

		$rarray = array(
			"success"=>true);
		exit(json_encode($rarray));
	}
	public function registerSeller(){
		$token = $this->input->post("token");

		$uid = $this->validateToken($token);

		$table = "t_register_seller";

		$cols = $this->db->list_fields($table);

		$ndt = array();
		foreach ($cols as $col) {
			$val = $this->input->post($col);
			if($val != null){
				$ndt[$col] = $val;
			}
		}

		$ndt["id_user"] = $uid;

		$rarray = array(
			"success"=>$this->db->insert($table, $ndt));

		exitWithResult($rarray);

	}
	public function getSellerStatus(){
		$token = $this->input->post("token");
		
		$id = $this->validateToken($token);

		$this->db
				->select("*")
				->from("t_register_seller")
				->where(array("id_user"=>$id));
		$q = $this->db->get();

		if($q->num_rows() != 1){
			$rarray = array(
				"success"=>false);
			
		}else{
			$udt = $q->result();			
			$udt = end($udt);			
			$imgs = array();
			foreach (explode(";", $udt->seller_photo) as $img) {
				array_push($imgs, $id."-".$img);
			}
			$udt->seller_photo = $imgs;
			$rarray = array(
				"success"=>true,
				"udt"=>$udt);
		}
		exitWithResult($rarray);

	}
	public function base(){
		echo base_url();
	}
	public function sellerimage($action = null, $arg0 = null, $arg1 = null, $arg2 = null){		
		switch ($action) {
			case MODE_ADD:
				$this->sellerImageAdd();
				break;
			case MODE_GET:
				$this->sellerImageGet($arg0,$arg1,$arg2);
				break;
			default:
				# code...
				break;
		}
	}
	private function sellerImageGet($imgid, $widthResize, $token){		
		$d = explode("-", $imgid);
		$uid = $d[0];
		unset($d[0]);
		$imgpath = $this->config->item("upload_sellerimage_dir").$uid."/".implode("-", $d);

		$this->load->helper("image");

		$img = new SimpleImage($imgpath);
		$img->resizeToWidth($widthResize);

		$img->output();
	}
	private function sellerImageAdd(){
		$uid = $this->validateToken($this->input->post("token"));

		$table = "t_register_seller";

		$q = $this->db
			->where("id_user",$uid)
			->from($table)
			->get();

		if($q->num_rows() != 1){
			$rarray = array();
			$rarray['success'] = false;
			$rarray['error'] = "Can't find seller registration!";
			exitWithResult($rarray);
		}

		$q = $q->result();
		$q = end($q);
		$imgs = explode(";", $q->seller_photo);

		if(strlen($imgs[0]) < 1)
			unset($imgs[0]);

		$upcfg = array();
		$upload_path = $this->config->item("upload_sellerimage_dir").$uid."/";

		$this->load->helper('file');
		$this->load->helper('dir');

		recursive_check_add_dir($upload_path,'/');

		$imgname = "imgupload-".count(get_filenames($upload_path)).".jpg";
		$imgurl = $upload_path.$imgname;
		$upload = write_file($imgurl, base64_decode($this->input->post('img')));

		$rarray = array();
		$rarray['success'] = $upload;
		$rarray['upath'] = $imgurl;

		if(!$upload)
			$rarray['error'] = "Can't store image!";
		else
		{
			$rarray['url'] = base_url($imgurl);

			array_push($imgs, $imgname);
			$this->db->from($table)
				->where("id_user",$uid)
				->set("seller_photo",implode(";", $imgs))
				->update();
		}

		exitWithResult($rarray);
	}
	public function getUserData(){
		$token = $this->input->post("token");

		$q = $this->db->select("id_user")
				->where(array("token"=>$token))
				->get("t_access_token");		
		if($q->num_rows() != 1){
			$rarray = array(
				"success"=>false,
				"msg"=>"Invalid Token!");
			exitWithResult($rarray);
		}

		$userinf = $q->result();
		$userinf = end($userinf);
		$userid = $userinf->id_user;

		$this->db
			->select("id,nama,email,last_login,join_date")
			->from("m_user")
			->where(array("id"=>$userid));
		$q = $this->db->get();



		if($q->num_rows() == 1){

			$udt = $q->result();
			$udt = end($udt);
			$this->db
				->select("id")
				->from("t_register_seller")
				->where(array("id_user"=>$udt->id));
			$sq = $this->db->get();
			$rarray = array(
			"success"=>true,
			"udt"=>$udt,
			"seller"=>$sq->num_rows() == 1	);
			exitWithResult($rarray);
		}
		else{
			$rarray = array(
			"success"=>false,
			"msg"=>"Userid not found!");
			exitWithResult($rarray);	
		}
	}
	public function register(){
		$table = 'm_user';
		$cols = $this->getColumns($table);

		$post = $this->input->post(null);
		if(!isset($post["email"])){
			$rarray = array(
			"success"=>false);
			exitWithResult($rarray);
		}
		$chk = $this->db->where(array("email"=>$post["email"]))
			->get($table)->num_rows();
		if($chk > 0){			
			$rarray = array(
			"success"=>false);
			exitWithResult($rarray);
		}		
		$uar = array();
		foreach ($cols as $val) {
			if(isset($post[$val])){
				if($val == "password")
					$post[$val] = md5($post[$val]);

				$uar[$val] = $post[$val];
			}
		}		
		$rarray = array(
			"success"=>$this->db->insert($table, $uar));
		exitLine:
		exitWithResult($rarray);
	}
	private function exitWithResult($array){
		exit(json_encode($array));
	}
	private function getRows($table){		
		$req = $this->input->post(null);

		$sort = isset($req['sort'])?$req['sort']:null;
		$sort_mode = isset($req['sort_mode'])?$req['sort_mode']:null;
		$rows = intval(isset($req['rows'])?$req['rows']:null);
		$page = intval(isset($req['page'])?$req['page']:null);
		$find = isset($req['find'])?$req['find']:null;

		$db_fields = $this->getColumns($table);

		$this->db->select("*");
		$this->db->from($table);

		if($rows != null && $page != null)
			$this->db->limit($page - 1 * $rows,$rows);

		if($find != null){
			foreach ($db_fields as $field) {
				$this->db->like($field, $find);
			}
		}

		if($sort != null){
			$this->db->order_by($sort,$sort_mode);
		}

		$q = $this->db->get();

		$rarray = 
		array(
			"rows"=>$q->num_rows(),
			"data"=>$q->result());
		exit(json_encode($rarray));

	}
	private function newRow($table){
		$cols = $this->getColumns($table);

		$post = $this->input->post(null);

		$uar = array();
		foreach ($cols as $val) {
			if(isset($post[$val])){
				if($val == "password")
					$post[$val] = md5($post[$val]);

				$uar[$val] = $post[$val];
			}
		}		
		$rarray = array(
			"success"=>$this->db->insert($table, $uar));

		exit(json_encode($rarray));
	}
	
	private function getRow($table,$id){
		$this->db->select("*");
		$this->db->from($table);
		$this->db->where("id",$id);

		$q = $this->db->get();
		$rst = $q->result();

		$rarray = 
		array(
			"exist"=>$q->num_rows() == 1,
			"data"=>end($rst));

		exit(json_encode($rarray));
	}
	private function editRow($table,$id){
		$cols = $this->getColumns($table);

		$post = $this->input->post(null);

		$uar = array();
		foreach ($cols as $val) {
			if(isset($post[$val]))
				$uar[$val] = $post[$val];
		}

		$rarray = array(
			"success"=>$this->db->update($table, $uar, array("id"=>$id)));

		exit(json_encode($rarray));
	}
	private function deleteRow($table,$id){
		$rarray = array(
			"success"=>$this->db->delete($table, array("id"=>$id)));

		exit(json_encode($rarray));
	}
}?>