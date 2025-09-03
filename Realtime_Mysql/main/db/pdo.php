<?php
require "config.php";

class PDOTool extends PDO
{
    protected $tabName = ''; //存储表名
    protected $sql = '';//存储最后执行的sql语句
    
    protected $limit = '';//存储limit条件
    protected $order = '';//存储order排序条件
    protected $field = '*';//存储要查询的字段
    
    //protected $where = ''; //存储where条件
    protected $allFields = [];//存储当前表的所有字段

    /**
    * 构造方法
    * @param string $tabName 要操作表名
    */

    public function __construct($tabName)
    {
        //连接数据库
        parent::__construct('mysql:host='.MYSQL_HOST.';dbname='.MYSQL_DB.';charset=utf8;port='.MYSQL_PORT,MYSQL_USER,MYSQL_PASS);

        //存储表名
        $this->tabName = $tabName;
        //获取当前数据表中有哪些字段
    // $this->getFields();
    }

    protected function getFields()
    {
        $sql = "desc {$this->tabName}";
        //echo "$sql";
        $stmt = $this->query($sql);

        /*
        if ($stmt) {
            $arr = $stmt->fetchAll(2);
            //从二维数组中取出指定下标的列
            $this->allFields = array_column($arr,'Field');
        } else {
            die('表名错误');
        }
        */

    }

    /**
    * 添加数据
    * @param array $data 要添加的数组
    */

    public function add($data)
    {
        /*
        //过滤非法字段：将数据表里面木有的字段干掉
        foreach ($data as $k=>$v) {
        if (!in_array($k,$this->allFields)) {
        unset($data[$k]);
        }
        }*/

        //判断是否全是非法字段
        if (empty($data))
            return;

        $keys = join(',',array_keys($data));
        $vals = join("','",$data);

        $sql = "insert into {$this->tabName}({$keys}) values('{$vals}')";
    // echo $sql;
        $this->sql = $sql;
        return $this->exec2($sql);

    }
    
    /**
    * 根据id删除数据
    * @param int $id 要删除的id
    * @return int 返回受影响行数
    */

    public function delete($where)
    {
        $sql = "delete from {$this->tabName} {$where}";
        return (int)$this->exec2($sql);

    }

    /**
    * 修改数据
    * @param array $data 要改的数据
    * @return int 返回受影响的行数
    */

    public function save($data,$where)
    {
        $str = '';
        foreach ($data as $k=>$v) {
            $str .= "`$k`='$v',";
            }

        $str=substr($str,0,-1);

        /*
        if (in_array($k,$this->allFields)) {
        $str .= "`$k`='$v',";
        } else {
        unset($data[$k]);
        }
        }*/

        // echo $str;exit;
        //判断是否全特么是非法字段
        if (empty($str)) {
            return;
        }

        //去除右边的,
        $str = rtrim($str,'');
        $sql = "update {$this->tabName} set $str {$where}";
        $this->sql = $sql;
        return (int)$this->exec2($sql);
    }
    public static function writeLog($save,$type="SQL: ")
    {
        $logFilePath = './log/debug.txt';
        if (is_readable($logFilePath)) {
            $size = filesize($logFilePath);
            if( $size>1024*1024*2) //2M
            {
                $logFilePath2 = './log/debug2.txt';
                if (is_readable($logFilePath2))
                    unlink($logFilePath2);
                rename($logFilePath,$logFilePath2);
            }
        }

        if(strlen($save)>200)
            $save=substr($save,0,200);
        file_put_contents($logFilePath,  $type.$save." [".date("Y-m-d H:i:s")."]\r\n",FILE_APPEND);
    }
    public function exec2($sql)
    {
        $save=$sql;
        $this->writeLog($save,"Exc: ");

        $ret= (int)$this->exec($sql);

        return $ret;
    }
    /**
    * 查询并返回二维数组
    * @return array 查到了返回二维数组，没查到返回空数组
    */

    public function select($where,$bSave=true)
    {
        $sql = "select {$this->field} from {$this->tabName} {$where} {$this->order} {$this->limit}";
        $this->sql = $sql;
        if($bSave)
        {
            $this->writeLog($sql);
        }

        //发送查询sql
        $stmt = $this->query($sql);

        if ($stmt) {
            return $stmt->fetchAll(2);
        }
        return [];
    }

    /**
    * 查询并返回1条数据的一维数组
    * @param int $id 要查询的id
    * @return array 返回查到的数据
    */

    public function find($id)
    {
        $sql = "select {$this->field} from {$this->tabName} where id={$id} limit 1";
        $this->sql = $sql;
        //发送查询sql
        $stmt = $this->query($sql);
        if ($stmt) {
        return $stmt->fetch(2);
        }
        return [];
    }

    /**
    * 统计总条数
    * @return int 返回查到的条数
    */

    public function count($where)
    {
        $sql = "select count(*) from {$this->tabName} {$where} limit 1";
        $this->sql = $sql;
        //发送查询sql
        $stmt = $this->query($sql);
        if ($stmt) {
        return (int)$stmt->fetch()[0];
        }
        return 0;

    }

    /**
    * 获取最后执行的sql语句
    * @return string sql语句
    */

    public function _sql()
    {
        return $this->sql;
    }

    /**
    * 处理limit条件
    * @param string $str limit条件
    * @return object 返回自己，保证连贯操作
    */

    public function limit($str)
    {
        $this->limit = 'limit '.$str;
        return $this;

    }
    public function tableName($str)
    {
        if($this->tabName!=$str)
        {
            $this->tabName = $str;
            //$this->getFields();
        }
        $this->order ='';
        $this->field='*';
        return $this;

    }
    /**
    * 处理order排序条件
    * @param string $str order条件
    * @return object 返回自己，保证连贯操作
    */

    public function order($str)
    {
        $this->order = 'order by '.$str;
        return $this;
    }

    /**
    * 设置要查询的字段信息
    * @param string $str 要查询的字段
    * @return object 返回自己，保证连贯操作
    */
    public function field($str)
    {
        $this->field = $str;
        return $this;
    }
}

?>
