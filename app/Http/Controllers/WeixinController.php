<?php

namespace App\Http\Controllers;

use App\InformationModel;
use App\model\UsersModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\UserModel;
use GuzzleHttp\Client;
class WeixinController extends Controller
{
    protected $xml_obj;
    private function check(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = 'kly';
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    public function wxEvent()
    {
//        $this->check();
        $xml_str = file_get_contents("php://input");//接收数据 获取最新的数据
       // dd($xml_str);
        file_put_contents('logs.log', $xml_str."\n\n",FILE_APPEND);//记录日志
        $data = simplexml_load_string($xml_str, 'SimpleXMLElement', LIBXML_NOCDATA);//把xml文本转换成对象
        //dd($data);
        $this->xml_obj=$data;
        if($this->check()==false)
        {
            //TODO 验签不通过
            exit;
        }
        $msg_type =$this->xml_obj->MsgType;
        //dd($msg_type);
        switch ($msg_type) {
            case 'event' :
                $EventKey=$this->xml_obj->EventKey;
                //var_dump($EventKey);die();
                if($EventKey=='V1001_TODAY_MUSIC'){ // 天气
                    //echo '11';die;
                    $Content=$this->tianqi();
                    //var_dump($Content);die;
                    $object=$this->xml_obj;
                    $aa=$this->transmitText($object,$Content);
                    //$bb=json_decode($aa);
                    return $aa;die;
                }//天气
                if($EventKey=='V1002_TODAY_MUSIC'){    //签到
                    $FromUserName=$this->xml_obj->FromUserName;
                    $key='wx:FromUserName:'.$FromUserName;
                    $user_time=Redis::zrange($key,0,-1);
                    //dd($user_time);
                    $time=strtotime(date("Y-m-d"));
//                    dd($time);
                    if(in_array($time,$user_time)){
                        $Content='今天已签到';
                        echo($Content);
                    }else {
                        Redis::zAdd($key,$FromUserName,strtotime(date("Y-m-d")));
                        $Content = '签到成功';
                        echo($Content);
                    }
                    $object=$this->xml_obj;
                   return $result=$this->transmitText($object,$Content);
                }//签到
                if($EventKey=='V1003_TODAY_MUSIC'){   //微信授权
                    $code=$this->shouquan();
                    if(!empty($code)){
                        $content= "授权成功";
                    }else{
                        $content= "授权失败";
                    }
                    $object=$this->xml_obj;
                    $this->transmitText($object, $content);
                }
                $openid=$this->xml_obj->FromUserName;
                $where=[
                    'openid'=>$openid,
                ];
                $res=UserModel::where($where)->first();
                if(empty($res)){
                    if ($data->Event=='subscribe') {
                        $xml = $this->receiveEvent($data);
                        echo $xml;
                        ///echo '111';
                        die;
                    } else {
                        //echo '2222';die;
                        echo "";
                    }
                }else{
                    $content = "欢迎回来";
                    $object=$this->xml_obj;
                    $result = $this->transmitText($object, $content);

                    echo $result;
                    die;
                }
            break;
            case 'text' ://处理文本信息
                $result = $this->gettext();
                return $result;
                //echo '文本';
                break;
            case 'image' :          // 处理图片信息
                $this->getimage();

                //echo '图片';
                break;
            case 'voice' :          // 语音
                $this ->voice();
                //echo '语音';
                break;
            case 'video' :
                $this->video();
                //echo "视频";
                break;
            default:
                echo 'default';
        }
    }
   //获取code
    public function shouquan(){
        //appid 我的微信id
        //redirect_uri 重定向的回调链接地址
        //response_type 类型为 code
        //state  重定向后会带上state参数
        $scope='ABCDEFGHIGKLMNOPQRSTUVWN1234567890qwertyuiopasdfghjklzxcvbnm';
        $redirectUri=urlencode("http://www.414shop.top/code");
        $str = substr(str_shuffle($scope),0,3);
        $url='https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx6b03c964599b8ff1&redirect_uri='.$redirectUri.'&response_type=code&scope=snsapi_userinfo&state='.$str.'#wechat_redirect';
        echo $url;die;
        header("location:".$url);
       // dd($res);
       //dd($data);
    }
    //微信授权
    public function codes(){

        //echo "123456";die;
        $code=request()->code;
        $url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx6b03c964599b8ff1&secret=dd7d5fa1b03cfdbcb4948e4c08c5609c&code=".$code."&grant_type=authorization_code";
        $refresh_token = file_get_contents($url);
        file_put_contents('logs.log', $refresh_token."\n\n",FILE_APPEND);//记录日志
        $res = json_decode($refresh_token,true);
        $access_token=$res['access_token'];
        $openid=$res['openid'];
        if($res['scope']=="snsapi_userinfo"){
            echo $res['openid'];die;
        }else{
            $urls="https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
            $userss = file_get_contents($urls);
            file_put_contents('logs.log', $userss."\n\n",FILE_APPEND);//记录日志

            $users = json_decode($userss,true);
            return $users;
        }
    }
    //处理文本
    public function gettext(){
        //获取access_token
        $xml_str = file_get_contents("php://input");//接收数据 获取最新的数据
        $data = simplexml_load_string($xml_str, 'SimpleXMLElement', LIBXML_NOCDATA);//把xml文本转换成对象
        if($this->xml_obj->Content=='天气'){
            $Content=$this->tianqi();
            //var_dump($Content);die;
            $object=$this->xml_obj;
            $aa=$this->transmitText($object,$Content);
            //$bb=json_decode($aa);
            return $aa;
        }
        $datas[]=[
            "FromUserName"=>$this->xml_obj->FromUserName,
            "CreateTime"=>$this->xml_obj->CreateTime,
            "MsgType"=>$this->xml_obj->MsgType,
            "Content"=>$this->xml_obj->Content,
        ];
        $information_model=new InformationModel();
        $information_model->insert($datas);
    }
    //处理图片
    public function getimage(){
       // echo "234567";die;
        $token=$this->weixin2();
        $media_id=$this->xml_obj->MediaId;
        $url='https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$token.'&media_id='.$media_id;
        $img = file_get_contents($url);
        //var_dump($img);die;
        $images=uniqid();
        $media_path = 'img/'.$images.'.jpg';
        $res = file_put_contents($media_path,$img);
        if($res){
           // TODO 成功
        }else{
            // TODO 失败
        }

        $datas[]=[
            "FromUserName"=>$this->xml_obj->FromUserName,
            "CreateTime"=>$this->xml_obj->CreateTime,
            "MsgType"=>$this->xml_obj->MsgType,
            "PicUrl" => $this->xml_obj->PicUrl,
            "MediaId" => $this->xml_obj->MediaId,
        ];
        $information_model=new InformationModel();
        $information_model->insert($datas);
    }
    //处理语音
    public function voice(){
        $token=$this->weixin2();
        $media_id=$this->xml_obj->MediaId;
        $url='https://api.weixin.qq.com/cgi-bin/media/get/jssdk?access_token='.$token.'&media_id='.$media_id;
        $img = file_get_contents($url);
        $images=uniqid();
        $media_path = 'voice/'.$images.'.speex';
        $res = file_put_contents($media_path,$img);
        if($res){
            //echo "成功";die;
            // TODO 成功
        }else{
            // TODO 失败
        }
        $datas[]=[
            "FromUserName" => $this->xml_obj->FromUserName,
            "CreateTime" => $this->xml_obj->CreateTime,
            "MsgType" => $this->xml_obj->MsgType,
            "MediaId" => $this->xml_obj->MediaId,
            "Format" => $this->xml_obj->Format,
            "ThumbMediaId" =>$this->xml_obj->ThumbMediaId,
        ];
        $information_model=new InformationModel();
        $information_model->insert($datas);
    }
    //处理视频
    public function video(){
        $token=$this->weixin2();
        $media_id=$this->xml_obj->MediaId;
        $url='https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$token.'&media_id='.$media_id;
        $img = file_get_contents($url);
        $images=uniqid();
        $media_path = 'video/'.$images.'.jpg';
        $res = file_put_contents($media_path,$img);
        if($res){
            // TODO 成功
        }else{
            // TODO 失败
        }
        $datas[]=[
            "FromUserName" =>  $this->xml_obj->FromUserName,
            "CreateTime" =>  $this->xml_obj->CreateTime,
            "MsgType" =>  $this->xml_obj->MsgType,
            "MediaId" =>  $this->xml_obj->MediaId,
            "ThumbMediaId" => $this->xml_obj->ThumbMediaId,
        ];
        $information_model=new InformationModel();
        $information_model->insert($datas);
    }
    public function http_get($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);//向那个url地址上面发送
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);//设置发送http请求时需不需要证书
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置发送成功后要不要输出1 不输出，0输出
        $output = curl_exec($ch);//执行
        curl_close($ch);    //关闭
        return $output;
    }
    //添加用户
    private function receiveEvent($object){
        $access_token=$this->weixin2();
        $opten_id=$object->FromUserName;
        $res="https://api.weixin.qq.com/cgi-bin/user/info?access_token="."$access_token"."&openid="."$opten_id"."&lang=zh_CN";
        $reses=$this->http_get($res);
        file_put_contents('logs.log',$reses ."\n\n",FILE_APPEND);
        $where=json_decode($reses,true);
        $ress = [
            'openid'=>$opten_id,
            'nickname'=>$where['nickname'],
            'sex'=>$where['sex'],
            'language'=>$where['language'],
            'city'=>$where['city'],
            'province'=>$where['province'],
            'country'=>$where['country'],
            'subscribe_time'=>$where['subscribe_time']
        ];
        UserModel::insert($ress);
        $content = "欢迎关注";
        $result = $this->transmitText($object, $content);
        //dd($result);
        echo $result;
    }
    //菜单
    public  function createmanu(){
        $manu=[
            "button"=>[
                    [
                        "name"=>"菜单",
                        "sub_button"=>[
                            [
                                "type"=>"click",
                                "name"=>"天气",
                                "key"=>"V1001_TODAY_MUSIC"
                            ],
                            [
                                "type"=>"click",
                                "name"=>"签到",
                                "key"=>"V1002_TODAY_MUSIC"
                            ],
                            [
                                "type"=>"click",
                                "name"=>"商城",
                                "key"=>"V1003_TODAY_MUSIC"
                            ]
                        ]
                    ],
                    [
                        "type"=>"view",
                        "name"=>"百度",
                        "url"=>"https://www.baidu.com/"
                ]
            ]
        ];

        $access_token=$this->weixin2();
        $https="https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
       // dd($https);
        $client=new Client();

        $resoonse=$client->request('post',$https,[
           'verify'=>false,
            'body'=>json_encode($manu,JSON_UNESCAPED_UNICODE),
        ]);

        $data= $resoonse->getBody();
        return  $data;
    }
    //签到
    public  function qiandao(){


    }

    //xml
    private function transmitText($object, $content){
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    </xml>";

        $result = sprintf($textTpl,$object->FromUserName,$object->ToUserName,time(),'text',$content);
       // var_dump($result);die;
        //file_put_contents('logs.log',$result);
        return  $result;
    }
    public  function weixin(){
        // $token=request()->get('echostr','');
        // if(!empty($token)&&$this->checkSignature()){
        //     echo $token;
        // }
        $access_token=$this->weixin2();
            dd($access_token);
    }
    //access_token 存缓存
    public  function weixin2(){
        $tokens = Redis::get("token");
        if(!$tokens){
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx6b03c964599b8ff1&secret=dd7d5fa1b03cfdbcb4948e4c08c5609c";
            $token = file_get_contents($url);
            $token=json_decode($token,true);
            $tokens = $token["access_token"];
            Redis::setex("token",3600,$tokens);
        }
       return $tokens;


    }
    public  function tianqi(){
        $url = "http://api.k780.com:88/?app=weather.future&weaid=beijing&&appkey=10003&sign=b59bc3ef6191eb9f747dd4e83c99f2a4&format=json";
        $weather = file_get_contents($url);
        $weather = json_decode($weather,true);
        if($weather["success"]){
            $content = "";
            foreach ($weather["result"] as $v) {
                $content .= "\n"."地区:" . $v['citynm'] .","."日期:" . $v['days'] . $v['week'] .","."温度:" . $v['temperature'] .","."风速:" . $v['winp'] .","."天气:" . $v['weather'];
            }
        }
        return $content;
    }
    public function aaa(){
        Redis::get();
    }
}
