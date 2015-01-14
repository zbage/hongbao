<?php
namespace Home\Controller;

class VoteController extends BaseController {

    private $app_id = 'wxc43356a7940e32d4';

    private $app_secret = 'ec234926610a429dfaca36328af9b014';

    public function indexAction() {
        $this->display();
    }
    
    public function addpiaoAction() {
        $bmid = I('get.bmid');
        if (!$bmid) {
            echo '无此参赛人';exit;
        }
        $userid = $this->userInfo['user_id'] || 'test';
        $piao = M("piao");
        $baoming = M("baoming");
        $bminfo = $baoming->where('bm_id = "'.$bmid.'"')->find();
        if (!$bminfo) {
            echo '查无此人';exit;
        }
        $istou = $piao->where('bm_id = "'.$bmid.'" and toupiao_user = "'.$userid.'"')->count();
        if ($istou) {
            echo '您已投过此人，不可重复投';exit;
        }
        
        $piaoid = $piao->add(array('bm_id'=>$bmid, 'toupiao_user'=>$userid, 'piao_date'=>date('Y-m-d H:i:s'), 'vote_id'=>$bminfo['vote_id']));
        if ($piaoid) {
            $baoming->where('bm_id = "'.$bmid.'"')->setInc('total_piao', 1);
            echo '投票成功';exit;
        } else {
            echo '投票失败';exit;
        }
    }
    
    public function showVoteAction() {
        $vote = M("Vote");
        $voteid = I('get.voteid');
        $sortby = I('get.sortby');

        $voteinfo = $vote->where('vote_id = "'.$voteid.'"')->find();
        if (!$voteinfo) {
            $this->error("无此投票", U('vote/index'));
        }
        $this->assign('voteinfo', $voteinfo);
        
        $piao = M("piao");
        $total_piao = $piao->where('vote_id = "'.$voteid.'"')->count();
        $this->assign('total_piao', $total_piao);

        $baoming = M("baoming");
        if (!$sortby || $sortby == 'new') {
            $orderby = 'baoming_date desc';
        } else {
            $orderby = 'total_piao desc';
        }
        $count = $baoming->where('vote_id = "'.$voteid.'"')->count();
        $page = new \Think\Page($count, 18);
        $bmlist = $baoming->where('vote_id = "'.$voteid.'"')->limit($page->firstRow.','.$page->listRows)->order($orderby)->select();
        $show = $page->show();
        $this->assign('page', $show);
        $this->assign('total_baoming', $count);
        $this->assign('bmlist', $bmlist);

        $this->assign('sortby', $sortby);
        $this->display();
    }
    
    public function saveVoteAction() {
        $isdelimage = I('post.delweibo_send');
        if ($isdelimage) {
            $_POST['weibo_send'] = '';
            unlink('./upload/'.$isdelimage);
        }
        if ($_FILES['weibo_send']['name']) {
            $upload = new \Think\Upload();
            $upload->maxSize = 3145728;//3M
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = './upload/';
            $uploadinfo = $upload->uploadOne($_FILES['weibo_send']);
            if(!$uploadinfo) {
                $this->error($upload->getError());
            }
            $_POST['weibo_send'] = $uploadinfo['savepath'].$uploadinfo['savename'];
        }
        $vote = M("Vote");
        $post = filterAllParam('post');
        if (isset($post['vote_id']) && $post['vote_id']) {
            unset($post['delweibo_send']);
            $voteid = $vote->where('vote_id="'.$post['vote_id'].'"')->save($post);
        } else {
            $voteid = $vote->add($post);
        }
        if ($voteid) {
            $this->success('保存成功', U('vote/showVote', array('voteid'=>$voteid)));
        } else {
            $this->error("保存失败");
        }
    }

    public function baomingAction() {
        $voteid = I('get.voteid');
        $this->assign('voteid', $voteid);
        $this->display();
    }

    public function savebmAction() {
        if ($_FILES['weibo_send1']['name']) {
            $upload = new \Think\Upload();
            $upload->maxSize = 3145728;//3M
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = './upload/';
            $uploadinfo = $upload->uploadOne($_FILES['weibo_send1']);
            if(!$uploadinfo) {
                $this->error($upload->getError());
            }
            $_POST['weibo_send1'] = $uploadinfo['savepath'].$uploadinfo['savename'];
        }
        if ($_FILES['weibo_send2']['name']) {
            $upload = new \Think\Upload();
            $upload->maxSize = 3145728;//3M
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = './upload/';
            $uploadinfo = $upload->uploadOne($_FILES['weibo_send2']);
            if(!$uploadinfo) {
                $this->error($upload->getError());
            }
            $_POST['weibo_send2'] = $uploadinfo['savepath'].$uploadinfo['savename'];
        }
        if ($_FILES['weibo_send3']['name']) {
            $upload = new \Think\Upload();
            $upload->maxSize = 3145728;//3M
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = './upload/';
            $uploadinfo = $upload->uploadOne($_FILES['weibo_send3']);
            if(!$uploadinfo) {
                $this->error($upload->getError());
            }
            $_POST['weibo_send3'] = $uploadinfo['savepath'].$uploadinfo['savename'];
        }
        $baoming = M("baoming");
        $post = filterAllParam('post');
        unset($post['area']);
        $post['baoming_date'] = date('Y-m-d H:i:s');
        $baomingid = $baoming->add($post);
        if ($baomingid) {
            $this->success('报名成功', U('vote/showVote', array('voteid'=>$post['vote_id'])));
        } else {
            $this->error("报名失败");
        }
    }
}
