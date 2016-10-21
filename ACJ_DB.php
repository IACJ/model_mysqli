<?php
// by ACJ  2016 4 30
class DB {

	// Variables
	public $err;		//错误描述
	public $affected_rows;		//影响行数

	protected $mysqli;
	protected $table;  //$table每次执行sql后不会重置

	protected $stmt;

	//下列变量会被$this->reset()重置
	protected $sql;
	protected $param;
	protected $ptype;
	protected $data;
	protected $where_param; 
	protected $where_ptype; 
	protected $where_sql; 


	public function __construct ($host,$user,$pw,$database) {
		$this->err = '';
		$this->table = '';
		$this->reset();

		$this->mysqli=@new mysqli($host,$user,$pw,$database);
		if($this->mysqli->connect_errno){
			die('Connect Error:'.$mysqli->connect_error);
		}
		$this->mysqli->set_charset('UTF8');

	}
	public function __destruct() {
		$this->mysqli->close();
	}

	//选择数据表
	public function table($tb) {
		if (!is_array($tb)) {
			$this->err='参数应为数组';
			return 0;
		}
		$this->table=$tb;
		return 1;
	}


	public function where($val = array()) {
		// return 0 if error occurs,
		// return 1 if everything is OK.
		// 传入空数组相当于 where 1.
	 	$this->reset();

		if (!is_array($val)) {
			$this->err='参数应为数组';
			return 0;
		}
			
		if (empty($val))
			return 1;

		// build where
		$fst = true;	
		foreach($val as $key => $val) {
			if (!$fst)
				$this->where_sql .= 'and ';
			$fst = false;

			$this->where_sql .='`'.$key.'` = ? ';
			array_push($this->where_param,$val);
			$this->where_ptype .= $this->get_type($val);
		}	
		return 1;
	}

	public function select($col = array()) {
		// return 0 if it meets error,
		// return an array if no error occurs.	
		if (!is_array($col)) {
			$this->reset();
			$this->err='参数应为数组';
			return 0;
		}
		if (empty($this->table)) {
			$this->reset();
			$this->err='未选择数据表';
			return 0;
		}
					
		// build sql
		if (empty($col)){ 
			$this->sql = 'SELECT * ';
		} else {
			$this->sql = 'SELECT ';
			
			$fst = true;	
			foreach($col as $val) {
				if (!$fst)
					$this->sql .= ', ';
				$fst = false;

				$this->sql .='`'.$val.'` ';
			}	
		}
		$this->sql .= 'FROM ';
		foreach ($this->table as $val) {
			$this->sql .='`' .$val.'` ';
		} ;

		//准备sql并绑定参数
		if(!$this->prepareAndBind()) {
			$this->reset();
			 // $this->err已在内层函数中修改
			return 0;
		}
		
    	//执行stmt并返回结果集  
		$this->stmt->execute();
		$this->reset();
		$result = $this->stmt->get_result();
		$rows=array();
		while ($row = $result->fetch_array(MYSQLI_ASSOC)){
		    $rows[]=$row;

		}
		return($rows);		
	}

	public function insert($col ) {
		// return 0 if it meets error,
		// return 1 if no error occurs.	
		if (!is_array($col)) {
			$this->reset();
			$this->err='参数应为数组';
			return 0;
		}
		if (empty($this->table)) {
			$this->reset();
			$this->err='未选择数据表';
			return 0;
		}
		if (count($this->table) != 1 || empty($this->table[0])) {
			$this->reset();
			$this->err='仅应选择一个数据表';
			return 0;
		}
		if (!empty($this->where_sql)) {
			$this->reset();
			$this->err='insert不应有where条件';
			return 0;
		}
					
		// build sql
		$this->sql = 'INSERT INTO `' .$this->table[0].'` ' ;
		$fst = true;
		$left  = '';
		$right = '';	
		foreach($col as $key => $val) {
			if (!$fst) {
				$left  .= ', ';
				$right .= ', ';
			} else {
				$left  .= '( ';
				$right .= '( ';
			}
			$fst = false;
			$left  .= '`'.$key.'` ';
			$right  .= '? ';
			array_push($this->param,$val);
			$this->ptype .= $this->get_type($val);
		}
		$left  .= ') ';
		$right .=') ';
		$this->sql .=$left .'VALUES '.$right;	


		//准备sql并绑定参数
		if(!$this->prepareAndBind()) {
			$this->reset();
			 // $this->err已在内层函数中修改
			return 0;
		}
		
    	//执行stmt  
		$this->stmt->execute();
		$this->reset();
		return 1;		
	}

	public function delete() {
		// return 0 if it meets error,
		// return 1 if no error occurs.	
	
		if (empty($this->table)) {
			$this->reset();
			$this->err='未选择数据表';
			return 0;
		}
		if (count($this->table) != 1 || empty($this->table[0])) {
			$this->reset();
			$this->err='仅应选择一个数据表';
			return 0;
		}
		if (empty($this->where_sql)) {
			$this->reset();
			$this->err='delete必须有where条件';
			return 0;
		}
					
		// build sql
		$this->sql = 'DELETE FROM `' .$this->table[0].'` ' ;

		//准备sql并绑定参数
		if(!$this->prepareAndBind()) {
			$this->reset();
			 // $this->err已在内层函数中修改
			return 0;
		}
		

    	//执行stmt  
		$this->stmt->execute();
		$this->reset();
		$this->affected_rows= $this->stmt->affected_rows;	
		if ($this->affected_rows == 0) {
			$this->err='没有符合删除条件的数据';
			return 0;
		}
		
		return  1;		
	}

