<?php
namespace LifeSteal\Command;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use LifeSteal\Main;
class SafeZoneCommand implements CommandExecutor
{
    private $plugin;
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }
    public function onCommand(
        CommandSender $sender, Command $command,$label,array $args)
        {
        if(!$sender instanceof Player)
            {
            $sender->sendMessage("§cUse this command in game though");
            return true;
        }
        if(!isset($args[0]))
            {
            $sender->sendMessage("§eUsage: /safezone <pos1|pos2|save|remove|info>");
            return true;
        }
        $zone = $this->plugin->getZoneManager();
        $arg = strtolower($args[0]);
        switch ($arg)
        {
            case "pos1":
                $zone->setPos1($sender->getPosition());
                $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §aPosition 1 set");
                break;
            case "pos2":
                $zone->setPos2($sender->getPosition());
                $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §aPosition 2 set");
                break;
            case "save":
                if(!$zone->hasPositions())
                    {
                    $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §cSet pos1 first. and then do the pos2");
                    break;
                }
                $zone->save();
                $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §aSafe zone saved. Now go and set your world's spawn point is inside of it via §f/setworldspawn");
                break;
            case "remove":
                $zone->remove();
                $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §cSafe zone removed.");
                break;

            case "info":
                if(!$zone->isDefined())
                    {
                    $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §cNo safe zone has defined. please set a one first via /safezone");
                    break;
                }
                $info = $zone->getInfo();
                $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §eSafe Zone information:");
                $sender->sendMessage("§eWorld: §b" . $info["world"]);
                $sender->sendMessage("§eMin: §b" . $info["minX"] . " " . $info["minY"] . " " . $info["minZ"]);
                $sender->sendMessage("§eMax: §b" . $info["maxX"] . " " . $info["maxY"] . " " . $info["maxZ"]);
                break;
            default:
                $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §cUnknown format. Please recheck what you have typed");
        }
        return true;
    }
}
