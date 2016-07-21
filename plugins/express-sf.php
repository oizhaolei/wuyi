<?php 
    date_default_timezone_set('PRC');

class Express_SF 
{
    //private $domain = 'open-prod.sf-express.com';
    private $domain = 'open-sbox.sf-express.com';
    private $version = 'v1.0';
    private $sf_appid = '00017315';
    private $sf_appkey = '255A2F3CC5B8DC5C4689EDD526B2DE9B';

    private $SF_TYPE_PUBLIC = 'public';	// 免授权API（适用于安全类接口）
    private $SF_TYPE_REST = 'rest';	// 授权类API（适用于非安全类的所有接口）

    static $access_token = '';

    /*
    *************************************************************************
    *     授权类型(type)                 值
    *     public                         免授权API（适用于安全类接口）
    *     rest                           授权类API（适用于非安全类的所有接口）
    *************************************************************************
    *     资源名称(resource)              值
    *     快速下单                        /order/
    *     订单查询                        /order/query/
    *     订单筛选                        /filter/
    *     路由查询                        /route/query/
    *     路由增量查询                    /route/inc/query/
    *     电子运单图片下载                /waybill/image/
    *     基础服务查询                    /product/basic/query/
    *     附加服务查询                    /product/additional/query/
    *     申请访问令牌                    /security/access_token/
    *     查询访问令牌                    /security/access_token/query/
    *     刷新访问令牌                    /security/refresh_token/
    *************************************************************************
    *     编码(transType)                 描述
    *     200                             快速下单
    *     201                             订单结果通知
    *     203                             订单查询
    *     204                             订单筛选
    *     205                             电子运单图片下载
    *     250                             基础服务查询
    *     251                             附加服务查询
    *     500                             路由推送
    *     501                             路由查询
    *     504                             路由增量查询
    *     300                             查询ACCESS_TOKEN
    *     301                             申请ACCESS_TOKEN
    *     302                             刷新ACCESS_TOKEN
    */

    /*
     * 组装URL
     */
    public function getUrl($type, $resource, $query_string)
    {
        $url = 'https://' . $this->domain . '/' . $type . '/' . $this->version . $resource . $query_string;
        return $url;
    }

    /*
     * 格式化Header
     */
    function FormatHeader($url, $data)
    { 
        $temp = parse_url($url); 
        $query = isset($temp['query']) ? $temp['query'] : ''; 
        $path = isset($temp['path']) ? $temp['path'] : '/'; 
        $format_header = array ( 
            "POST {$path}?{$query} HTTP/1.1", 
            "Host: {$temp['host']}", 
            "Content-Type: application/json",  
            "Content-length: ".strlen($data), 
            "Connection: Close" 
        );
        return $format_header; 
    } 

    /*
     * 得到API响应内容
     */
    function getResponseContent($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT,5);
        curl_setopt($ch, CURLOPT_POST, true);
        $format_header = $this->FormatHeader($url, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $format_header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result_json=curl_exec($ch);
        curl_close($ch);

        if($result_json)
        {
            $json_pos = strpos($result_json, '{');
            if($json_pos < 0)
            {
                return "";
            }
            else
            {
                $result_json = substr($result_json, $json_pos);
                return $result_json;
            }
        }
        else
        {
            return '';
        }
    }

    /*
     * 交易流水号格式如：YYYYMMDD+流水号{10},
     * 例如：201404120000000001,
     * 交易流水号唯一且不能重复
     */
    function transMessageId()
    {
        return date("Ymd") . random(10);
    }

    /*
     * 300                             查询ACCESS_TOKEN
     */
    function queryAccessToken(){
        $type = $this->SF_TYPE_PUBLIC;
        $resource = '/security/access_token/query/';
        $query_string = 'sf_appid/' . $this->sf_appid . '/sf_appkey/' . $this->sf_appkey;

        $url = $this->getUrl($type, $resource, $query_string);
        $transType = '300';
        $data='{"head":{"transType":"' . $transType .'","transMessageId":"' . $this->transMessageId() . '"}}';
 
        $result_json = $this->getResponseContent($url, $data);
        if($result_json)
        {
            $arr = json_decode($result_json,true);
            if($arr["head"]["code"] == 'EX_CODE_OPENAPI_0200')
            {
                return $arr["body"]["accessToken"];
            }
            else
            {
                return $arr["head"]["message"];
            }
        }
        else
        {
            return '';
        }
    }

    /*
     * 301    ACCESS_TOKEN申请
     */
    function applyAccessToken(){
        $type = $this->SF_TYPE_PUBLIC;
        $resource = '/security/access_token/';
        $query_string = 'sf_appid/' . $this->sf_appid . '/sf_appkey/' . $this->sf_appkey;

        $url = $this->getUrl($type, $resource, $query_string);
        $transType = '301';
        $data='{"head":{"transType":"' . $transType .'","transMessageId":"' . $this->transMessageId() . '"}}';
        $result_json = $this->getResponseContent($url, $data);
        if($result_json)
        {
            $arr = json_decode($result_json,true);
            if($arr["head"]["code"] == 'EX_CODE_OPENAPI_0200')
            {
                return $arr["body"]["accessToken"];
            }
            else
            {
                return $arr["head"]["message"];
            }
        }
        else
        {
            return '';
        }
    }

