<?php
namespace FingerPrint;
use PDOTool;

class routeEmps
{
    public static function getData($curPage)
    {
        if($curPage<1)
            $curPage=1;

        $deTool = new PDOTool("person");
        $deTool->field('COUNT(*) as count');
        $list=$deTool->select('');
        $count=$list[0]['count'];
        $perpages=8;
        $pages=intval(($count+$perpages-1)/$perpages);
        $start=($curPage-1)*$perpages;

        $deTool = new PDOTool("person LEFT JOIN enrollinfo ON person.id= enrollinfo.enroll_id and backupnum=50");
        $deTool->field("person.id as enrollId,name,backupnum as num,roll_id as admin,enrollinfo.imagepath AS imagePath");
        $deTool->limit("".$start.','.$perpages);
        $deTool->order('person.id asc');
        
        $list=$deTool->select('');
      
        //$list1 = array('enrollId' => '123', 'name' => 'name1', 'imagePath' => 'test123.jpg', 'id' => 'id');
        //$list=array($list1,$list1);

        $navigatepageNums[0]=1;
        for($i=1;$i<$pages;$i++)
            $navigatepageNums[$i]=$i+1;
        
        $pageInfo = array('list' => $list,'pages' => $pages,'total' => $count,'pageNum' => $curPage,'startRow' =>$start,
                'hasPreviousPage' => $curPage>1,'hasNextPage' => $curPage< $pages,'navigatepageNums' => $navigatepageNums);
        
        
        $page = array('pageInfo' => $pageInfo);
        
        $result = array('code' => 100,'msg' => 'Success！','extend' => $page);
        echo json_encode($result);
    }
}
//routeEmps::getData(1);
?>

