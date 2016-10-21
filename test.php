<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<pre>
<?php
    include "./ACJ_DB.php";
    $db= new db('localhost','root','','test');

    /*$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$
	$
	$
	$ ____所有public成员函数，均可用此种方式查询操作错误____		
	$	
	$
	$	
    $	 //选择一个表
   	$	 if (!$db->table( array('user') ) )
   	$	 	 echo $db->err;     				//若无错误则无输出
	$
	$	 //查询
   	$	 if (!$db->SELECT( 'dsafadfasdfghjkl' ) )
   	$		 echo $db->err;						//若有错误则有输出
	$
	$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$ */
    

    //选择一个表
   	$db->table( array('user'));				//用数组传入要选择的表（多次生效）（支持多表查询）
   		
   	
   	//select样例1
   	echo "<hr>";
   	$result = $db->select();				//select * from `selected_table`;
   	print_r($result);
   
   	//select样例2
   	echo "<hr>";
   	$db->where(array('name' => 'test1' ));			//用数组传入要where的条件
   	$result = $db->select();		
   	print_r($result);

   	//select样例3
   	echo "<hr>";
   	$db->where(array('name' => 'hahahha' ));				
   	$result = $db->select(array('age','uid'));		//用数组传入要select的字段
   	print_r($result);


   	//______insert使用须知______!!!
   	//1.数据表仅限1个  （删、改 同理）
   	//2.insert必须有参数，且参数类型为数组
   	//3.insert不应有where条件


   	//insert错误示范：选择了多个数据表
   	echo "<hr>";
   	$db->table( array('user' , 'mysqli'));				
   	if (!$db->insert(array('name' => 'test1' ,'age'=>10)) )
   		 echo $db->err;		

   	//insert正确示范：
   	echo "<hr>";
   	$db->table( array('user'));				
   	if (!$db->insert(array('name' => 'test1' ,'age'=>10,'account' => 'test')) )
   		echo "操作错误:".$db->err;
   	else
   		echo "添加成功"	;				

   	//______delete使用须知______
   	//1.数据表仅限1个  
   	//2.delete不接受参数
   	//3.delete必须有where条件  （______不允许删除所有数据!!______）

   	//delete错误示范：无where条件的delete
   	echo "<hr>";
   	$db->table( array('user'));				
   	if (!$db->delete())
   		 echo $db->err;	      //无where条件的delete
   	else
   		echo "删除成功，影响行数：".$rows;

   	//delete正确示范： 
   	echo "<hr>";
    $db->where(array('uid' => 26));			
   	if (!$db->delete()) 
   		 echo $db->err;	      //若找不到要删的数据，将在$db->err中提示
   	else
   		echo "删除成功，影响行数：".$db->affected_rows;

   	//______update使用须知______
   	//1.数据表仅限1个  
   	//2.update接受数组参数
   	//3.update必须有where条件  （______不允许更改所有数据!!______）


   	//update示范： 
   	echo "<hr>";
    $db->where(array('uid' => 5));			
   	if (!$db->update(array('name' => 'hahahha'))) 
   		 echo $db->err;	      //若找不到要改的数据，将在$db->err中提示
   	else
   		echo "更改成功，影响行数：".$db->affected_rows;

   	//______count和select用法相似______
   	//count不接受参数

   	//样例1
   	echo "<hr>";
   	if (!$result = $db->count()) 
   		 echo $db->err;	      //若找不到要改的数据，将在$db->err中提示
   	else
   		echo "结果为：".$result;

   	//样例2
   	echo "<hr>";
   	$db->where(array('name' => 'test1' ));				
   	$result = $db->count();		//用数组传入要select的字段
   	echo "结果为：".$result;
   	

   	
?>
</pre>