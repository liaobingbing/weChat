<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/19
 * Time: 9:17
 */

namespace FindColor\Controller;


use Common\Controller\ApiBaseController;
use Common\Controller\ApiLoginController;
use FindColor\Model\QuestionsModel;
use FindColor\Model\ShareGroupModel;
use FindColor\Model\UserGameModel;
use FindColor\Model\UsersModel;

class ApiController extends ApiBaseController
{
    // 初始化
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        //$this->check_status();  // 验证用户状态 1:正常 0:禁用
        $this->check_sign();    // 验证用户签到状态
    }

    // 总挑战次数
    public function challenge_num(){
        $result = array('code' => 400, 'msg' => '获取失败', 'data' => 0);
        $count_challenge = M('UserGame')->sum('challenge_num');

        if($count_challenge <= 5000){
            $count_challenge += 5000;
        }

        if($count_challenge){
            $result['code'] =   200;
            $result['msg']  =   '获取成功';
            $result['data'] = array('count_challenge' => $count_challenge);
        }

        $this->ajaxReturn($result);
    }

    // 验证挑战次数接口
    public function check_chance_num(){
        $user_id = session('user_id');
        $UserGame = new UserGameModel();

        $result = array('code'=>400,'msg'=>'无挑战次数');
        $re = $UserGame->check_chance_num($user_id);

        if($re){
            $result = array('code'=>200,'msg'=>'有挑战次数');
        }

        $this->ajaxReturn($result);
    }

    // 获取题目
    public function get_question(){
        $user_id = session('user_id');
        $layer = I('layer',1);

        if($layer == 1){
            $UserGame = new UserGameModel();
            $user_game = $UserGame->find_by_user_id($user_id);

            if($user_game['chance_num'] <= 0){
                $this->ajaxReturn(array('code' => 400, 'msg' => '挑战次数为空'));
            }

            session('questions',null);
            $Questions = new QuestionsModel();
            $questions = $Questions->get_rand_questions();

            session('questions',$questions);

            M('UserGame')->where(array('uid' => $user_id))->setDec('chance_num');
            M('UserGame')->where(array('uid' => $user_id))->setInc('challenge_num');
        }

        $questions = session('questions');

        $option = array_shift($questions);
        session('questions',$questions);

        if( !$option ){
            $this->ajaxReturn(array('code' => 400, 'msg' => '获取失败'));
        }


        if( $layer <= 2 ){
            $i = 4;
            $j = 1;
        }else if( $layer <= 5 ){
            $i = 9;
            $j = 0.65;
        }else if( $layer <= 9 ){
            $i = 16;
            $j = 0.48;
        }else if( $layer <= 14 ){
            $i = 25;
            $j = 0.38;
        }else if( $layer <= 20 ){
            $i = 36;
            $j = 0.32;
        }else if( $layer <= 27 ){
            $i = 49;
            $j = 0.28;
        }else if( $layer <= 35 ){
            $i = 64;
            $j = 0.23;
        }else if( $layer <= 45 ){
            $i = 81;
            $j = 0.21;
        }else{
            $this->ajaxReturn(array('code' => 400, 'msg' => 'layer不能超过44'));
        }

        $arr = array();

        if($option['answer'] != 'option_1'){
            for($a = 0; $a < $i - 1; $a++){
                $arr[$a]['text']    = $option['option_1'];
                $arr[$a]['percent'] = $j;
            }
            if( $layer == 44 ){
                $arr[$i - 1]['text'] = $option['option_1'];
                $arr[$i - 1]['percent'] = $j;
            }else{
                $arr[$i - 1]['text'] = $option['option_2'];
                $arr[$i - 1]['percent'] = $j;
            }

            $answer = $option['option_2'];
        }else{
            for($a = 0; $a < $i - 1; $a++){
                $arr[$a]['text']    = $option['option_2'];
                $arr[$a]['percent'] = $j;
            }
            if( $layer == 44 ){
                $arr[$i - 1]['text'] = $option['option_2'];
                $arr[$i - 1]['percent'] = $j;
            }else{
                $arr[$i - 1]['text'] = $option['option_1'];
                $arr[$i - 1]['percent'] = $j;
            }
            $answer = $option['option_1'];
        }

        shuffle($arr);

        $result['code'] = 200;
        $result['msg']  = '获取成功';
        $result['data']['words'] = $arr;
        $result['data']['answer'] = $answer;
        $result['data']['next_layer'] = $layer+1;


        $this->ajaxReturn($result);
    }

    // 验证用户状态
    public function check_status(){
        $user_id = session('user_id');

        $User = new UsersModel();
        $user = $User->find_by_user_id($user_id,'id,status');

        if($user['status'] != 1){
            session('user_status',1);
            $result['code'] = 403;
            $result['msg']  = '该用户已禁用';
            $this->ajaxReturn($result);
        }
    }

    // 验证签到状态
    public function check_sign(){
        $user_id = session('user_id');
        $openid = session('openid');
        $key = 'find_color_sign_status_'.$openid;
        $sign = S($key);

        if( !$sign ){
            $UserGame = new UserGameModel();
            $UserGame->do_sign($user_id);

            $today_0 = strtotime(date('Y-m-d',time()));
            $expire = $today_0 + 24*60*60 - time();

            S($key,1,$expire);
        }
    }

    // 群分享操作
    public function share_group(){
        $user_id = session('user_id');
        $session_key = session('session_key');

        $encryptedData = I("post.encryptedData");
        $iv = I("post.iv");
        $share_type=I('post.share_type',1);  // 分享类型

        $result = array( 'code' => 400 , 'msg' => '分享失败');

        if($encryptedData && $iv){
            // 对微信数据解密
            $ApiLogin = new ApiLoginController();
            $data = $ApiLogin->wx_biz_data_crypt($encryptedData,$iv,$session_key);

            if($data['errCode'] == 0){
                // 验证今天是否已分享
                $data = json_decode($data['data'],true);
                $ShareGroup = new ShareGroupModel();
                $re = $ShareGroup->check_share_group($user_id,$data

                ['openGId']);

                if($re){
                    if($share_type == 1){
                        $UserGame = M('UserGame');
                        if($UserGame->where("uid=$user_id")->setInc('chance_num')){
                            $result['code'] = 200;
                            $result['msg']  = '分享成功';
                        }
                    }else{
                        $result['code'] = 200;
                        $result['msg']  = '分享成功';
                    }
                }else{
                    $result['code'] = 400;
                    $result['msg']  = '该群今天已分享过';
                }

            }else{
                $result['code']=402;
                $result['msg']='session_key过期，需重新登录获取';
            }

        }else{
            $result['code'] = 400;
            $result['msg'] = '参数不全';
        }

        $this->ajaxReturn($result);
    }

    // 毅力榜
    public function challenge_top(){
        $UserGame = new UserGameModel();
        $rankings = $UserGame->get_rankings('challenge_num',8);
        $result = array('code'=>400, 'msg'=>'获取失败');

        if($rankings){
            $result['code'] = 200;
            $result['msg']  = '获取成功';
            $result['data'] = array('ranking_list' => $rankings);
        }

        $this->ajaxReturn($result);
    }

    // 荣耀榜
    public function get_prize_top(){
        $UserGame = new UserGameModel();
        $rankings = $UserGame->get_rankings('get_prize',8);
        $result = array('code'=>400, 'msg'=>'获取失败');

        if($rankings){
            $result['code'] = 200;
            $result['msg']  = '获取成功';
            $result['data'] = array('ranking_list' => $rankings);
        }

        $this->ajaxReturn($result);
    }

    // 奖品列表
    public function prize_list(){
        $page = I('page',1);
        $len  = I('len',10);

        $prize_list = M('Prize')->page($page,$len)->select();
        $result = array('code'=>200,'msg'=>'获取成功');
        $result['data'] = $prize_list;

        $this->ajaxReturn($result);
    }

    // 将用户禁用
    public function disable(){
        $user_id = session('user_id');
        $result = array('code'=>400, 'msg'=> '禁用失败');
        $re = M('Users')->where("id={$user_id}")->setField('status',0);

        if($re){
            session('user_status',1);
            $result['code'] = 403;
            $result['msg']  = '已禁用';
            $result['data'] = array('user_id'=>$user_id);
        }

        $this->ajaxReturn($result);
    }

    // 获取用户信息
    public function get_user_info(){
        $user_id = session('user_id');
        $UserGame = new UserGameModel();

        $result = array('code'=>400, 'msg'=> '获取失败');

        $user = $UserGame->find_by_user_id($user_id,'challenge_num,chance_num,get_prize,uid,nickname,avatar_url');

        if($user) {
            $result['code'] = 200;
            $result['msg'] = '获取成功';
            $result['data'] = $user;
        }

        $this->ajaxReturn($result);
    }

    //获取用户ID
    public function get_user_id(){
        $user_id=session('user_id');
        if($user_id){
            $data['code']='200';
            $data['msg']='成功';
            $data['user_id']=$user_id;
        }else{
            $data['code']=401;
        }
        $this->ajaxReturn($data,'JSON');
    }

}