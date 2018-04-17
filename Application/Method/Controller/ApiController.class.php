<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/4/14
 * Time: 14:12
 */

namespace Method\Controller;


use Common\Controller\ApiBaseController;

class ApiController extends ApiBaseController
{
    private  $key="kuaiyu666666";
    //统计挑战的次数
    public function count_challenge()
    {
        $count_challenge=M('user_game')->sum('challenge_num');
        if($count_challenge<=5000){
            $count_challenge=$count_challenge+5000;
        }
        $data['code']=200;
        $data['msg']='success';
        $data['data']=$count_challenge;
       $this->ajaxReturn($data);
    }
    //智力榜
    public function intelligence_top()
    {
        //SELECT avatarUrl,gt_number as number,nickname FROM method_test_game WHERE id >= ((SELECT MAX(id) FROM method_test_game)-(SELECT MIN(id) FROM method_test_game)) * RAND() + (SELECT MIN(id) FROM method_test_game)  order by  number desc LIMIT 5;
        $sql1="SELECT avatarUrl,gt_number,nickname FROM method_test_game order by gt_number desc limit 3";
        $data1=M()->query($sql1);
         $sql2="SELECT avatarUrl,gt_number,nickname FROM method_test_game WHERE id >= ((SELECT MAX(id) FROM method_test_game)-(SELECT MIN(id) FROM method_test_game)) * RAND() + (SELECT MIN(id) FROM method_test_game)  order by  gt_number desc LIMIT 8";
        $data2=M()->query($sql2);
        $user_info=$data1+$data2;
         // $user_info=M('user_game')->field('get_number,avatar_url,nickname')->order('get_number desc')->limit(5)->select();
        $arr=array('code'=>200,'msg'=>'sucess','data'=>$user_info);
        $this->ajaxReturn($arr);
    }
    //毅力榜
    public function num_top()
    {
        $user_info = S('num_top');
        if(!$user_info){
            $user_info=M('user_game')->field('challenge_num,avatar_url,nickname')->order('challenge_num desc')->limit(200)->select();
        }
        $page=I('post.page');
        $page_size=10;
        //$count=count($user_info);
        $start=($page-1)*$page_size;
       // $total = ceil($count/$page_size);
        $data=array_slice($user_info,$start,$page_size);
        $arr=array('code'=>200,'msg'=>'success','data'=>$data);
        $this->ajaxReturn($arr);
    }
    //缓存挑战次数
    public function cache_num()
    {
        $key=I('get.key');
        if($key==$this->key){
            $user_info=M('user_game')->field('challenge_num,avatar_url,nickname')->order('challenge_num desc')->select();
            S("num_top",$user_info);
        }
    }
    //娃娃奖品图片列表
    public function prize_list()
    {
        $prize_info=M('prize')->select();
         $arr=array('code'=>200,'msg'=>'sucess','data'=>$prize_info);
        $this->ajaxReturn($arr);
    }
    //截屏监听事件，把用户置为无效
    public function user_status()
    {
        $status['status']=0;
        $uid=I('post.user_id');
        M('users')->where('id=%d',$uid)->save( $status);
        session(null);
        $arr=array('code'=>403,'msg'=>'已经被拉黑','data'=>"");
        $this->ajaxReturn($arr);
    }

    //检查机会次数
    public function check_chance_num(){
        $user_id=session('user_id');
        if($user_id){
            $user_game=M('user_game')->find($user_id);
            if($user_game){
                if($user_game['chance_num']>0){
                    $data['code']=200;
                    $data['msg']='有挑战次数';
                }else{
                    $data['code']=400;
                    $data['msg']='无挑战次数';
                }
            }else{
                $data['code']=401;
            }
        }else{
            $data['code']=401;
        }
        $this->ajaxReturn($data,'JSON');
    }

