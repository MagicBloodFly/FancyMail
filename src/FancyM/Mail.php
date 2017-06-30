<?php
namespace FancyMail;

use pocketmine\plugin\Plugin;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\network\protocol\Info;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class Mail extends PluginBase implements Listener{
 
 public function onEnable(){
     @mkdir($this->getDataFolder());
	$this->cfg = new Config($this->getDataFolder() . "Config.yml", CONFIG::YAML ,array(
	"SMTP服务器地址"=>"",
	"SMTP服务器端口"=>"",
	"SMTP服务器的用户邮箱"=>"",
	"发件人SMTP用户名"=>"",
	"发件人SMTP用户名密码"=>"",
	"邮件主题"=>"玩家登陆游戏确认",
	"邮件内容"=>"您在XX服务器登录了一次游戏,如果不是本人登陆,请联系腐竹进行申诉",
	"身份验证"=>"false",
	"以上内容请认真填写,否则将出现错误"=>""
		));	
    @mkdir($this->getDataFolder());
	$this->pl = new Config($this->getDataFolder() . "Player.yml", CONFIG::YAML ,array(
 "玩家绑定的邮箱大全"=>""
		));	

		$this->getServer()->getPluginManager()->registerEvents($this,$this);
		
	}
public function onLoad(){
  
   $this->getServer()->getLogger()->info("\n§6==MG雪飞插件==\n§b邮箱发送加载成功\n§e作者:韩雪飞");
 
	}
	

public function onJoin(PlayerJoinEvent $e){

$p=$e->getPlayer();
$n=$p->getName();

if($this->pl->get($n)==null)
{
$p->sendMessage("§e<§aFancyMail§e>§b您还没有设置邮箱,请使用/fcmail绑定");

}
else
{
require("smtp.php");

$smtpserver = "$this->cfg->get('SMTP服务器地址')";

$smtpserverport =$this->cfg->get("SMTP服务器端口");//SMTP服务器端口

$smtpusermail = "$this->cfg->get('SMTP服务器的用户邮箱')";

$smtpemailto = "$this->pl->get($n)";

$smtpuser = "$this->cfg->get('发件人SMTP用户名')";

$smtppass = "$this->cfg->get('发件人SMTP用户名密码')";

$mailsubject = "$this->cfg->get('邮件主题')";

$mailbody = "$this->cfg->get('邮件内容')";//邮件内容

$mailtype = "TXT";

$smtp = new smtp($smtpserver,$smtpserverport,$this->cfg->get("身份验证"),$smtpuser,$smtppass);

$smtp->debug = FALSE;

$smtp->sendmail($smtpemailto, $smtpusermail, $mailsubject, $mailbody, $mailtype);




}


	}
	
	public function onCommand(CommandSender $s, Command $cmd, $label, array $args)
{

    if($cmd->getName()=="fcmail")
    {
    	if(isset($args[0])){
            switch($args[0]){
			    case "help":
			         $s->sendMessage("§e===§6FancyMail§e===\n§b欢迎您使用FancyMail\n§b这是一款可以防止账号被盗的插件\n§b使用方法很简单,只需要绑定自己的邮箱\n§b请使用指令 /fc <自己的邮箱> 进行绑定");
			         break;
			    case "info":
			         $s->sendMessage("§e===§6FancyMail§e===\n§e作者:§6Magic雪飞\n§e插件版本:§61.0.0\n§e开源地址:§6github.com/MagicBloodFLY\n§b来自 FancyDream Team 团队");
			         return true;
			         break;
            }
        }
		else
		{
			$s-sendMessage("§e<§aFancyMail§e>§b请使用正确指令/fcmail help/info");
			
		}
    }
    if($cmd=='fc'){
        if(empty($args[0])){
            $s->sendMessage('§b请使用指令 /fc <自己的邮箱> 进行绑定\n§e示例:/fc xxxxx@qq.com');
            return false;
        }
        $e = $args[0];
        if($this->checkExists($e)){
            $s->sendMessage('邮箱已经被使用过了。');
            return true;
        }
        if(!$this->is_email($e)){
            $s->sendMessage('无效邮箱');
            return true;
        }
        if($this->bindMail($s,$e)){
            $s->sendMessage('绑定成功');
          
		  $this->pl->set($s->getName(),$e);
		   
        }else{
            $s->sendMessage('绑定失败');
        
        }
    }
}

public function is_email(String $email, $checkNS = true){
    if(empty($email)) return false;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    if(!empty($checkNS)){
        if (!checkdnsrr($domain, 'MX')) {
            return false;
        }
    }

    return true;
}

/**
 * checkExists 检查数据库中是否有人曾经使用过这个邮箱
 * @param String $email 需要检查的邮箱
 * @return Boolean 返回布林
 */
public function checkExists($email){
	
	if($email==$this->pl->getAll())
	{
		return false;
		
	}
	else
	{
		return true;
	}
    //TODO:检查邮箱是否被绑定过，绑定过返回TRUE，否则返回FALSE
}

/**
 * checkBind 检查数据库中此玩家是否绑定过
 * @param Player $p 需要检查的玩家
 * @return Boolean 返回布林
 */
public function checkBind(Player $p){
    $pn = $p->getName();
    $cid = $p->getClientId();
	
	if($this->pl->get($pn)!==null)
	{
		return $p->sendMessage("§e<§aFancyMail§e>§b您已经绑定过邮箱了,请勿连续绑定");
		
	}
    //TODO:检查是否已经绑定。绑定过返回TRUE 没有则返回FALSE；
}

/**
 * bindMail 绑定邮箱函数
 * @param Player $p
 * @param String $e
 * @return Boolean
 */
public function bindMail(Player $p, $e ){
    $pn = $p->getName();
    $cid = $p->getClientId();
    //TODO:绑定邮箱具体实现函数
}

	
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	