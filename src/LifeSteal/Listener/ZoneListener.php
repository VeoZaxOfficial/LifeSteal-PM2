<?php
namespace LifeSteal\Listener;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\Player;
use LifeSteal\Main;
use LifeSteal\Manager\ProtectionRules;
use LifeSteal\Entity\LeaderboardEntity;
class ZoneListener implements Listener
{
    const MESSAGE_COOLDOWN_MS = 1000;
    private $plugin;
    private $lastProtectedMessage = [];
    public function __construct(Main $plugin) 
    {
        $this->plugin = $plugin;
    }
    private function bypasses(Player $player):bool{
        return $player->hasPermission("safezone.bypass");
    }
    private function isPrivileged(Player $player):bool{
        return $this->plugin->isPrivileged($player);
    }
    private function resyncBlock(Player $player,$block) 
    {
        $level = $block->getLevel();
        if($level !== null) 
            {
            $level->sendBlocks([$player], [$block]);
        }
    }
    private function sendCooldownTip(Player $player, string $key, string $message)
{
    $name = strtolower($player->getName());
    $now = microtime(true) * 1000;
    if(isset($this->lastProtectedMessage[$name][$key]) && 
          ($now - $this->lastProtectedMessage[$name][$key]) < self::MESSAGE_COOLDOWN_MS) 
        {
        return;
    }
    $this->lastProtectedMessage[$name][$key] = $now;
    $player->sendTip($message);
}
    private function sendProtectedTip(Player $player) 
    {
        $this->sendCooldownTip($player, "block", "§cThis place is Protected!\n§eGo outside for these interactions.");
    }
    private function sendZoneLockedTip(Player $player) 
    {
        $this->sendCooldownTip($player, "zonelock", "§cYou lost all hearts in Lifesteal\n§cYou cannot survive outside\n§eGet at least 1 Heart to go out");
    }
    public function onPlayerMove(PlayerMoveEvent $event) 
    {
        $player = $event->getPlayer();
        $zone = $this->plugin->getZoneManager();
        if(!$zone->isDefined()) 
            return;
        $from = $event->getFrom();
        $to = $event->getTo();
        $wasInside = $zone->contains($from->x, $from->y, $from->z, $from->getLevel()->getFolderName());
        $willBeInside = $zone->contains($to->x, $to->y, $to->z, $to->getLevel()->getFolderName());
        if($wasInside === $willBeInside) 
            return;

        $world = strtolower($to->getLevel()->getFolderName());
        $isLifestealWorld = $this->plugin->isLifestealWorld($world);
        $hearts = $this->plugin->getHeartManager();
        $name = strtolower($player->getName());
        if($isLifestealWorld && $wasInside && !$willBeInside && !$hearts->hasEnoughToEnter($name)) 
        {
            $event->setCancelled(true);
            $player->teleport($from);
            $this->sendZoneLockedTip($player);
            return;
        }
        if($willBeInside) 
            {
            $player->sendMessage("§l§8[§cLife§fSteal§8]§r §aYou entered the safe zone.");
        }else{
            $player->sendMessage("§l§8[§cLife§fSteal§8]§r §cYou left the safe zone.");
        }
        $this->plugin->applyHearts($player);
    }
     public function onPlayerQuit(PlayerQuitEvent $event)
        {
    $name = strtolower(
           $event->getPlayer()->getName());
      unset(
          $this->lastProtectedMessage[$name]);
          }
    public function onEntityDamage(EntityDamageEvent $event) 
    {
        $entity = $event->getEntity();
        if($entity instanceof LeaderboardEntity)
             {
            $event->setCancelled(true);
            if($event instanceof EntityDamageByEntityEvent) 
                {
                $damager = $event->getDamager();
                if($damager instanceof Player && $damager->isOp() && $damager->getItemInHand()->getId() === 352) 
                    {
                    $entity->kill();
                }
            }
            return;
        }
        if(!($entity instanceof Player)) 
            return;
        $owners = $this->plugin->getOwnerManager();
        if($event instanceof EntityDamageByEntityEvent) 
            {
            $damager = $event->getDamager();
            if($damager instanceof Player && $owners->isOwner($entity->getName())) 
                {
                $event->setCancelled(true);
                $damager->sendTip("§cThis player is protected and cannot be attacked!");
                return;
            }
            if($damager instanceof Player && $this->isPrivileged($damager)) 
                {
                return;
            }
            if($damager instanceof Player && !$this->bypasses($damager)
                && $this->plugin->getZoneManager()->containsPlayer($damager))
            {
                $event->setCancelled(true);
                $damager->sendTip("§cYou cannot attack anyone here!");
                return;
            }
        }
        if($this->bypasses($entity))
            return;
        if(!$this->plugin->getZoneManager()->containsPlayer($entity))
            return;

        $event->setCancelled(true);
        $entity->setHealth($entity->getMaxHealth());
        if($event instanceof EntityDamageByEntityEvent)
            {
            $damager = $event->getDamager();
            if($damager instanceof Player) {
                $damager->sendTip("§cYou cannot attack anyone here!");
            }
        }
    }
    public function onBlockPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        if($this->isPrivileged($player))
            return;
        $zone = $this->plugin->getZoneManager();
        if(!$zone->isDefined())
            return;
        $block = $event->getBlock();
        if($zone->containsBlock($block))
            {
            $event->setCancelled(true);
            $original = method_exists($event, "getBlockReplace") ? $event->getBlockReplace() : $block;
            $this->resyncBlock($player, $original);
            $this->sendProtectedTip($player);
        }
    }
    public function onBlockBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        if($this->isPrivileged($player))
            return;
        $zone = $this->plugin->getZoneManager();
        if(!$zone->isDefined())
            return;
        $block = $event->getBlock();
        if($zone->containsBlock($block))
            {
            $event->setCancelled(true);
            $this->resyncBlock($player, $block);
            $this->sendProtectedTip($player);
        }
    }
    public function onPlayerInteract(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        if($this->isPrivileged($player))
            return;
        if(!$this->plugin->getZoneManager()->containsBlock($event->getBlock()))
            return;
        $id = $event->getItem()->getId();
        $event->setCancelled(true);
        if(ProtectionRules::isBlockedInteraction($id))
            {
            $player->sendMessage("§l§8[§cLife§fSteal§8]§r §cYou cannot use this item inside the safe zone.");
        }
    }
}
