<?php
namespace LifeSteal\Listener;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\Player;
use LifeSteal\Main;
use LifeSteal\ApplyHeartTask;

class HeartListener implements Listener
{
    const APPLY_DELAY_TICKS = 20;
    const TELEPORT_DELAY_TICKS = 40;
    private $plugin;
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }
    public function onUse(PlayerInteractEvent $event) 
    {
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        if(!$this->plugin->getHeartManager()->isHeartItem($item))
        return;
        $world = strtolower($player->getLevel()->getFolderName());
        if(!in_array($world, $this->plugin->getAllowedWorlds(),true))
            {
            $player->sendMessage("§l§8[§cLife§fSteal§8]§r §cSorry, Hearts can be used only in LifeSteal enabled worlds");
            $event->setCancelled(true);
            return;
        }
        $event->setCancelled(true);
        $this->addHeart($player,2);
        $count = $item->getCount();
        if($count <= 1)
            {
            $player->getInventory()->setItemInHand(Item::get(Item::AIR));
        }else{
            $item->setCount($count - 1);
            $player->getInventory()->setItemInHand($item);
        }
    }
    public function addHeart(Player $player, int $amount = 2)
    {
        $hearts = $this->plugin->getHeartManager();
        $name = strtolower($player->getName());
        $wasLocked = !$hearts->hasEnoughToEnter($name);
        $hearts->add($name, $amount);
        $player->sendMessage("§l§8[§cLife§fSteal§8]§r §aYou used a Heart!");
        if($wasLocked && $hearts->hasEnoughToEnter($name)) {
            $player->sendMessage("§l§8[§cLife§fSteal§8]§r §aYou can now leave the safe zone.");
        }
        $this->plugin->applyHearts($player);
    }
    public function onJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        $this->plugin->getHeartManager()->ensurePlayer(strtolower($player->getName()));

        $this->plugin->getServer()->getScheduler()->scheduleDelayedTask(
            new ApplyHeartTask($this->plugin,$player),self::APPLY_DELAY_TICKS);
    }
    public function onRespawn(PlayerRespawnEvent $event)
    {
        $this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new ApplyHeartTask($this->plugin, $event->getPlayer()), self::APPLY_DELAY_TICKS);
    }
    public function onTeleport(EntityTeleportEvent $event) 
    {
        $entity = $event->getEntity();
        if(!($entity instanceof Player)) return;

        $this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new ApplyHeartTask($this->plugin, $entity), self::APPLY_DELAY_TICKS);
    }
    public function onCommandPreprocess(PlayerCommandPreprocessEvent $event) 
    {
        $cmd = strtolower($event->getMessage());
        foreach(["/spawn", "/hub", "/lobby"] as $trigger) 
            {
            if(strpos($cmd, $trigger) === 0) 
                {
                $this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new ApplyHeartTask($this->plugin, $event->getPlayer()), self::TELEPORT_DELAY_TICKS);
                return;
            }
        }
    }
    public function onDeath(PlayerDeathEvent $event)
    {
        $victim = $event->getEntity();
        if(!($victim instanceof Player)) 
            return;
        if($this->plugin->consumeForcedDeath($victim->getName())) 
            return;

        $cause = $victim->getLastDamageCause();
        if(!($cause instanceof EntityDamageByEntityEvent)) 
            return;

        $damager = $cause->getDamager();
        if(!($victim instanceof Player) || !($damager instanceof Player) || $damager === $victim) 
            return;
        $world = strtolower($victim->getLevel()->getFolderName());
        if(!$this->plugin->isLifestealWorld($world)) 
            return;
        $victimName = strtolower($victim->getName());
        $this->plugin->getHeartManager()->remove($victimName, 2);
        $victim->getLevel()->dropItem($victim, $this->plugin->getHeartManager()->makeHeartItem());
        $damager->sendMessage("§l§8[§cLife§fSteal§8]§r §a{$victim->getName()} dropped a Heart!");
    }
}
