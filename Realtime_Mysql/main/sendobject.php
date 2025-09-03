<?php
namespace FingerPrint;
use DateTime;
use PDOTool;

class sendObject
{
    public $startData;
    public $imgID;
    public $bUpdated;
    protected $deTool;

    public function __construct()
    {
        $this->deTool = new PDOTool("machine_command");
        $this->deTool->limit("0,2");

        $this->startData = 'upd '.date('md_His_');
        $this->imgID=1;
        $this->bUpdated=false;
    }
    //检查需要发送的数据
    public function checkCommand($machines,$socket,$id)
    {
        foreach ($machines as $ready) {
            if($ready!=$socket)
            {
                socket_getpeername($ready, $address, $port);
                if(isset($id["id{$address}_{$port}"])) //已经 收到 reg 的
                {
                    $sn=$id["id{$address}_{$port}"];

                    $this->deTool->tableName('machine_command');
                    $this->deTool->order("gmt_crate asc,id asc");
                    $list=$this->deTool->select("where status=0 and send_status=0 and serial=\"".$sn.'"',false); // 0,0 新消息
                    $listPending=$this->deTool->select("where status=0 and send_status=1 and err_count<3 and serial=\"".$sn.'"',false); //0,1 待确认

                    $content=null;
                    $dataSql=null;
                    $where='';
                    if(count($list)>0)
                    {
                        $content=$list[0]['content'];
                        $dataSql=array("status" => 0
                                ,"send_status" => "1"
                                ,"run_time" => date("Y-m-d H:i:s"));
                        $where="where id=".$list[0]['id'];
                    }
                    if(count($listPending)>0) //重发之前的
                    {
                        // 20 秒重发一次，最多重发三次
                        $date1 = new DateTime();
                        $date2 = new DateTime($listPending[0]['run_time']);
                        // 计算时间差
                        $interval = $date1->diff($date2);
                        $seconds = $interval->format('%s'); // 秒数
                        if( $interval->days||$interval->h || $interval->s>20)
                        {
                            $errTimes=$listPending[0]['err_count'];
                            $content =$listPending[0]['content'];
                            $dataSql=array(
                                "run_time" => date("Y-m-d H:i:s")
                                ,"err_count" => $errTimes+1);
                            $where="where id=".$listPending[0]['id'];
                        
                        }
                        else 
                        {
                            $content=null;
                        }
                    }

                    if($content)
                    {
                        echo "</br></br>sendto[".$sn.']:'.$content;
                        //echo "</br>".$this->deTool->_sql();
                        ob_flush();
                        
                        $this->sendCmdTo($ready,$content);
                        $this->deTool->save($dataSql,$where);
                        
                    }
                }
            }
        }
    }
    public function updateCommandStatus($serial,$commandType)
    {
        $this->deTool->tableName('machine_command');
        $this->deTool->order("gmt_crate asc,id asc");
        $listPending=$this->deTool->select("where status=0 and send_status=1 and err_count<3 and serial=\"".$serial.'"',false); //0,1 待确认
        if(count($listPending)>0 && $listPending[0]['name']===$commandType)
        {
            $dataSql=array("status" => 1
                ,"send_status" => 0
                ,"run_time" => date("Y-m-d H:i:s"));
                $this->deTool->save($dataSql,"where id=".$listPending[0]['id']); //0,1 待确认
            
            $this->bUpdated=true;
            ob_flush();
        }
    }
    //发送消息
    public function sendCmdTo($socket,$retTxt)
    {
        $size = strlen($retTxt);
        if($size>0)
        {
            $code = 129;
            $bufferCommand = ($size<126?pack('CC', $code, $size):($size<65536?pack('CCn', $code, 126, $size):pack('CCNN', $code, 127,0, $size))).$retTxt;'{"ret":"sendlog", "result":true, "cloudtime":"'.date('Y-m-d H:i:s').'"}';
            $sendCount=0;
            socket_write($socket, $bufferCommand);
             
            //echo "</br>【sendcmd】".$bufferCommand." cont=".$sendCount."</br>";
            //ob_flush();
            return $sendCount;
        }
        return 0;
    }
    function saveImg($img)
    {
        $fileName="commands/".$this->startData.$this->imgID.".jpg";
        $this->imgID++;
        $file = file_put_contents($fileName,$img);
        return $fileName;
    }

