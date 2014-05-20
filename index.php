<?php
//数据库设置
$db_host = 'localhost';
$db_user = 'root';
$db_pswd = '';
$db_name = 'db2389960-main';

//数据库表
$db_info   = 'abc_book_info';  
$db_config = 'abc_book_config';

//连接数据库主机 地址 帐号 密码
$conn = mysql_connect($db_host,$db_user,$db_pswd);
if(!$conn) exit('<br/>无法连接数据库主机！ 请打开程序文件 进行数据库设置！');
mysql_query('SET NAME GBK');
//连接数据库
if(mysql_select_db($db_name,$conn)){
    //创建数据库表
    $sql = "CREATE TABLE $db_config(
        name  varchar(50) NOT NULL,
        pswd  varchar(32) NOT NULL,
        pv    int(10)     NOT NULL,
        info  text,
        PRIMARY KEY(name)
    )";
    mysql_query($sql,$conn);
    $sql = "CREATE TABLE $db_info(
        id    int NOT NULL AUTO_INCREMENT,
        ip    varchar(12) NOT NULL,
        time  varchar(10) NOT NULL,
        info  text,
        reply text,
        PRIMARY KEY(id)
    )";
    mysql_query($sql,$conn);

    //网站配置
    $admin_pswd = md5('admin');
    $web_name   = '个人留言本';
    $web_info   = '请在这留言（ 管理密码：admin ）';
    //添加内容
    mysql_query(
        "INSERT INTO $db_config(name,pswd,info,pv)
        VALUES ('$web_name','$admin_pswd','$web_info',1)"
    );
}else{
    //创建数据库
    mysql_query("CREATE DATABASE $db_name",$conn) or die('无法创建数据库!');
    header('location:?');
}


