<?php
namespace FingerPrint;
use PDOTool;

class routeRecord
{
    public static function getData($curPage)
    {
        $deTool = new PDOTool("records");
        if($curPage<1)
            $curPage=1;   
        $deTool->field('COUNT(*) as count');
        $list=$deTool->select('');
        $count=$list[0]['count'];
        $perpages=8;
        $pages=intval(($count+$perpages-1)/$perpages);
        $start=($curPage-1)*$perpages;

        $deTool->field("enroll_id as enrollId,records_time as recordsTime,mode,intOut as intout,event,device_serial_num as deviceSerialNum,temperature,image");
        $deTool->limit("".$start.','.$perpages);
        //$deTool->order("id desc");
        $list=$deTool->select('');

        //$list1 = array('enrollId' => '123', 'recordsTime' => 'name1', 'mode' => 'test123.jpg', 'intout' => 'id');
        
        $navigatepageNums[0]=1;

        for($i=1;$i<$pages;$i++)
            $navigatepageNums[$i]=$i+1;

        $pageInfo = array('list' => $list,'pages' => $pages,'total' => $count,'pageNum' => $curPage,'startRow' =>$start,
                'hasPreviousPage' => $curPage>1,'hasNextPage' => $curPage< $pages,'navigatepageNums' => $navigatepageNums);
        $page = array('pageInfo' => $pageInfo);
        $result = array('extend' => $page,'code' => 100,'msg' => 'Success！');
        echo json_encode($result);
    }

    public static function getAllLog($sn)
    {
        $message="{\"cmd\":\"getalllog\",\"stn\":true}";

        $deTool = new PDOTool("machine_command");

        $machineCommand=array("serial" => $sn
                                ,"name" => "getalllog"
                                ,"status" => 0
                                ,"send_status" => 0
                                ,"err_count" => 0
                                ,"gmt_crate" => date("Y-m-d H:i:s")
                                ,"gmt_modified" => date("Y-m-d H:i:s")
                                ,"content" => $message);

        $deTool->add($machineCommand);

        routeCommand::returnSuccess();
    }
    public static function getNewLog($sn)
    {
        $message="{\"cmd\":\"getnewlog\",\"stn\":true}";

        $deTool = new PDOTool("machine_command");

        $machineCommand=array("serial" => $sn
                                ,"name" => "getnewlog"
                                ,"status" => 0
                                ,"send_status" => 0
                                ,"err_count" => 0
                                ,"gmt_crate" => date("Y-m-d H:i:s")
                                ,"gmt_modified" => date("Y-m-d H:i:s")
                                ,"content" => $message);

        $deTool->add($machineCommand);

        routeCommand::returnSuccess();
    }
}

?>