<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class WeixinController extends Controller
{
    private function checkSignature ()
    {
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
          $this->receiveEvent($data);
           //dd($result);
            die;
        }else{
            echo "";
        }
    }
    private function receiveEvent($object){
        if($object->MsgType=="event"){
            if($object->Event=="subscribe"){
                $content = "欢迎关注";
                $result = $this->transmitText($object, $content);

            }
        }

        return $result;
    }
    private function transmitText($object, $content)
    {
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
        $token=request()->get('echostr','');
        if(!empty($token)&&$this->checkSignature()){
            echo $token;
        }
    }
    public  function weixin2(){
        $tokens = Redis::get("token");
        if(!$tokens){
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx6b03c964599b8ff1&secret=dd7d5fa1b03cfdbcb4948e4c08c5609c";
            $token = file_get_contents($url);
            $token=json_decode($token,true);
            $tokens = $token["access_token"];
            Redis::setex("token",60*60*24,$tokens);
        }
        dd($tokens);


    }
    public function aaa(){
        Redis::get();
    }
}