    //注册 连接设备信息
    public function onReg($sn)
    {
        self::updateDevice($sn,1);
    }
    //获得打卡记录，包括机器号
    public function onSendLog($packet)
    {
        $sn=$packet['sn'];
        $count=$packet['count'];
        $logindex=-1;

		if(isset($packet['count'])) {
			$logindex=$packet['count'];
		}
        $ret='';

        if($count>0) {
            if ($logindex>=0) 
            {
				$ret="{\"ret\":\"sendlog\",\"result\":true".",\"count\":".$count
                        .",\"logindex\":".$logindex.",\"cloudtime\":\"" . date("Y-m-d H:i:s") . "\"}";

                //更新 records
                $records =$packet['record'];
                foreach($records as $record)
                {
                    $dataSql=[];
                    if(isset($record['image']))
                    {
                        $img=$record['image'];
                        $img=base64_decode($img,false);
                        if($img!=null)
                        {
                           $imgName=self::saveImg($img);
                           echo "</br>save file ".$imgName;
                           $dataSql['image']=$imgName;
                        }
                    }
                    $dataSql['device_serial_num']=$sn;
                    $dataSql['enroll_id']=$record['enrollid'];
                    $dataSql['records_time']=$record['time'];

                    $dataSql['intOut']=$record['inout'];
                    $dataSql['mode']=$record['mode'];
                    $dataSql['event']=$record['event'];

                    $temperature=0;
                    if(isset($record["temp"])) {
                        $temperature=$record["temp"];
                        //$temperature=$temperature/10;
                        //$temperature=(double) Math.round(temperature * 10) / 10;

                        //System.out.println("温度值"+temperature);
                        //obj.put("temperature", String.valueOf(temperature));
                    }
                    $dataSql['temperature']=$temperature;
                    self::addRecords($dataSql);
                }
			}else if($logindex<0)
            {
				$ret="{\"ret\":\"sendlog\",\"result\":true".",\"cloudtime\":\"".date("Y-m-d H:i:s") . "\"}";
            }
            //更新设备状态 1
        }
        else if($count==0)
        {
            $ret="{\"ret\":\"\"sendlog\"\",\"result\":false,\"reason\":1}";
        }
        return $ret;
    }
    public function onSendUser($packet)
    {
        $sn=$packet['sn'];
        $retTxt='';

        $signatures=$packet['record'];
        if($signatures!=null)
        {	
            $retTxt = '{"ret":"senduser", "result":true, "cloudtime":"'.date('Y-m-d H:i:s').'"}';

            $enrollId=$packet['enrollid'];
            $rollId=$packet['admin'];
            $name=$packet['name'];
            $backupnum=$packet['backupnum'];
            
            //更新 person;
            $this->updatePerson($enrollId,$name,$rollId);

            $imgName=null;
            if ($backupnum==50) 
            {
                $img=base64_decode($signatures,false);
                if($img!=null)
                {
                   $imgName= self::saveImg($img);
                   echo "</br>save file ".$imgName;
                   $dataSql['imagepath']=$imgName;
                }
            }
            //更新 enrollInfo
            $this->updateEnollInfo($enrollId,$backupnum,$imgName,$signatures);

            //更新设备状态 1
            $this->updateDevice($sn,1);
        }
        else
            $retTxt="{\"ret\":\"senduser\",\"result\":false,\"reason\":1}";
		
        return $retTxt;
    }
    
