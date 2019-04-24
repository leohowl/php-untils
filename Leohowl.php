<?php
/**
 * Created by PhpStorm.
 * User: HANSN-CZ-P01
 * Date: 2019-04-24
 * Time: 9:29 AM
 */

namespace leohowl\utils;


class Leohowl
{
  public static function createRandomString($len){
    $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
    $string=time();
    for(;$len>=1;$len--){
      $position=rand()%strlen($chars);
      $position2=rand()%strlen($string);
      $string=substr_replace($string,substr($chars,$position,1),$position2,0);
    }
    return $string;
  }


  public static function formatCategory($format_category, $root_uuid = '0')
  {
    //提取有效内容
    $category = [];

    //上个循环新增的数量
    $first_loop = true;
    $new_num = 1;
    while($new_num !== 0){
      $new_num = 0;
      if($first_loop){
        //处理首次循环
        foreach ($format_category as $key1 => $single_remain_category){
          if($single_remain_category['father'] === $root_uuid){
            $category[] = $single_remain_category;
            unset($format_category[$key1]);
            $new_num++;
          }
        }
        $first_loop = false;
      } else {
        foreach ($format_category as $key1 => $single_remain_category){
          foreach ($category as $key => $single_useful_category){
            if(isset($single_useful_category['uuid'])){
              if($single_remain_category['father'] === $single_useful_category['uuid']){
                $category[] = $single_remain_category;
                unset($format_category[$key1]);
                $new_num++;
              }
            }
          }
        }
      }
    }
    return $category;
  }

  /**
   * @param $cate
   * @return array|bool
   * 层级格式化
   */
  public static function orderCategory($cate, $root_uuid = '0'){
    //取所有分类
    if (!$cate || empty($cate)) {
      return false;
    }



    $cate = self::formatCategory($cate, $root_uuid);

    /**
     * 将分类按级别整理成一个数组
     * 同一层级的在一个数组中
     * 每处理完一条数据之后，将其删除
     */
    //记录转存表
    $record_dump = [];
    //层级记录表，二维数组,注意数组初始化,表示根层级
    //注意数据库中取出的父级值数据类型为字符串，0初始化的时候特别注意应是字符串类型
    $level_recorder = [[$root_uuid]];

    $i = 0;
    //按层级进行整理
    while(count($cate) > 0){
      foreach ($cate as $key => $value){
        //如果一条记录的父级，在当前进行整理的层级记录表中可以找到，则可以确定这条记录属于那一级
        if(isset($level_recorder[$i])){
          if(in_array($value['father'], $level_recorder[$i])){
            //将记录写入记录转存表
            $record_dump[$i][$value['uuid']] = $value;
            //更新层级记录表，记录当前的层级
            $level_recorder[$i+1][] = $value['uuid'];
            //删除当前的数据
            unset($cate[$key]);
          }
        }
      }
      $i ++;
    }

    //将数组书顺序翻转,低层次的记录排在前面
    $record_dump = array_reverse($record_dump);
    //排序后的数组
    $sorted_record = [];
    //子节点记录
    $child_record = [];

    //以层级为基础进行处理
    foreach ($record_dump as $key => $value){
      //如果当前的子节点非空，则将改子节点赋值给相应的父节点
      if(!empty($child_record)){
        foreach ($child_record as $key1 => $value1){
          $value[$key1]['children'] = $value1;
        }
        //重置子节点
        $child_record = [];
      }

      //层级遍历
      foreach ($value as $key1 => $value1){
        //如果当前层级的子记录还包含有父级,则将当前记录赋值给父级的子节点
        if($value1['father'] !== $root_uuid){
          $child_record[$value1['father']][] = $value1;
        }
      }
      //将当前
      $sorted_record = $value;

    }
    return $sorted_record;

  }

  public static function categoryBeauty($arr,$level=0){
    $level++;
    static $data;//定义一个静态变量用来存储最终结果
    if(!$arr){
      return $arr;
    }
    foreach ($arr as $val) {
      $temp = $val;
      $temp['name'] = str_repeat('　',$level-1).'└'.$temp['name'];
//            $temp['name'] = str_repeat('...',$level-1).'└'.$temp['name'];
      if (isset($val['children'])) {
        unset($temp['children']);
        $data[] = $temp;
        self::categoryBeauty($val['children'],$level);//递归调用
      }
      else{
        $data[] = $temp;
      }
    }
    return $data;
  }

  public static function format_date($time){
    $t = time()-$time;
    $f = [
      '31536000'=>'年',
      '2592000'=>'个月',
      '604800'=>'星期',
      '86400'=>'天',
      '3600'=>'小时',
      '60'=>'分钟',
      '1'=>'秒'
    ];

    foreach ($f as $k=>$v) {
      if (0 != $c = floor($t/(int)$k)) {
        return $c . $v . '前';
      }
    }
  }


  /**
   * @param $num
   * @return int
   *
   * 判断是不是正整数，不是时返回1
   */
  public static function positiveIntegerFilter($num)
  {
    if(preg_match("/^[1-9][0-9]*$/", $num)){
      return $num;
    } else {
      return 1;
    }
  }

  /**
   * 获取ip地址所在地
   */
  public static function GetIpLookup($ip = ''){

    $res = @file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=' . $ip);

    var_dump($res);
    if(empty($res)){
      return false;
    }

    $jsonMatches = array();

    preg_match('#\{.+?\}#', $res, $jsonMatches);

    if(!isset($jsonMatches[0])){
      return false;
    }

    $json = json_decode($jsonMatches[0], true);

    if(isset($json['ret']) && $json['ret'] == 1){
      $json['ip'] = $ip;
      unset($json['ret']);
    }else{
      return false;
    }
    return $json;
  }
}