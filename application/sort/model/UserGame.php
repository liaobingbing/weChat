<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/26
 * Time: 14:12
 */

namespace app\sort\model;


use think\Model;

class UserGame extends Model
{
	/**
     * 由 user_id 获取用户游戏数据
     * @param $user_id
     * @param null $field
     * @return mixed
     */
    public function find_by_user_id($user_id,$field=null){

        if($field){
            $user = db('UserGame')->field($field)->where(array('uid'=>$user_id))->find();

        }else{
            $user = db('user_game')->where(array('uid'=>$user_id))->find();

        }

        return $user;
    }

    /**
     * 验证挑战机会
     * @param $user_id
     * @return bool
     */
    public function check_chance_num($user_id){
        $chance_info = db('UserGame')->where(array('uid'=>$user_id))->field('chance_num')->find();
        $chance_num = $chance_info['chance_num'];
        if($chance_num > 0){
            $resutl = true;
        }else{
            $resutl = false;
        }

        return $resutl;
    }

    /**
     * 获取排名
     * @param $field '字段'
     * @param $len    '长度'
     * @param int $expire '时间'
     * @return mixed
     */
    public function get_rankings($field,$len,$expire=300){
        $rankings = db('UserGame')->field("{$field},avatar_url,nickname")->order("{$field} desc")->limit($len)->select();
        foreach ($rankings as $k => $v){
            $rankings[$k]['ranking'] = $k + 1;
        }

        return $rankings;
    }

    /**
     * 签到操作
     * @param $user_id
     */
    public function do_sign($user_id){
        $UserGame = db('UserGame');
        $sign_time_info = $UserGame->where("uid=$user_id")->field('sign_time')->find();
        $sign_time = $sign_time_info['sign_time'];
        $today_0 = strtotime(date('Y-m-d',time()));

        if($sign_time < $today_0){
            $user_game['chance_num'] =5;
            $user_game['sign_time']  = time();
            $UserGame->where("uid=$user_id")->update($user_game);
        }
    }
}