	public function update($col) {
		// return 0 if it meets error,
		// return 1 if no error occurs.	
		if (!is_array($col)) {
			$this->reset();
			$this->err='参数应为数组';
			return 0;
		}
		if (empty($this->table)) {
			$this->reset();
			$this->err='未选择数据表';
			return 0;
		}
		if (count($this->table) != 1 || empty($this->table[0])) {
			$this->reset();
			$this->err='仅应选择一个数据表';
			return 0;
		}
		if (empty($this->where_sql)) {
			$this->reset();
			$this->err='update必须有where条件';
			return 0;
		}
					
		// build sql
		$this->sql = 'UPDATE `' .$this->table[0].'` SET ' ;
		$fst = true;
	
		foreach($col as $key => $val) {
			if (!$fst) {
				$this->sql .=', ';		
			} 
			$fst = false;
			$this->sql  .= '`'.$key.'` = ? ';
			array_push($this->param,$val);
			$this->ptype .= $this->get_type($val);
		}

		//准备sql并绑定参数
		if(!$this->prepareAndBind()) {
			$this->reset();
			 // $this->err已在内层函数中修改
			return 0;
		}
		
    	//执行stmt  
		$this->stmt->execute();
		$this->reset();
		$this->affected_rows= $this->stmt->affected_rows;	
		if ($this->affected_rows == 0) {
			$this->err='没有符合更改条件的数据';
			return 0;
		}
		
		return  1;		
	}

	public function count($col = array()) {
		// return 0 if it meets error,
		// return a num if no error occurs.	
		if (!is_array($col)) {
			$this->reset();
			$this->err='参数应为数组';
			return 0;
		}
		if (empty($this->table)) {
			$this->reset();
			$this->err='未选择数据表';
			return 0;
		}
					
		// build sql
		if (empty($col)){ 
			$this->sql = 'SELECT COUNT(* ';
		} else {
			$this->sql = 'SELECT COUNT( ';
			
			$fst = true;	
			foreach($col as $val) {
				if (!$fst)
					$this->sql .= ', ';
				$fst = false;

				$this->sql .='`'.$val.'` ';
			}	
		}
		$this->sql .= ') FROM ';
		foreach ($this->table as $val) {
			$this->sql .='`' .$val.'` ';
		} 

		//准备sql并绑定参数
		if(!$this->prepareAndBind()) {
			$this->reset();
			 // $this->err已在内层函数中修改
			return 0;
		}
		
    	//执行stmt并返回结果集  
		$this->stmt->execute();
		$this->reset();
		$result = $this->stmt->get_result();
		$row = $result->fetch_array(MYSQLI_NUM) ;		
		if ($row[0] == 0) {
			$this->err='没有符合条件的数据';
			return 0;
		}
		return($row[0]);		
	}

	//如其名
	protected function prepareAndBind() {	
		//｀主句sql及参数｀  与 ｀where语句的sql及参数｀ 合并
		if (!empty($this->where_sql)) {
			$this->sql .= 'where '. $this->where_sql;
			$this->param = array_merge($this->param,$this->where_param);
			$this->ptype .= $this->where_ptype;
		}
		
		//用于调试
		//$this->showbug();

		//准备并绑定
		$this->stmt = $this->mysqli->stmt_init();
		if(!$this->stmt->prepare($this->sql))
		{
		    $this->err= 'sql表名或字段错误：Failed to prepare statement';
		    return 0;
		}
		if (!empty($this->param)) {
			$this->data = array_merge((array)$this->ptype,$this->param);
			call_user_func_array(array($this->stmt, 'bind_param'), $this->arr2ref($this->data));
		}
		return 1;	
	}

	//重置条件
	protected function reset() {
		$this->where_param=array();
		$this->where_ptype='';
		$this->where_sql='';
		$this->param=array();
		$this->ptype='';
		$this->sql='';
		$this->data=array();
		$this->affected_rows=0;
        
     }

     //获取参数类型
     protected function get_type($temp) {
		if (is_int($temp))
			return 'i';
		if (is_double($temp))
			return 'd';
		if (is_string($temp))
			return 's';	

		//不能识别的视为字符串
		return 's';
	}


	//为解决一个bug而加的引用
	private function arr2ref($value) {
          $refs = array();
          foreach ($value as $k => $v) {
               $refs[$k] = &$value[$k];
          }
          return $refs;
     }

	//用于调试
	protected function showbug() {
	
		echo "<br/>";
		echo "params:<br/>";
		print_r($this->param);
		echo  "<br/>";
		echo $this->ptype.'<br/>';
		echo $this->sql.'<br/>';
		echo "<br/>";
	}
}