<?php
namespace LifeSteal\Command;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use LifeSteal\Main;

class ShareHeartCommand implements CommandExecutor
{
    private $plugin;
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }
    public function onCommand(
        CommandSender $sender, Command $command, $label, array $args){
        if(!$sender instanceof Player)
            {
            $sender->sendMessage("§cUse this command in game though");
            return true;
        }
        $name = strtolower($sender->getName());
        $world = strtolower($sender->getLevel()->getFolderName());
        if(!$this->plugin->isLifestealWorld($world))
            {
            $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §cYou must be in LifeSteal world to do this.");
            return true;
        }
        if(!isset($args[0], $args[1]) || !is_numeric($args[1]) || $args[1] <= 0)
            {
            $sender->sendMessage("§cUsage: /share-heart <player> <amount>");
            return true;
        }
        $target = $this->plugin->getServer()->getPlayer($args[0]);
        if(!$target || !$target->isOnline())
            {
            $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §cPlayer is not online or you typed their name incorrectly.");
            return true;
        }
        if($target === $sender)
            {
            $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §cYou cannot share hearts with yourself.");
            return true;
        }
        if(!$this->plugin->isLifestealWorld(strtolower($target->getLevel()->getFolderName())))
            {
            $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §cThat player must be in LifeSteal world to receive hearts.");
            return true;
        }
        $hearts = $this->plugin->getHeartManager();
        $tName = strtolower($target->getName());
        $amount = intval($args[1]) * 2;
        $senderHearts = $hearts->get($name);

        if($senderHearts <= $amount)
        {
            $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §cYou don't have that many hearts.");
            return true;
        }
        $hearts->set($name, $senderHearts - $amount);
        $hearts->add($tName, $amount);
        $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §fYou have §asuccessfully§f shared§b {$args[1]} heart §fto§e {$target->getName()}");
        $target->sendMessage("§l§8[§cLife§fSteal§8]§r §e{$sender->getName()} §fhas §ashared§b {$args[1]} heart §fwith you");
        $this->plugin->applyHearts($sender);
        $this->plugin->applyHearts($target);
        return true;
    }
}
