<?php
namespace FingerPrint;
require "main/sendobject.php";
use PDOTool;
use FingerPrint\routeSendCommand;

class routeCommand
{
    public static function returnSuccess()
    {
        $result = array('code' => 100,'msg' => 'Success！');
        echo json_encode($result);
    }
    public static function returnSuccessContent($name,$data)
    {
        $result = array('code' => 100,'msg' => 'Success！',$name => $data);
        echo json_encode($result);
    }
    public static function returnFail()
    {
        $result = array('code' => 200,'msg' => 'Fail！');
        echo json_encode($result);
    }
    public static function getUserList($sn)
    {
        $message="{\"cmd\":\"getuserlist\",\"stn\":true}";

        $deTool = new PDOTool("device");
        //$deTool->field("serial_num as serialNum"); 
        //$list=$deTool->select("");

        $deTool->tableName("machine_command");
        //foreach($list as $key=>$value)
        {
            $machineCommand=array("serial" => $sn //$value['serialNum']
                                ,"name" => "getuserlist"
                                ,"status" => 0
                                ,"send_status" => 0
                                ,"err_count" => 0
                                ,"gmt_crate" => date("Y-m-d H:i:s")
                                ,"gmt_modified" => date("Y-m-d H:i:s")
                                ,"content" => $message
                    );

            $deTool->add($machineCommand);
        }        
        routeCommand::returnSuccess();
    }
    public static function getUserInfo($sn)
    {
        $deTool = new PDOTool("person INNER JOIN enrollinfo ON person.id= enrollinfo.enroll_id");
        $deTool->field("person.id,enroll_id as enrollId,backupnum as num");
        $deTool->order('person.id asc');
        $list=$deTool->select("");

        $deTool->tableName("machine_command");
        foreach($list as $sub=>$value)
        {
            if($value['enrollId'] && !is_null($value['num']))
            {
                $message="{\"cmd\":\"getuserinfo\",\"enrollid\":".$value['enrollId'].",\"backupnum\":"
                                    .$value['num']."}";
                $machineCommand=array("serial" => $sn
                                    ,"name" => "getuserinfo"
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
        routeCommand::returnSuccess();
    }

    public static function setUserToDevice2($sn)
    {
        $deTool = new PDOTool("person INNER JOIN enrollinfo ON person.id= enrollinfo.enroll_id");
        $deTool->field("name,roll_id,person.id,enroll_id as enrollId,backupnum as num,signatures");
        $deTool->order('person.id asc');
        $list=$deTool->select('');

        $deTool->tableName("machine_command");
        foreach($list as $sub=>$value)
        {
            $backupnum=$value['num'];
            if(!is_null($backupnum))
            {
                $admin=$value['roll_id'];
                $record=$value['signatures'];
                $message="";
                if ($backupnum==11||$backupnum==10) {
                    $message="{\"cmd\":\"setuserinfo\",\"enrollid\":".$value['enrollId']. ",\"name\":\"" . $value['name'] ."\",\"backupnum\":" . $backupnum
                            . ",\"admin\":" . $admin . ",\"record\":" . $record . "}"; 
                }
                else
                {
                    $message="{\"cmd\":\"setuserinfo\",\"enrollid\":".$value['enrollId']. ",\"name\":\"" . $value['name'] ."\",\"backupnum\":" . $backupnum
                            . ",\"admin\":" . $admin . ",\"record\":\"" . $record . "\"}"; 
                }

                $machineCommand=array("serial" => $sn
                                    ,"name" => "setuserinfo"
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
        routeCommand::returnSuccess();
    }
    public static function setUsernameToDevice($sn)
    {
        $deTool = new PDOTool("person");
        $list=$deTool->select('');

        $message="{\"cmd\":\"setusername\",\"count\":".count($list).",\"record\":[";

        $deTool->tableName("machine_command");
        foreach($list as $key=>$value)
        {
            $message=$message."{\"enrollid\":".$value['id'].",\"name\":\"" . $value['name']."\"},";
        }
        $message=substr($message, 0, -1)."]}";

        $machineCommand=array("serial" => $sn
                            ,"name" => "setusername"
                            ,"status" => 0
                            ,"send_status" => 0
                            ,"err_count" => 0
                            ,"gmt_crate" => date("Y-m-d H:i:s")
                            ,"gmt_modified" => date("Y-m-d H:i:s")
                            ,"content" => $message);

        $deTool->add($machineCommand);
                
        routeCommand::returnSuccess();
    }

    public static function initSystem($sn)
    {
        $deTool = new PDOTool("machine_command");

        $message="{\"cmd\":\"initsys\"}";

        $machineCommand=array("serial" => $sn
                            ,"name" => "initsys"
                            ,"status" => 0
                            ,"send_status" => 0
                            ,"err_count" => 0
                            ,"gmt_crate" => date("Y-m-d H:i:s")
                            ,"gmt_modified" => date("Y-m-d H:i:s")
                            ,"content" => $message);

        $deTool->add($machineCommand);
                
        routeCommand::returnSuccess();
    }
    public static function setLocckGroup()
    {
        $message="{\"cmd\":\"setdevlock\",\"lockgroup\":[";
        $message.="{\"group\":" . $_POST['group1'] . "},";
        $message.="{\"group\":" . $_POST['group2'] . "},";
        $message.="{\"group\":" . $_POST['group3'] . "},";
        $message.="{\"group\":" . $_POST['group4']. "},";
        $message.="{\"group\":" . $_POST['group5']. "}]}";


        $deTool = new PDOTool("device");
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

        routeCommand::returnSuccess();
    }
    public static function setUserLock($sn)
    {
        $message="{\"cmd\":\"setuserlock\",\"count\":1,\"record\":[{";
        $message.="\"enrollid\":" . $_POST['enrollId'] . ",";
        $message.="\"weekzone\":" . $_POST['weekZone'] . ",";
        $message.="\"group\":" . $_POST['group'] . ",";
        $message.="\"starttime\":\"" . $_POST['starttime']. " 00:00:00\",";
        $message.="\"endtime\":\"" . $_POST['endtime']. " 00:00:00\"}]}";


        $deTool = new PDOTool("device");
       // $deTool->field("serial_num as serialNum"); 
       // $list=$deTool->select('');

        $deTool->tableName("machine_command");
        //foreach ($list as $key => $value)
        {
            $machineCommand=array("serial" => $sn //$value["serialNum"]
                                    ,"name" => "setuserlock"
                                    ,"status" => 0
                                    ,"send_status" => 0
                                    ,"err_count" => 0
                                    ,"gmt_crate" => date("Y-m-d H:i:s")
                                    ,"gmt_modified" => date("Y-m-d H:i:s")
                                    ,"content" => $message
                        );

            $deTool->add($machineCommand);
        }

        routeCommand::returnSuccess();
    }
    public static function sendGetUserInfo($id,$bknum,$sn)
    {
        $message="{\"cmd\":\"getuserinfo\",\"enrollid\":".$id.",\"backupnum\":". $bknum."}";

        $deTool = new PDOTool("machine_command");

        $machineCommand=array("serial" => $sn
                                ,"name" => "getuserinfo"
                                ,"status" => 0
                                ,"send_status" => 0
                                ,"err_count" => 0
                                ,"gmt_crate" => date("Y-m-d H:i:s")
                                ,"gmt_modified" => date("Y-m-d H:i:s")
                                ,"content" => $message
                    );

        $deTool->add($machineCommand);

        routeCommand::returnSuccess();
    }
    public static function getDeviceInfo($sn)
    {
        $message="{\"cmd\":\"getdevinfo\"}";

        $deTool = new PDOTool("machine_command");

        $machineCommand=array("serial" => $sn
                                ,"name" => "getdevinfo"
                                ,"status" => 0
                                ,"send_status" => 0
                                ,"err_count" => 0
                                ,"gmt_crate" => date("Y-m-d H:i:s")
                                ,"gmt_modified" => date("Y-m-d H:i:s")
                                ,"content" => $message);

        $deTool->add($machineCommand);

        routeCommand::returnSuccess();
    }

    public static function openDoor($sn,$doorNum)
    {
        $message="{\"cmd\":\"opendoor\"".",\"doornum\":".$doorNum."}";

        $deTool = new PDOTool("machine_command");

        $machineCommand=array("serial" => $sn
                                ,"name" => "opendoor"
                                ,"status" => 0
                                ,"send_status" => 0
                                ,"err_count" => 0
                                ,"gmt_crate" => date("Y-m-d H:i:s")
                                ,"gmt_modified" => date("Y-m-d H:i:s")
                                ,"content" => $message);

        $deTool->add($machineCommand);

        routeCommand::returnSuccess();
    }
    public static function addPerson()
    {
        $id=$_POST['userId'];
        $name=$_POST['name'];
        $roolId=$_POST['privilege'];

        $routeSendCmd =new sendObject(); 
       
        if($_FILES["pic"])
        {
            if ($_FILES["pic"]['error']>0)
            {

            }
            else
            {
                $size=$_FILES["pic"]["size"];
                $file=$_FILES["pic"]["tmp_name"];
                $content = file_get_contents($file);
                $newName= $routeSendCmd->saveImg($content);
                if($newName!='')
                {
                    $base64Str=base64_encode($content);
                    $routeSendCmd->updateEnollInfo($id,50,$newName,$base64Str);
                }
            }
        }

        $routeSendCmd->updatePerson($id,$name,$roolId);

        //pwssword
        $password=$_POST['password'];
        if(!is_null($password) && $password!='')
        {
            $routeSendCmd->updateEnollInfo($id,10,'',$password);
        }

        $cardNum=$_POST['cardNum'];
        if(!is_null($cardNum) && $cardNum!='')
        {
            $routeSendCmd->updateEnollInfo($id,11,'',$cardNum);
        }
        routeCommand::returnSuccess();
    }
    public static function getDevLock($sn)
    {
        $message="{\"cmd\":\"getdevlock\"}";

        $deTool = new PDOTool("machine_command");

        $machineCommand=array("serial" => $sn
                                ,"name" => "getdevlock"
                                ,"status" => 0
                                ,"send_status" => 0
                                ,"err_count" => 0
                                ,"gmt_crate" => date("Y-m-d H:i:s")
                                ,"gmt_modified" => date("Y-m-d H:i:s")
                                ,"content" => $message);

        $deTool->add($machineCommand);

        routeCommand::returnSuccess();
    }
    public static function geUSerLock($sn,$enrollId)
    {
        $message="{\"cmd\":\"getuserlock\",\"enrollid\":".$enrollId."}";

        $deTool = new PDOTool("machine_command");

        $machineCommand=array("serial" => $sn
                                ,"name" => "getuserlock"
                                ,"status" => 0
                                ,"send_status" => 0
                                ,"err_count" => 0
                                ,"gmt_crate" => date("Y-m-d H:i:s")
                                ,"gmt_modified" => date("Y-m-d H:i:s")
                                ,"content" => $message);

        $deTool->add($machineCommand);

        routeCommand::returnSuccess();
    }
    public static function deletePersonFromDevice($sn,$enrollId)
    {
        $backupnum=13;
        $message="{\"cmd\":\"deleteuser\",\"enrollid\":".$enrollId.",\"backupnum\":".$backupnum."}";

        $deTool = new PDOTool("machine_command");

        $machineCommand=array("serial" => $sn
                                ,"name" => "deleteuser"
                                ,"status" => 0
                                ,"send_status" => 0
                                ,"err_count" => 0
                                ,"gmt_crate" => date("Y-m-d H:i:s")
                                ,"gmt_modified" => date("Y-m-d H:i:s")
                                ,"content" => $message);

        $deTool->add($machineCommand);

        $deTool->tableName('person');
        $deTool->delete('where id='.$enrollId);

        $deTool->tableName('enrollinfo');
        $deTool->delete('where enroll_id='.$enrollId);

        routeCommand::returnSuccess();
    }
    public static function cleanAdmin($sn)
    {
        $message="{\"cmd\":\"cleanadmin\"}";

        $deTool = new PDOTool("machine_command");

        $machineCommand=array("serial" => $sn
                                ,"name" => "cleanadmin"
                                ,"status" => 0
                                ,"send_status" => 0
                                ,"err_count" => 0
                                ,"gmt_crate" => date("Y-m-d H:i:s")
                                ,"gmt_modified" => date("Y-m-d H:i:s")
                                ,"content" => $message);

        $deTool->add($machineCommand);

        routeCommand::returnSuccess();
    }

    public static function setOneUser($enrollId,$bknum,$sn)
    {
        $deTool = new PDOTool("person");
        $listUser=$deTool->select('where id='.$enrollId);

        $deTool->tableName("enrollinfo");
        $listRoll=$deTool->select('where enroll_id='.$enrollId.' and backupnum='.$bknum);

        $name=$listUser[0]['name'];
        $admin=0;
        $records='';

        if(count($listRoll)>0)
        {
            $admin=$listRoll[0]['enroll_id'];
            $records=$listRoll[0]['signatures'];
        }
        else if($bknum==-1) {

        }
        else
        {
            routeCommand::returnFail();
            return;
        }

        $message="";
        if($bknum==-1)
        {
            $message="{\"cmd\":\"setusername\",\"count\":"."1".",\"record\":["
                     ."{\"enrollid\":".$enrollId.",\"name\":\"" . $name."\"}]}";
        }
        else if($bknum==11||$bknum==10)
        {
            $message="{\"cmd\":\"setuserinfo\",\"enrollid\":".$enrollId. ",\"name\":\"" . $name 
                ."\",\"backupnum\":" . $bknum . ",\"admin\":" . $admin . ",\"record\":" . $records . "}";
        }
        else
        {
            $message="{\"cmd\":\"setuserinfo\",\"enrollid\":".$enrollId. ",\"name\":\"" . $name 
                ."\",\"backupnum\":" . $bknum . ",\"admin\":" . $admin . ",\"record\":\"" . $records . "\"}";
        }
        
        $deTool->tableName("machine_command");
        $machineCommand=array("serial" => $sn
                                ,"name" => "setusername"
                                ,"status" => 0
                                ,"send_status" => 0
                                ,"err_count" => 0
                                ,"gmt_crate" => date("Y-m-d H:i:s")
                                ,"gmt_modified" => date("Y-m-d H:i:s")
                                ,"content" => $message
                    );

        $deTool->add($machineCommand);

        routeCommand::returnSuccess();
    }
    public static function enrollInfo()
    {
        $deTool = new PDOTool("person");
        $deTool->field('id,name,roll_id as rollId');
        $list=$deTool->select('');

        $extend = array('enrollInfo' => $list);

        routeCommand::returnSuccessContent("extend",$extend);
    }
}
?>