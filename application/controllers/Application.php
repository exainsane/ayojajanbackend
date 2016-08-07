<?php
defined('BASEPATH') OR exit('No direct script access allowed');
define("MODE_EDIT","edit");
define("MODE_DELETE","delete");
function getString($str){
	return $str == null? $str : null;
}
class App extends CI_Controller {
	function Application(){				
	}
	function index(){
		$this->getData("bXNfbWhz");
	}
	public function getData($id_table, $id_data = null, $mode = null){
		$table = base64_decode($id_table);
		if($id_data != null && $mode == null)
			$this->getRow($table, $id_data);
		else if($id_data != null && mode == MODE_EDIT)
			$this->editRow($table, $id_data);
		else if($id_data != null && mode == MODE_DELETE)
			$this->deleteRow($table, $id_data);
		else
			$this->getRows($table);
	}
	private function getColumns($table){
		return $this->db->list_fields($table);
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
	private function getRow($table,$id){

	}
	private function editRow($table,$id){

	}
	private function deleteRow($table,$id){

	}
}?>