    //开始挑战
    public function begin_challenge(){
        $user_id=session('user_id');
        if($user_id){
            $user_game=M('user_game')->find($user_id);
            if($user_game){
                if($user_game['chance_num']>0){
                    $user_game['chance_num']-=1;
                    $user_game['challenge_num']+=1;
                    $info=M('user_game')->save($user_game);
                    if($info){
                        $data['code']=200;
                        $data['msg']='开始成功';
                    }else{
                        $data['code']=400;
                        $data['msg']='开始失败';
                    }
                }else{
                    $data['code']=400;
                    $data['msg']='开始失败';
                }
            }else{
                $data['code']=401;
            }
        }else{
            $data['code']=401;
        }
        $this->ajaxReturn($data,'JSON');
    }


    //获取题目
    public function get_question(){
        $user_id=session('user_id');
        if($user_id){
            $user_game=M('user_game')->find($user_id);

            if($user_game){

                $layer=I('post.layer',1);
                if($layer<=30){
                    $sql='SELECT * FROM method_answer WHERE status=1 ORDER BY  RAND() LIMIT 1';
                    $question=M()->query($sql);
                    if($question){
                        $data['code']=200;
                        $data['msg']='获取成功';
                        $data['data']['subject1']=$question[0]['subject1'];
                        $data['data']['subject2']=$question[0]['subject2'];
                        if($layer>18){
                            $odds=($layer-18)*100;
                        }else{
                            $odds=0;
                        }
                        $rand=rand(0,1000);
                        if($rand>$odds){
                            $data['data']['answer']=$question[0]['answer'];
                        }else{
                            $data['data']['answer']=2;
                        }
                    }else{
                        $data['code']=400;
                        $data['msg']='题库出错';
                    }

                }else{
                    $data['code']=400;
                    $data['msg']='没有此等级';
                }

            }else{
                $data['code']=401;
            }
        }else{
            $data['code']=401;
        }


        $this->ajaxReturn($data,'JSON');
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


    //分享群
    public function share_group(){
        $user_id=session('user_id');
        if($user_id){
            $encryptedData = I("post.encryptedData");
            $iv = I("post.iv");
            if($encryptedData&&$iv){
                $session_key=session('session_key');
                if($session_key){
                    $user_game=M('user_game')->find($user_id);
                    if($user_game){
                        vendor("wxaes.WXBizDataCrypt");
                        $wxBizDataCrypt = new \WXBizDataCrypt(C("WECHAT_APPID"), $session_key);
                        $data_arr = array();
                        $errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $data_arr);
                        if($errCode==0){
                            $json_data = json_decode($data_arr, true);
                            $has=M('share_group')->where('uid='.$user_id.' and open_gid like "'.$json_data['openGId'].'"')->find();
                            if($has){
                                if($has['share_time']<strtotime(date("Y-m-d"),time())){
                                    $user_game['chance_num']+=1;
                                    M('user_game')->save($user_game);
                                    $has['share_time']=time();
                                    M('share_group')->save($has);
                                    $data['code']=200;
                                    $data['msg']='分享成功';
                                }else{
                                    $data['code']=400;
                                    $data['msg']='该群已分享过';
                                }
                            }else{
                                $user_game['chance_num']+=1;
                                M('user_game')->save($user_game);
                                $group['uid']=$user_id;
                                $group['open_gid']=$json_data['openGId'];
                                $group['share_time']=time();
                                M('share_group')->add($group);
                                $data['code']=200;
                                $data['msg']='分享成功';
                            }
                        }else{
                            $data['code']=402;
                            $data['msg']='session_key过期，需重新登录获取';
                        }
                    }else{
                        $data['code']=401;
                    }

                }else{
                    $data['code']=401;
                }
            }else{
                $data['code']=400;
                $data['msg']='参数不全';
            }
        }else{
            $data['code']=401;
        }
        $this->ajaxReturn($data,'JSON');
    }

    //我的奖品
    public function my_prize(){
        $user_id=session('user_id');
        if($user_id){
            $user_game=M('user_game')->find($user_id);
            if($user_game){
                $data['code']=200;
                $data['msg']='获取成功';
                $data['data']['avatar_url']=$user_game['avatar_url'];
                $data['data']['get_number']=$user_game['get_number'];
                $data['data']['chance_num']=$user_game['chance_num'];
                $data['data']['challenge_num']=$user_game['challenge_num'];
            }else{
                $data['code']=401;
            }
        }else{
            $data['code']=401;
        }
        $this->ajaxReturn($data,'JSON');
    }

}