<?php

namespace App\Http\Controllers;

use App\InformationModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\UserModel;
use GuzzleHttp\Client;
class WeixinController extends Controller
{
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

        if($this->check()==false)
        {
            //TODO 验签不通过
            exit;
        }
        $xml_str = file_get_contents("php://input");//接收数据 获取最新的数据
      // dd($xml_str);die;
        file_put_contents('logs.log', $xml_str."\n\n",FILE_APPEND);//记录日志
        $data = simplexml_load_string($xml_str, 'SimpleXMLElement', LIBXML_NOCDATA);//把xml文本转换成对象
        // die;
//        dd($data);die;
        //$dd=explode($rses,true);
        $msg_type = $data->MsgType;
        $datas=[];
        switch ($msg_type) {
            case 'event' :
            if ($data->Event=='subscribe') {
                $xml = $this->receiveEvent($data);
                echo $xml;
                die;

            } else {
                echo "";
            }
            break;
            case 'text' ://处理文本信息
                $datas[]=[
                    "FromUserName"=>$data->FromUserName,
                    "CreateTime"=>$data->CreateTime,
                    "MsgType"=>$data->MsgType,
                    "Content"=>$data->Content,
                ];
                $information_model=new InformationModel();
                $information_model->insert($datas);
                //echo '文本';
                break;
            case 'image' :          // 处理图片信息
                $datas[]=[
                    "FromUserName"=>$data->FromUserName,
                    "CreateTime"=>$data->CreateTime,
                    "MsgType"=>$data->MsgType,
                    "PicUrl" => $data->PicUrl,
                    "MediaId" => $data->MediaId,
                ];
                $information_model=new InformationModel();
                $information_model->insert($datas);
                //echo '图片';
                break;
            case 'voice' :          // 语音
                $datas[]=[
                    "FromUserName" => $data->FromUserName,
                    "CreateTime" => $data->CreateTime,
                    "MsgType" => $data->MsgType,
                    "MediaId" => $data->MediaId,
                    "Format" => $data->Format,
                    "ThumbMediaId" =>$data->ThumbMediaId,
                ];
                $information_model=new InformationModel();
                $information_model->insert($datas);
                //echo '语音';
                break;
            case 'video' :          // 视频
                $datas[]=[
                    "FromUserName" => $data->FromUserName,
                    "CreateTime" => $data->CreateTime,
                    "MsgType" => $data->MsgType,
                    "MediaId" => $data->MediaId,
                    "ThumbMediaId" =>$data->ThumbMediaId,
                ];
                $information_model=new InformationModel();
                $information_model->insert($datas);
                //echo "视频";
                break;

            default:
                echo 'default';
        }
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
    private function receiveEvent($object){
        $access_token=$this->weixin2();
        $opten_id=$object->FromUserName;
        $res="https://api.weixin.qq.com/cgi-bin/user/info?access_token="."$access_token"."&openid="."$opten_id"."&lang=zh_CN";
        $reses=$this->http_get($res);
        file_put_contents('logs.log',$reses ."\n\n",FILE_APPEND);
        $where=json_decode($reses,true);
         // dd($where);
        //print_r($where);die;
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
        //print_r($ress);die;
       // dd($ress);
        UserModel::insert($ress);
//        dd($inser);
        $content = "欢迎关注";
        $result = $this->transmitText($object, $content);
       // var_dump($content);
        //var_dump($result);die;
        echo $result;
    }
    //菜单
    public  function createmanu(){
        $manu=[
            "button"=>[
                    [
                        "type"=>"click",
                        "name"=>"歌曲",
                        "key"=>"V1001_TODAY_MUSIC",
                        "sub_button"=>[
                            "type"=>"click",
                            "name"=>"流行歌曲",
                            "key"=>"V1001_TODAY_MUSIC"
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
        //dd($https);
        $client=new Client();
        $resoonse=$client->request('POST',$https,[
           'verify'=>false,
            'body'=>json_encode($manu,JSON_UNESCAPED_UNICODE),
        ]);
        $data= $resoonse->getBody();
        echo $data;
    }
    //xml
    private function transmitText($object, $content){
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Content><![%s]></Content>
                    </xml>";

        $result = sprintf($textTpl,$object->FromUserName,$object->ToUserName,time(),'text',$content);
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

    public function aaa(){
        Redis::get();
    }
}
