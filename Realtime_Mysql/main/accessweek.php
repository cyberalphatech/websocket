<?php
namespace FingerPrint;
use PDOTool;
class AccessWeek 
{
    public $id;
    public $serial;
    public $name;
    public $sunday;
    public $monday;
    public $tuesday;
    public $wednesday;
    public $thursday;
    public $friday;
    public $saturday;

    
    private function init()
    {
        $this->id=$_POST['id'];
        $this->serial=$_POST['serial'];
        $this->name=$_POST['name'];
        $this->sunday=$_POST['sunday'];
        $this->monday=$_POST['monday'];
        $this->tuesday=$_POST['tuesday'];
        $this->wednesday=$_POST['wednesday'];
        $this->thursday=$_POST['thursday'];
        $this->friday=$_POST['friday'];
        $this->saturday=$_POST['saturday'];
    }
    public  function set()
    {
        $this->init();
       
        $deTool = new PDOTool("access_week");
        
        $list=$deTool->select("where id=".$this->id);
        if(count($list)>0)
        {
            routeCommand::returnFail();
            return;
        }
        $data=array("id" => $this->id
                    ,"serial" => $this->serial
                    ,"name" => $this->name
                    ,"monday" => $this->monday 
                    ,"tuesday" => $this->tuesday
                    ,"wednesday" => $this->wednesday
                    ,"thursday" => $this->thursday
                    ,"friday" => $this->friday
                    ,"saturday" => $this->saturday
                    ,"sunday"=> $this->sunday);

        $deTool->add($data);
        
        $this->setAccessWeek($deTool);

        
        routeCommand::returnSuccess();
    }
    private function setAccessWeek($deTool)
    {
        $message="{\"cmd\":\"setdevlock\",\"weekzone\":[";
        $deTool->order("id asc");
        $accessWeeks=$deTool->select('');
        
        $id=1;
        foreach ($accessWeeks as $key => $value){
            $idcur= intval($value["id"]);
            while($id<$idcur)
            {
                $message.="{\"week\":[";
                    $message.="{\"day\":" . 0 . "},";
                    $message.="{\"day\":" . 0 . "},";
                    $message.="{\"day\":" . 0 . "},";
                    $message.="{\"day\":" . 0 . "},";
                    $message.="{\"day\":" . 0 . "},";
                    $message.="{\"day\":" . 0 . "},";
                    $message.="{\"day\":" . 0 . "}]},"; 
                $id++;
            }
            $message.="{\"week\":[";
            $message.="{\"day\":" . $value["sunday"] . "},";
            $message.="{\"day\":" . $value["monday"] . "},";
            $message.="{\"day\":" . $value["tuesday"] . "},";
            $message.="{\"day\":" . $value["wednesday"] . "},";
            $message.="{\"day\":" . $value["thursday"] . "},";
            $message.="{\"day\":" . $value["friday"] . "},";
            $message.="{\"day\":" . $value["saturday"] . "}]},";
            $id++;
        }
        while($id<=8){
            $message.="{\"week\":[";
                $message.="{\"day\":" . 0 . "},";
                $message.="{\"day\":" . 0 . "},";
                $message.="{\"day\":" . 0 . "},";
                $message.="{\"day\":" . 0 . "},";
                $message.="{\"day\":" . 0 . "},";
                $message.="{\"day\":" . 0 . "},";
                $message.="{\"day\":" . 0 . "}]},";    
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
