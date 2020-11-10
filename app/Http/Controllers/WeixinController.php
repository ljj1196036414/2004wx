<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\UserModel;
class WeixinController extends Controller
{
    private function checkSignature (){
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
    public function wxEvent(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = 'kly';
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            $xml_str=file_get_contents("php://input");
            file_put_contents('logs.log',$xml_str);
            $data=simplexml_load_string($xml_str,'SimpleXMLElement',LIBXML_NOCDATA);

           // die;
           // dd($inser);
            //$dd=explode($res,true);
          $this->receiveEvent($data);
           //dd($result);
            die;
        }else{
            echo "";
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
        if($object->MsgType=="event"){
            if($object->Event=="subscribe"){
                $access_token=$this->weixin2();
                $opten_id=$object->FromUserName;
                $res="https://api.weixin.qq.com/cgi-bin/user/info?access_token="."$access_token"."&openid="."$opten_id"."&lang=zh_CN";
                $reses=$this->http_get($res);
                file_put_contents('logs.log',$reses);
                $where=json_decode($reses,true);
//            dd($where);
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
                //dd($wheres);
                $inser=UserModel::insert($ress);
                $content = "欢迎关注";
                $result = $this->transmitText($object, $content);
                return $result;
            }
        }
    }
    public  function createmanu(){
        $https=" https://api.weixin.qq.com/cgi-bin/menu/create?access_token=ACCESS_TOKEN";
    }
    private function transmitText($object, $content){
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            </xml>";

        $result = sprintf($textTpl,$object->FromUserName,$object->ToUserName,time(),'text',$content);
        file_put_contents('logs.log',$result);
        echo  $result;
    }
    public  function weixin(){
        // $token=request()->get('echostr','');
        // if(!empty($token)&&$this->checkSignature()){
        //     echo $token;
        // }
        $access_token=$this->weixin2();
            dd($access_token);
    }
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
