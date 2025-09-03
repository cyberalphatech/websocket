<?php
namespace FingerPrint;
use PDOTool;
class AccessDay 
{
    public $id;
    public $serial;
    public $name;
    public $startTime1;
    public $endTime1;
    public $startTime2;
    public $endTime2;
    public $startTime3;
    public $endTime3;
    public $startTime4;
    public $endTime4;
    public $startTime5;
    public $endTime5;

    public static function get()
    {
        $deTool = new PDOTool("access_day");
        $list=$deTool->select('');
        $extend = array('accessdays' => $list);

        routeCommand::returnSuccessContent("extend",$extend);
    }
    private function init()
    {
        $this->id=$_POST['id'];
        $this->serial=$_POST['serial'];
        $this->name=$_POST['name'];
        $this->startTime1=$_POST['startTime1'];
        $this->endTime1=$_POST['endTime1'];

        $this->startTime2=$_POST['startTime2'];
        $this->endTime2=$_POST['endTime2'];

        $this->startTime3=$_POST['startTime3'];
        $this->endTime3=$_POST['endTime3'];

        $this->startTime4=$_POST['startTime4'];
        $this->endTime4=$_POST['endTime4'];

        $this->startTime5=$_POST['startTime5'];
        $this->endTime5=$_POST['endTime5'];
    }
    public  function set()
    {
        $this->init();

        $deTool = new PDOTool("access_day");
        
        $list=$deTool->select("where id=".$this->id);
        if(count($list)>0)
        {
            routeCommand::returnFail();
            return;
        }
        $data=array("id" => $this->id
                    ,"serial" => $this->serial
                    ,"name" => $this->name
                    ,"start_time1" => $this->startTime1 
                    ,"end_time1" => $this->endTime1
                    ,"start_time2" => $this->startTime2 
                    ,"end_time2" => $this->endTime2
                    ,"start_time3" => $this->startTime3 
                    ,"end_time3" => $this->endTime3
                    ,"start_time4" => $this->startTime4 
                    ,"end_time4" => $this->endTime4
                    ,"start_time5" => $this->startTime5 
                    ,"end_time5" => $this->endTime5
                );

        $deTool->add($data);
        
        $this->setAccessDay($deTool);

        
        routeCommand::returnSuccess();
    }
    private function setAccessDay($deTool)
    {
        $message="{\"cmd\":\"setdevlock\",\"dayzone\":[";

        $deTool->order("id asc");
        $accessDay=$deTool->select('');

        $id=1;
  
        foreach ($accessDay as $key => $value){

            $idcur= intval($value["id"]);
            while($id<$idcur)
            {
                $message.="{\"day\":[";
                    $message.="{\"section\":\"" . "00:00~00:00" . "\"},";
                    $message.="{\"section\":\"" . "00:00~00:00" . "\"},";
                    $message.="{\"section\":\"" . "00:00~00:00" . "\"},";
                    $message.="{\"section\":\"" . "00:00~00:00" . "\"},";
                    $message.="{\"section\":\"" . "00:00~00:00" . "\"}]},";
                $id++;
            }
            $message.="{\"day\":[";
            $message.="{\"section\":\"" . $value["start_time1"] .'~'.$value["end_time1"]. "\"},";
            $message.="{\"section\":\"" . $value["start_time2"] .'~'.$value["end_time2"] . "\"},";
            $message.="{\"section\":\"" . $value["start_time3"] .'~'.$value["end_time3"] . "\"},";
            $message.="{\"section\":\"" . $value["start_time4"] .'~'.$value["end_time4"]. "\"},";
            $message.="{\"section\":\"" . $value["start_time5"] .'~'.$value["end_time5"]. "\"}]},";
            $id++;
        }
        while($id<=8){
            $message.="{\"day\":[";
                $message.="{\"section\":\"" . "00:00~00:00" . "\"},";
                $message.="{\"section\":\"" . "00:00~00:00" . "\"},";
                $message.="{\"section\":\"" . "00:00~00:00" . "\"},";
                $message.="{\"section\":\"" . "00:00~00:00" . "\"},";
                $message.="{\"section\":\"" . "00:00~00:00" . "\"}]},";
            $id++;
        }
        $message=substr($message,0,-1);
        
        $message.="]}";

        $deTool->tableName("device");
        $deTool->field("serial_num as serialNum"); 
        $list=$deTool->select('');
        $deTool->tableName("machine_command");
        foreach ($list as $key => $value){
            $machineCommand=array("serial" => $value["serialNum"]
                                    ,"name" => "setdevlock"
                                    ,"status" => 0
                                    ,"send_status" => 0
                                    ,"err_count" => 0
                                    ,"gmt_crate" => date("Y-m-d H:i:s")
                                    ,"gmt_modified" => date("Y-m-d H:i:s")
                                    ,"content" => $message
                        );

                $deTool->add($machineCommand);
        }
                
    }
}

?>
