<?php
namespace LifeSteal\Command;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use LifeSteal\Main;

class LifeStealCommand implements CommandExecutor
{
    private $plugin;
    public function __construct(Main $plugin) 
    {
        $this->plugin = $plugin;
    }
    public function onCommand(
        CommandSender $sender,Command $command,$label,array $args)
    {
        if(!$sender instanceof Player)
            {
            $sender->sendMessage("§cUse this command in game though");
            return true;
        }
        $sub = strtolower($args[0] ?? "");
        if($sub === "worlds") 
            {
            $worlds = $this->plugin->getLifestealWorlds();
            if(empty($worlds)) 
                {
                $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §cNo LifeSteal worlds was configured.");
            }else{
                $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §fLifeSteal worlds: §a" . implode("§f, §a", $worlds));
            }
            return true;
        }
        if($sub === "owners") 
            {
            $owners = $this->plugin->getOwnerManager()->getOwners();
            if(empty($owners)) 
                {
                $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §cNo owners configured.");
            }else{
                $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §fOwners: §a" . implode("§f, §a", $owners));
            }
            return true;
        }
        if(!$sender->hasPermission("lifesteal.manage"))
            {
            $sender->sendMessage("§cYou don't have permission to do that.");
            return true;
        }
        if($sub === "set") 
            {
            if(!isset($args[1])) 
                {
                $sender->sendMessage("§cUsage: /lifesteal set <world>");
                return true;
            }
            $target = strtolower($args[1]);
            if(!$this->plugin->getServer()->isLevelGenerated($target)) 
                {
                $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §c{$target} doesn't exist as a world.");
                return true;
            }
            if(in_array($target, $this->plugin->getLifestealWorlds(), true))
                {
                $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §c{$target} is already a LifeSteal world.");
                return true;
            }
            $this->plugin->addLifestealWorld($target);
            $this->plugin->refreshWorldPlayers($target);
            $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §a{$target} added as a LifeSteal world.");
            return true;
        }
        if($sub === "remove")
            {
            if(!isset($args[1]))
                {
                $sender->sendMessage("§cUsage: /lifesteal remove <world>");
                return true;
            }
            $target = strtolower($args[1]);
            if(!in_array($target,$this->plugin->getLifestealWorlds(),true))
                {
                $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §c{$target} is not a LifeSteal world.");
                return
                true;
            }
            if(count($this->plugin->getLifestealWorlds()) <= 1)
                {
                $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §cYou must keep at least 1 LifeSteal world. Add another world first, then remove this one.");
                return 
                true;
            }
            $this->plugin->removeLifestealWorld($target);
            $this->plugin->refreshWorldPlayers($target);
            $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §a{$target} removed from LifeSteal worlds.");
            return
            true;
        }
        if($sub === "addowner")
            {
            if(!isset($args[1]))
                {
                $sender->sendMessage("§cUsage: /lifesteal addowner <name>");
                return true;
            }
            $target = $args[1];
            if(!$this->plugin->getOwnerManager()->addOwner($target))
                {
                $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §c{$target} is already an owner.");
                return true;
            }
            $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §a{$target} added as an owner.");
            return true;
        }
        if($sub === "lbspawn")
            {
            $this->plugin->spawnLeaderboardEntity($sender);
            $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §asuccess spawn top hearts leaderboard");
            return true;
        }
        if($sub === "removeowner")
            {
            if(!isset($args[1]))
                {
                $sender->sendMessage("§cUsage: /lifesteal removeowner <name>");
                return true;
            }
            $target = $args[1];
            if(!$this->plugin->getOwnerManager()->removeOwner($target))
                {
                $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §c{$target} is not an owner.");
                return true;
            }
            $sender->sendMessage("§l§8[§cLife§fSteal§8]§r §a{$target} removed from owners.");
            return true;
        }

        $sender->sendMessage("§fLifesteal Commands and their usage");
        $sender->sendMessage("§b/lifesteal set <world> §7- §6Add a LifeSteal world");
        $sender->sendMessage("§b/lifesteal remove <world> §7- §6Remove a LifeSteal world");
        $sender->sendMessage("§b/lifesteal worlds §7- §6See the list of added LifeSteal worlds");
        $sender->sendMessage("§b/lifesteal lbspawn §7- §6Spawn the leaderboard of Lifesteal");
        $sender->sendMessage("§b/lifesteal addowner <player> §7- §6Allow a member to bypass LifeSteal Zone Restrictions");
        $sender->sendMessage("§b/lifesteal removeowner <player> §7- §6Remove a member from bypassing LifeSteal Zone Restrictions");
        $sender->sendMessage("§b/lifesteal owners §7- §6List all bypassers who been in allowed list of Lifesteal Zone Restrictions");
        $sender->sendMessage("§b/safezone pos1 §7- §6Set LifeSteal SafeZone's position 1");
        $sender->sendMessage("§b/safezone pos2 §7- §6Set LifeSteal SafeZone's position 2");
        $sender->sendMessage("§b/safezone save §7- §6Save Lifesteal SafeZone after finishing the positions");
        $sender->sendMessage("§b/safezone info §7- §6See your SafeZone's information");
        $sender->sendMessage("§b/share-heart <player> <amount> §7- §6Share your own hearts with someone else");
        return true;
    }
}