    public function retgetAllLog($packet)
    {
        $sn=$packet['sn'];
        $result=$packet['result'];
        if( $result)
        {
            $count=$packet['count'];
            if($count)
            {
                //更新 records
                $records =$packet['record'];
                foreach($records as $record)
                {
                    $dataSql=[];
                    
                    $dataSql['device_serial_num']=$sn;
                    $dataSql['enroll_id']=$record['enrollid'];
                    $dataSql['records_time']=$record['time'];

                    $dataSql['intOut']=$record['inout'];
                    $dataSql['mode']=$record['mode'];
                    $dataSql['event']=$record['event'];

                    $temperature=0;
                    if(isset($record["temp"])) {
                        $temperature=$record["temp"];
                        //$temperature=$temperature/10;
                        //$temperature=(double) Math.round(temperature * 10) / 10;

                        //System.out.println("温度值"+temperature);
                        //obj.put("temperature", String.valueOf(temperature));
                    }
                    $dataSql['temperature']=$temperature;
                    $this->addRecords($dataSql);
                }
            }
        }
        //更新设备状态
        $this->updateDevice($sn,1);
    }
    public function retgetNewLog($packet)
    {
        $this->retgetAllLog($packet); //好像是一样的。
    }
    public function retgetUserList($packet)
    {
        $sn=$packet['sn'];

        $result=$packet['result'];
        if( $result)
        {
            $count=$packet['count'];
            if($count)
            {
                //添加 person
                $records =$packet['record'];
                foreach($records as $record)
                {
                    $enrollId=$record['enrollid'];
                    $rollId=$record['admin'];
                    $name='';
                    $this->updatePerson($enrollId,$name,$rollId);
                }

                //添加：enrollInfo
                $records =$packet['record'];
                foreach($records as $record)
                {
                    $enrollId=$record['enrollid'];
                    $backupnum=$record['backupnum'];
                    
                    $this->updateEnollInfo($enrollId,$backupnum,null,null);
                    
                }
            }
             //更新 machine_command "getuserlist"
            self::updateCommandStatus($sn,'getuserlist');
        }
        //更新设备状态
        $this->updateDevice($sn,1);
    }
    public function retgetUserInfo($packet)
    {
        global $uerIndex;
        $sn=$packet['sn'];

        $bknum=$packet['backupnum'];
        $result=$packet['result'];
        if( $result)
        {
            //添加、更新 person
            $enrollId=$packet["enrollid"];
     		$name=$packet["name"];
     		$admin=$packet["admin"];
            $this->updatePerson($enrollId,$name,$admin);

            //添加、更新：enrollInfo
            $imgName=null;
            $signatures=$packet['record'];
            if($bknum==50)
            {
                if($signatures!=null)
                {	
                    $img=base64_decode($signatures,false);
                    if($img!=null)
                    {
                        $imgName=self::saveImg($img);
                        echo "</br>save file ".$imgName;
                        $uerIndex=sendGetUserInfo($uerIndex);
                    }
                }
            }
            $this->updateEnollInfo($enrollId,$bknum,$imgName,$signatures);
            $uerIndex=sendGetUserInfo($uerIndex);

            //更新command状态
            self::updateCommandStatus($sn,'getuserinfo');
        }
    }
     //更新 person
    public function updatePerson($id,$name,$rollId)
    {
        $this->deTool->tableName('person');
        $list= $this->deTool->select('where id='.$id);
        
        if(count($list)==0)
        {
            $dataSql=array(
                'roll_id' => $rollId
                ,"name" => $name
                ,"id" =>$id);
            $this->deTool->add($dataSql);
        }
        else
        {
            $dataSql=array(
                'roll_id' => $rollId
                ,"name" => $name);
            $this->deTool->save($dataSql,'where id='.$id);
        }
    }

     //更新 enrollInfo
    public function updateEnollInfo($enrollId,$backNum,$imgName,$signatures)
    {
 
        $this->deTool->tableName('enrollinfo');
        $dataSql=[];
        
        $dataSql['backupnum']=$backNum;
        if($signatures)
            $dataSql['signatures']=$signatures;
        if($imgName)
            $dataSql['imagepath']=$imgName;

        $list=$this->deTool->select('where enroll_id='.$enrollId.' and backupnum="'.$backNum.'"');
        if(count($list)>0)
        {
            $this->deTool->save($dataSql,'where id='. $list[0]['id']);
        }
        else
        {
            $dataSql['enroll_id']=$enrollId;
            $this->deTool->add($dataSql);
        }
    }

    
    public function updateDevice($serial,$status)
    {
        $this->deTool->tableName("device");
        $list=$this->deTool->select('where serial_num="'.$serial.'"');
        if(count($list)>0)
        {
            $dataSql=array(
                "status" => $status);
            $this->deTool->save($dataSql,'where id="'.$list[0]['id'].'"');
        }
        else
        {
            $dataSql=array(
                "serial_num" => $serial
                ,"status" => $status);
           $this->deTool->add($dataSql);
        }
    }
    public function addRecords($dataSql)
    {
        $this->deTool->tableName("records");
        $this->deTool->add($dataSql);
    }
}

?>