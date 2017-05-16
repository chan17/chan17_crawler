<!DOCTYPE html>
<html>
<head>
    <title>微博评论爬虫</title>
    <style type="text/css">
        .WB_handle.W_fr{
            display: none;
        }
        .list_li.S_line1{
            border-bottom: 3px solid red;
            margin-bottom: 27px;
        }
    </style>
</head>
<body>
    <form method="post">
        <label>微博链接(电脑版链接)</label>
        <input type="text" name="url" value="<?php print empty(@$_POST['url'])?'':$_POST['url']; ?>">
        <label>评论页码(数字)</label>
        <input type="text" name="page" value="<?php print empty($_POST['page'])?1:$_POST['page']; ?>">
        <input type="submit" name="提交">

        <br/>
        <br/>
        <br/>
        <br/>

        <hr>

    </form>
</body>
</html>
<?php

/**
 * 测试用主程序
 */
function main() {
    $current_url = filter_var(@$_POST['url'],FILTER_VALIDATE_URL); //初始url
    if ($current_url===false) {
        print '请输入正确链接';exit;
    }
    $page = filter_var($_POST['page'],FILTER_VALIDATE_INT); //初始url
    if ($page===false) {
        print '请输入正确页码';exit;
    }

    $result_main_html=curl_get($current_url);
    if (empty($result_main_html)) {
        print '微博获取失败';exit;
    }
    $comment_id=_filterUrl($result_main_html);

    $comment_url="http://weibo.com/aj/v6/comment/big?ajwvr=6&id={$comment_id}&filter=all&page={$page}";

    $result_comment_html=curl_get($comment_url);
    $result_comment_html=preg_replace('/<div class=\\\"WB_handle W_fr(.*?)<\\/\div>/','',$result_comment_html);

    $result_comment_json=json_decode($result_comment_html,true);
    $result_comment_json=urldecode($result_comment_json['data']['html']);

    print('<div>'.$result_comment_json.'</div>');
}
main();



/**
 * 爬虫程序 -- 原型
 *
 * 从给定的url获取html内容
 * 
 * @param string $url 
 * @return string 
 */
function curl_get($url){
    $ch=curl_init();
    $http_haeder[]="Cookie:YF-V5-G0=".md5(time())."; YF-Page-G0=ee5462a7ca7a278058fd1807a910bc74; SUB=_2AkMuRT8af8NxqwJRmP4RyWnja4t-wgDEieKYGc7BJRMxHRl-yj83qmgmtRCqtnmw0J2_nt8F2O3QKaG-H183AQ..; SUBP=0033WrSXqPxfM72-Ws9jqgMF55529P9D9WWDPDQJZcqh-hB.OLsHapiA";
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch,CURLOPT_HEADER,false);//表示需要response header
    curl_setopt($ch,CURLOPT_HTTPHEADER,$http_haeder);
    $result=curl_exec($ch);
    $code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
    if($code!='404' && $result){
     return $result;
    }
    curl_close($ch);
}
/**
 * 从html内容中筛选comment id
 * 
 * @param string $web_content 
 * @return array 
 */
function _filterUrl($web_content) {
    $reg_tag_a = '/suda-data=\\\"\\\" action-data=\\\"mid=(.*)&src/i';
    $result = preg_match($reg_tag_a, $web_content, $match_result);
    // var_dump($match_result);exit();
    if ($result) {
        if (!empty($match_result[1])) {
            return $match_result[1];
        }else{
            print 'commend id 获取出错';exit;
        }
    } 
} 
/**
 * 修正相对路径
 * 
 * @param string $base_url 
 * @param array $url_list 
 * @return array 
 */
function _reviseUrl($base_url, $url_list) {
    $url_info = parse_url($base_url);
    $base_url = $url_info["scheme"] . '://';
    if ($url_info["user"] && $url_info["pass"]) {
        $base_url .= $url_info["user"] . ":" . $url_info["pass"] . "@";
    } 
    $base_url .= $url_info["host"];
    if ($url_info["port"]) {
        $base_url .= ":" . $url_info["port"];
    } 
    $base_url .= $url_info["path"];
    print_r($base_url);
    if (is_array($url_list)) {
        foreach ($url_list as $url_item) {
            if (preg_match('/^http/', $url_item)) {
                // 已经是完整的url
                $result[] = $url_item;
            } else {
                // 不完整的url
                $real_url = $base_url . '/' . $url_item;
                $result[] = $real_url;
            } 
        } 
        return $result;
    } else {
        return;
    } 
} 
/**
 * 爬虫
 * 
 * @param string $url 
 * @return array 
 */
function crawler($url) {
    $content = _getUrlContent($url);
    if ($content) {
        $url_list = _reviseUrl($url, _filterUrl($content));
        if ($url_list) {
            return $url_list;
        } else {
            return ;
        } 
    } else {
        return ;
    } 
} 
