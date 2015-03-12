<?php
namespace Home\Controller;

class ShakeController extends BaseController
{

    private $app_id = 'wxad6a4df6a301fb4b';

    private $app_secret = 'ece8f0acb68c7ad8765997fd79a72b5b';

    public function sendAction()
    {
        $userID = I('get.uid');
        $actionto = I('get.ac');
        $userobj = M('user');
        $userinfo = $userobj->field('user_id, user_name, user_regdate, user_image')->where('user_id = "' . $userID . '"')->find();
        if ($userinfo) {
            session('userinfo', $userinfo);
        } else {
            session('userinfo', array('user_id' => $userID, 'user_name' => '访客'));
        }
        $this->redirect('index/' . $actionto);
    }

    public function gotoOauthAction()
    {
        $parent = I('get.parentid');

        $redirect_url = urlencode('http://' . $_SERVER['SERVER_NAME'] . '/index.php/Shake/index?parentid=' . $parent . '&from=singlemessage&isappinstalled=0');

        $gotourl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $this->app_id . '&redirect_uri=' . $redirect_url . '&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
        redirect($gotourl);
    }

    public function indexAction()
    {

        require_once APP_PATH . "Common/Common/jssdk.php";
        require_once APP_PATH . "Common/Common/pay.php";




//-------------------------------------------------------------------------------------------------------------------------
        $packet = new \Packet();
        $refresh_token = session('refresh_token');
        $parentid = I('get.parentid');
        $code = I('get.code');

        $jssdk = new \JSSDK($this->app_id, $this->app_secret);
        $signPackage = $jssdk->GetSignPackage();
        if (!$refresh_token) {
            if (!$code) {
                $this->redirect('gotoOauth', array('parentid' => $parentid));
            }
            $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $this->app_id . "&secret=" . $this->app_secret . "&code=" . $code . "&grant_type=authorization_code";
            $json_content = file_get_contents($url);
            $json_obj = json_decode($json_content, true);
            $access_token = $json_obj['access_token'];
            $openid = $json_obj['openid'];
            session(array('name' => 'access_token_id', 'expire' => $json_obj['expires_in']));
            session('refresh_token', $json_obj['refresh_token']);
        } else {
            $url = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=" . $this->app_id . "&grant_type=refresh_token&refresh_token=" . $refresh_token;
            $json_content = file_get_contents($url);
            $json_obj = json_decode($json_content, true);
            $access_token = $json_obj['access_token'];
            $openid = $json_obj['openid'];
        }


        $userinfostr = file_get_contents("https://api.weixin.qq.com/sns/userinfo?access_token=" . $access_token . "&openid=" . $openid . "&lang=zh_CN");
        $userinfo = json_decode($userinfostr, true);
        if (!$userinfo['openid']) {
            unset($_SESSION['refresh_token']);

            $this->redirect('gotoOauth', array('parentid' => $parentid));
        }
   //    $packet->_route('wxpacket',array('openid'=>$userinfo['openid']));
        /*    $money = M('money');
        $setting = M("setting");
        $user = M('user');
        $setinfo = $setting->where('set_id = 1')->find();
        $my_money_list = array();
        $totel_money = 0;
        if ($userinfo) {
        $wxuser = $user->where('user_id = "'.$userinfo['openid'].'"')->find();
        if (!$wxuser) {
        $data = array('user_id'=>$userinfo['openid'], 'user_name'=>$userinfo['nickname'], 'user_regdate'=>date('Y-m-d H:i:s'), 'user_image'=>$userinfo['headimgurl'], 'user_status'=>'1', 'user_money'=>0);
        $user_result = $user->add($data);
        }
        //设置自己的初始资金
        $own_money = $money->where('money_owner = "'.$userinfo['openid'].'" and money_from = "0"')->find();
        if ($own_money) {
        $this->assign('is_get_money', 1);
        } else {
        $data = array('money_owner'=>$userinfo['openid'], 'money_number'=>$setinfo['set_beginmoney'], 'money_from'=>'0', 'money_time'=>date('Y-m-d H:i:s'));
        $own_money_result = $money->add($data);
        if ($own_money_result) {
        $user->where('user_id = "'.$userinfo['openid'].'"')->setInc('user_money', $setinfo['set_beginmoney']);
        }
        $this->assign('is_get_money', 0);
        }
        //得到我从别人那里分享来的资金
        $my_get_money = $money->where('money_owner = "'.$userinfo['openid'].'" and money_from != "0"')->select();
        foreach ($my_get_money as $my_money) {
        $usermoneyinfo = $user->where('user_id = "'.$my_money['money_from'].'"')->find();
        $my_money = array_merge($my_money, $usermoneyinfo);
        $my_money_list[] = $my_money;
        }
        //得到我的总金额
        $wxuser = $user->where('user_id = "'.$userinfo['openid'].'"')->find();
        $totel_money = $wxuser['user_money'];
        }

        $this->assign('my_money_list', $my_money_list);

        $this->assign('setinfo', $setinfo);
        $this->assign('totel_money', $totel_money);


        $this->assign('code', $code);*/
        $shakeuser = M('shake_user');
        $user = $shakeuser->where('openid = "' . $userinfo['openid'] . '"')->find();



//如果页面链接中没有邀请人，也就意味着这是一个全新的链接，

        if ($user&&isset($user['name'])&&isset($user['phone'])) {
                $isreg=1;
        } else {
                 $isreg=0;
        }
        $this->assign('signPackage', $signPackage);

        $this->assign('isreg', $isreg);

        $this->assign('parentid', $parentid);
        $this->assign('userinfo', $userinfo);

        $this->display();
    }