    /*
     * 302                             刷新ACCESS_TOKEN
     */
    function refreshAccessToken($access_token){
        $type = $this->SF_TYPE_PUBLIC;
        $resource = '/security/refresh_token/';
        $query_string = 'access_token/' . $access_token .'/sf_appid/' . $this->sf_appid . '/sf_appkey/' . $this->sf_appkey;

        $url = $this->getUrl($type, $resource, $query_string);
        $transType = '302';
        $data='{"head":{"transType":"' . $transType .'","transMessageId":"' . $this->transMessageId() . '"}}';
echo 'url=' .$url . '<br/>';
echo 'data=' .$data . '<br/>';
        $result_json = $this->getResponseContent($url, $data);
echo 'result_json=' . $result_json;
        if($result_json)
        {
            $arr = json_decode($result_json,true);
            if($arr["head"]["code"] == 'EX_CODE_OPENAPI_0200')
            {
                return $arr["body"]["accessToken"];
            }
            else
            {
                return $arr["head"]["message"];
            }
        }
        else
        {
            return '';
        }
    }

    /*
     * 203                             订单查询
     */
    function queryOrderInfo($access_token, $orderId)
    {
        $type = $this->SF_TYPE_REST;
        $resource = '/order/query/';
        $query_string = 'access_token/' . $access_token .'/sf_appid/' . $this->sf_appid . '/sf_appkey/' . $this->sf_appkey;
        $url = $this->getUrl($type, $resource, $query_string);
        $transType = '203';
        $data='{"head":{"transType":"' . $transType .'","transMessageId":"' . $this->transMessageId() . '"}, "body":{ "orderId":"' . $orderId . '" }}';
echo 'data=' .$data . '<br/>';
        $result_json = $this->getResponseContent($url, $data);
echo 'result_json=' . $result_json;
        if($result_json)
        {
            $arr = json_decode($result_json,true);
            if($arr["head"]["code"] == 'EX_CODE_OPENAPI_0200')
            {
                return $arr["body"]["accessToken"];
            }
            else
            {
                return $arr["head"]["message"];
            }
        }
        else
        {
            return '';
        }
    }

    /*
     * 501                             路由查询
     */
    function queryRouteInfo($access_token, $trackingNumber)
    {
        $type = $this->SF_TYPE_REST;
        $resource = '/route/query/';
        $query_string = 'access_token/' . $access_token .'/sf_appid/' . $this->sf_appid . '/sf_appkey/' . $this->sf_appkey;
        $url = $this->getUrl($type, $resource, $query_string);
        $transType = '501';
        $trackingType = '2';
        $data='{"head":{"transType":"' . $transType .'","transMessageId":"' . $this->transMessageId() . '"}, "body":{ "trackingType":"' . $trackingType . '","trackingNumber":"' . $trackingNumber .'","methodType":"1" }}';
echo 'data=' .$data . '<br/>';
        $result_json = $this->getResponseContent($url, $data);
echo 'result_json=' . $result_json;
        if($result_json)
        {
            $arr = json_decode($result_json,true);
//{"head":{"transType":"4501","transMessageId":"201607198548224326","code":"EX_CODE_OPENAPI_0200","message":"操作成功"},"body":[{"orderId":"605512172980","mailno":"755123456789","acceptTime":"2012-7-30 09:30:00","acceptAddress":"广东省深圳市福田区新洲十一街,万基商务大厦","opcode":"1","remark":"无"}]}
            if($arr["head"]["code"] == 'EX_CODE_OPENAPI_0200')
            {
                return $arr["body"][0]["acceptAddress"];
            }
            else
            {
                return $arr["head"]["message"];
            }
        }
        else
        {
            return '';
        }
    }


}

/*
 * 产生随机字符串
 * $length  字符串位数
 * $chars 所用到的字符
 * 返回 不重复的随机字符串
 */
function random($length, $chars = '0123456789') {
    $result = '';
    $max_len = strlen($chars) - 1;
    for($i = 0; $i < $length; $i++) 
    {
        $result .= $chars[mt_rand(0, $max_len)];
    }
    return $result;
}

$sf = new Express_SF();
//$access_token = $sf->applyAccessToken();    // OK
$access_token = $sf->queryAccessToken();    // OK
echo $access_token;
echo '<br/>................................................<br/>';
$access_token = $sf->refreshAccessToken($access_token);
echo $access_token;
//$orderId = '587123456789';//'605512172980';
//$result = $sf->queryOrderInfo($access_token, $orderId);
//$result = $sf->queryRouteInfo($access_token, $orderId);
echo '<br/>................................................<br/>';
//echo $result;
//$result_json='{"head":{"transType":4301,"transMessageId":"201607195986810954","code":"EX_CODE_OPENAPI_0200","message":"申请ACCESS TOKEN成功"},"body":{"accessToken":"03FE3906DA8096E76F56330B028A2288","refreshToken":"0349D07D213184A4890704D408272EE7"}}';
//$arr = json_decode($result_json,true);
//echo($arr["body"]["accessToken"]);
?>