function ok(){
	exit('
		<meta http-equiv="refresh" content="3; url=?">
		<span style="color:red;">操作成功！</span>
        <a href="?">点此返回</a>　3秒后自动跳转页面...
	');
}

function html($value){
    $value = htmlspecialchars($value,ENT_QUOTES);
    if(get_magic_quotes_gpc()) $value = stripslashes($value);
    return $value;
}


//读出数据库设置变量
$sql   = mysql_query("SELECT * FROM $db_config");
$row   = mysql_fetch_array($sql);
$name  = $row['name']; 
$pswd  = $row['pswd']; 
$info  = $row['info']; 
$pv    = $row['pv'];  
mysql_query("UPDATE $db_config SET pv = $pv + 1");

$id      = isset($_GET['id']) ? intval(trim($_GET['id'])) : '';
$cookie  = isset($_COOKIE['cookie']) ? $_COOKIE['cookie'] : '';
$cookies = md5('abcbook');

//登录 生成COOKIE
if(isset($_POST['login'])){
    if(md5($_POST['login']) == $pswd){
        setcookie('cookie',$cookies);
        header('location:?');
    }else{
		header('refresh:3;url=?login');
		exit('
        <span style="color:red;">密码错误！</span>
        <a href="?login">返回</a>　
        三秒后自动跳转页面...
        ');
    }
}

//退出 清除COOKIE
if(isset($_GET['exit'])){
    setcookie('cookie','');
    header('location:?');
}

echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<style type="text/css">
    body{width:750px;margin:10px auto;border:#eee 5px solid;overflow:auto;padding:8px;word-wrap:break-word;}
    textarea{width:95%;height:80px;}
    input,select{font-size:12px;}
    body,textarea{font-size:14px;font-family:宋体,Arial;line-height:25px;color:#333;}
    a{color:#168;text-decoration:none;}
    hr{height:1px;border:none;border-bottom:1px dashed #abc;}
    div{padding:15px;}
    span{color:#e33;}
    form{margin:0;}
</style>
<title>{$name}</title>
</head>
<body>
<a href="?" style="font-size: 18px;">{$name}</a><hr />
HTML;

//登陆界面
if(isset($_GET['login']) && !$cookie){
    exit('
    <form method="post">
        管理密码：<input name="login" type="password"/>
        <input type="submit" value=" 提 交 "/>
    </form>
    ');
}

//登录后才能执行的操作
if($cookie == $cookies){ 
    if(isset($_GET['delete'])){ 
        $id = intval(trim($_GET['id']));
        mysql_query("DELETE FROM $db_info WHERE id = $id");
		ok();
    }
    if(isset($_POST['id'])){ 
        $id    = $_POST['id'];
        $info  = html($_POST['content']);
        $reply = html($_POST['reply']);
        mysql_query("
            UPDATE $db_info SET 
            info  = '$info',
            reply = '$reply'
            WHERE id = $id
        ");
        ok();
    }
    if(isset($_POST['name']) && $_POST['name']){ 
        $pv    = ($_POST['pv'] < 1) ? 1 : intval(trim($_POST['pv']));
        $pswd  = $_POST['pswd'] ? md5($_POST['pswd']) : $pswd;
        $name  = html($_POST['name']);
        $info  = html($_POST['info']);
        mysql_query("
            UPDATE $db_config SET
            name  = '$name',
            pswd  = '$pswd',
            info  = '$info',
            pv    = $pv
        ");
        ok();
    }
}

//修改 回复 删除 界面
if(isset($_GET['reply']) && $cookie){
    $sql     = mysql_query("SELECT * FROM $db_info WHERE id = $id");
    $row     = mysql_fetch_array($sql);
    $ip      = $row['ip'];
    $time    = $row['time'];
    $time    = date('Y-m-d H:i:s',$time);
    $reply   = $row['reply'];
    $content = $row['info'];
    echo <<<HTML
    <a>管理　ID：{$id}</a>　|　TIME：{$time}　|　IP：{$ip}
    <form method="post">
        修改： <textarea name="content">{$content}</textarea><br />
        回复： <textarea name="reply">{$reply}</textarea><br />
        <input type="hidden" name="id"    value="{$id}"    />
        <input type="hidden" name="time"  value="{$time}"  />
        <input type="submit" value=" 提 交 " />　
        <a href="?delete&id={$id}" onclick="return confirm('确定删除？');">删除</a>
    </form>
HTML;
exit;
}

//配置管理界面
if($cookie){
    echo <<<HTML
    <form method="post">
        名称：
        <input name="name" type="text" value="{$name}" /><br />
        浏览：
        <input name="pv" type="text" value="{$pv}" /><br />
        密码：
        <input name="pswd" type="password" />　管理密码 如不修改请留空<br />
        公告：
        <textarea name="info">{$info}</textarea>
        <input type="submit" value="提交修改" />　
        <a href="?exit">退出管理</a>　
    </form>
    <p></p>
HTML;
}

//添加内容
if(!$cookie){
    $ip_all = $_SERVER["REMOTE_ADDR"] ? $_SERVER["REMOTE_ADDR"] : '0.0.0.0';
    $ip     = preg_replace('~(\d+)\.(\d+)\.(\d+)\.(\d+)~', '$1.$2.$3.*', $ip_all);
	if(isset($_POST['add']) && $_POST['add']){
        ini_set('max_execution_time','60');
		$time    = time();
		$content = html($_POST['add']);
		mysql_query("
			INSERT INTO $db_info(time,ip,info)
			VALUES($time,'$ip_all','$content')
		");
		ok();
	}
	
    //添加内容界面
	$date = date('Y-m-d',time());
	$info = str_replace('  ','&nbsp;&nbsp;',nl2br($info));
	echo <<<HTML
	{$info}
	<form method="post">
		<textarea name="add"></textarea>
		<p>
		<input type="submit" value=" 提 交 " />
	　{$date}　IP：{$ip}
		</p>
	</form>
HTML;
}

//分页显示
$size  = 10;
$count = mysql_result(mysql_query("SELECT count(id) FROM $db_info"),0);
$pagecount = ceil($count/$size);
if(isset($_GET['page']))     $page = trim($_GET['page']);
if(!isset($page) || $page<1) $page = 1;
if($page > $pagecount)       $page = $pagecount;
$page = intval($page);

$i    = 1;
$jump = ($page - 1) * $size;
$sql  = mysql_query("SELECT * FROM $db_info ORDER BY id DESC LIMIT $jump,$size");
while($count && $row = mysql_fetch_array($sql)){
    $id      = $row['id'];
    $ip      = $row['ip'];
    $time    = $row['time'];
    $time    = date('Y-m-d H:i:s',$time);
    $reply   = $row['reply'];
    $reply   = str_replace('  ','&nbsp;&nbsp;',nl2br($reply));
    $reply   = $reply ? "<hr /><span>回复：</span><br />{$reply}" : '';
    $content = $row['info'];
    $content = str_replace('  ','&nbsp;&nbsp;',nl2br($content));
    $color   = ($i%2) ? 'style="background-color:#f5f5f5;border:#eee 1px solid;"' : '';
    if($cookie){
        $text  = '管理' ;
        $admin = "<a href=\"?reply&id={$id}\">{$text}</a>　|　";
    } else {
        $ip    = preg_replace('~(\d+)\.(\d+)\.(\d+)\.(\d+)~', '$1.$2.$3.*', $ip);
        $admin = '';
    }
    $replys  = $reply  ? '<hr /><span>回复：</span><br />' : '';
    $waiting = $content;
    echo <<<HTML
        <div {$color}>
            <span style="color:#888">
            {$admin}{$id} #　|　{$time}　|　IP：{$ip}
            </span>
            <br />
           {$content}{$reply}
        </div>
HTML;
$i++;
}

$last = $page - 1;
$next = $page + 1;
echo <<<HTML
<form method="get"><hr />
    <a href="?page={$last}">上页</a>
    第{$page}/{$pagecount}页
    <a href="?page={$next}">下页</a>　
    跳至
    <input
    name="page" type="text" size="3"
    onkeyup="this.value=this.value.replace(/\D/g,'')"
    onafterpaste="this.value=this.value.replace(/\D/g,'')"
    />
    页
    <input type="submit" value="提交"/> 　|　
    每页［{$size}］
    总数［{$count}］
    浏览［{$pv}］　|　
    <a href="?login">管理</a>
</form>
</body>
</html>
HTML;

mysql_close($conn);//关闭数据库
?>