    public function zhuliAction()
    {
        $parentopenid = I('post.parentopenid');
        $zhuliuseropenid = I('post.zhuliuseropenid');
        $post = filterAllParam('post');
        $zhuli = M("jz_zhuli");
        $user = M("jz_user");
        /*  echo $parentopenid;
        echo 'zz+'.$zhuliuseropenid;
        exit;*/
        $zhuliuser = $zhuli->where('parentopenid = "' . $parentopenid . '" AND zhuliuseropenid="' . $zhuliuseropenid . '" ')->find();
        if ($zhuliuser) {
            echo '您已经帮他/她助力过啦！分享页面让好友为你助力吧！';
        } else {
            $zhuliid = $zhuli->add($post);
            if ($zhuliid) {
                $user->where('openid = "' . $parentopenid . '"')->setInc('countzan', 1);
                echo '助力成功了！分享页面让好友为你助力！';
            } else {
                echo "莫名原因！助力失败,重新试试？";
            }
        }
    }

    public function  joinAction()
    {
        $post = filterAllParam('post');
        $user = M("shake_user");
        $wxuser = $user->where('openid = "' . $post['openid'] . '"')->find();
        if (!$wxuser) {
            $userid = $user->add($post);
            if ($userid) {

                echo '报名成功';
            } else {
                echo "报名参加失败";
            }
        } else {
            echo "您已经在参与了";
        }

    }

    public function eventAction()
    {
        $fromUserName = I('post.fromUserName');
        $nickname = I('post.nickname');
        $headimgurl = I('post.headimgurl');
        $eventType = I('post.eventType');
        $user = M('user');
        if ($eventType == 'subscribe') {
            $status = '1';
        } else {
            $status = '0';
        }
        $userinfo = $user->where('user_id = "' . $fromUserName . '"')->find();
        if ($userinfo) {
            $result = $user->where('user_id = "' . $fromUserName . '"')->setField('user_status', $status);
        } else {
            $data = array('user_id' => $fromUserName, 'user_name' => $nickname, 'user_regdate' => date('Y-m-d H:i:s'), 'user_image' => $headimgurl, 'user_status' => $status);
            $result = $user->add($data);
        }
        return '关注成功';
    }

    public function tixianAction()
    {
        $openid = I('get.openid');
        $setting = M("setting");
        $setinfo = $setting->where('set_id = 1')->find();
        $untildate = strtotime($setinfo['set_untildate']);
        $now = time();
        if ($now > $untildate) {
            $this->error('啊呀，你来迟了，哈蓝女神被人捋走了，一个不剩（不气馁，下期可累积继续）');
        }
        $user = M('user');
        $wxuser = $user->where('user_id = "' . $openid . '"')->find();
        if ($wxuser['user_money'] < $setinfo['set_getmoney']) {
            $this->error($setinfo['set_getmoney'] . "都没有，还想泡哈蓝女神？快去赚吧！（第一波2015.1.12~1.19）");
        }
        $this->assign('totel_money', $wxuser['user_money']);
        $this->assign('setinfo', $setinfo);
        $this->assign('openid', $openid);
        $this->display();
    }

    public function savetxAction()
    {
        $post = filterAllParam('post');

        $setting = M("setting");
        $setinfo = $setting->where('set_id = 1')->find();
        $user = M('user');
        $wxuser = $user->where('user_id = "' . $post['tx_userid'] . '"')->find();
        if (!$wxuser) {
            $this->error('未知用户');
        }
        if ($wxuser['user_money'] < $setinfo['set_getmoney']) {
            $this->error("您账户金额小于可提现金额，账户金额大于" . $setinfo['set_getmoney'] . '时可提现');
        }
        if ($post['tx_number'] > $wxuser['user_money']) {
            $this->error('您输入的金额大于你账户拥有的资金');
        }

        $tixian = M("tixian");
        unset($post['tx_card2']);
        unset($post['totel_money']);
        $post['tx_date'] = date('Y-m-d H:i:s');
        $isok = $tixian->add($post);
        if ($isok) {
            $user->where('user_id = "' . $post['tx_userid'] . '"')->setDec('user_money', $post['tx_number']);
            $this->success('提现成功', U('index/index'));
        } else {
            $this->error("提现失败");
        }
    }
}
