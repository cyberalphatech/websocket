<?php
namespace FingerPrint;
use PDOTool;

class routeDevice
{
    public static function getData()
    {
        $deTool = new PDOTool("device");
        $deTool->field("serial_num as serialNum"); 
        $list=$deTool->select('');

        //$list1 = array('serialNum' => 'dev1');
        
        $page = array('device' => $list);
        $result = array('extend' => $page,'code' => 100,'msg' => 'Success！');
        $ret= json_encode($result);
        echo $ret;
        $deTool->writeLog($ret,"echo: ");
    }
}

?>