<?php
namespace LifeSteal;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\{ByteTag,CompoundTag,DoubleTag,ListTag,FloatTag,ShortTag,StringTag};
use LifeSteal\Manager\HeartManager;
use LifeSteal\Manager\ZoneManager;
use LifeSteal\Manager\OwnerManager;
use LifeSteal\Manager\LeaderboardManager;
use LifeSteal\Listener\HeartListener;
use LifeSteal\Listener\ZoneListener;
use LifeSteal\Command\LifeStealCommand;
use LifeSteal\Command\ShareHeartCommand;
use LifeSteal\Command\SafeZoneCommand;
use LifeSteal\Entity\LeaderboardEntity;

class Main extends PluginBase
{
    private static $instance = null;
    private $heartManager;
    private $zoneManager;
    private $ownerManager;
    private $leaderboardManager;
    private $allowedWorlds = [];
    private $lifestealWorlds = [];
    private $forcedDeaths = [];

    public function onEnable()
    {
        self::$instance = $this;
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->saveResource("leaderboard.yml");
        $this->allowedWorlds = array_map("strtolower",
        $this->getConfig()->get("allowed-worlds",["world"]));
        $this->lifestealWorlds = array_map("strtolower",
        $this->getConfig()->get("lifesteal-worlds",["world"]));
        $this->heartManager = new HeartManager($this);
        $this->zoneManager = new ZoneManager($this);
        $this->ownerManager = new OwnerManager($this);
        $this->leaderboardManager = new LeaderboardManager($this, $this->heartManager);
        Entity::registerEntity(LeaderboardEntity::class,true);
        $pm = $this->getServer()->getPluginManager();
        $pm->registerEvents(new HeartListener($this),$this);
        $pm->registerEvents(new ZoneListener($this),$this);
        $this->getCommand("lifesteal")->setExecutor(new LifeStealCommand($this));
        $this->getCommand("share-heart")->setExecutor(new ShareHeartCommand($this));
        $this->getCommand("safezone")->setExecutor(new SafeZoneCommand($this));
        if(!$this->zoneManager->isDefined())
            {
            $this->getLogger()->warning("LIFESTEAL IS MISSING A SAFE ZONE SETUP! run /safezone pos1, pos2 from game, then save via /safezone save. to set them up.");
        }
    }
    public static function getInstance()
    {
        return self::$instance;
    }
    public function getHeartManager():HeartManager{
        return 
        $this->heartManager;
    }
    public function getZoneManager():ZoneManager{
        return 
        $this->zoneManager;
    }
    public function getOwnerManager():OwnerManager{
        return 
        $this->ownerManager;
    }
    public function getLeaderboardManager():LeaderboardManager{
        return 
        $this->leaderboardManager;
    }
    public function spawnLeaderboardEntity(Player $sender)
    {
        $x = ((int) $sender->getX()) + 0.5;
        $y = $sender->getY();
        $z = ((int) $sender->getZ()) + 0.5;
        $nbt = new CompoundTag("", ["Pos" => new ListTag("Pos",
            [new DoubleTag("", $x),new DoubleTag("", $y),new DoubleTag("", $z)]),
            "Rotation" => new ListTag("Rotation",
            [new FloatTag("", $sender->getYaw()),new FloatTag("", $sender->getPitch())]),
            "Inventory" => new ListTag("Inventory", []),
            "Skin" => new CompoundTag("Skin", ["Data" => new StringTag("Data", $sender->getSkinData())]),
            "Health" => new ShortTag("Health", 1),
            "CustomName" => new StringTag("CustomName", ""),
            "CustomNameVisible" => new ByteTag("CustomNameVisible", 1),
        ]);
        $ent = Entity::createEntity("LeaderboardEntity", $sender->getLevel()->getChunk($x >> 4, $z >> 4), $nbt);
        $ent->spawnTo($sender);
    }
    public function getAllowedWorlds():array{
        return 
        $this->allowedWorlds;
    }
    public function getLifestealWorlds():array{
        return 
        $this->lifestealWorlds;
    }
    public function isPrivileged(Player $player):bool{
        return $player->hasPermission("safezone.bypass") || $this->ownerManager->isOwner($player->getName());
    }
    public function isLifestealWorld(string $world):bool{
        return in_array(strtolower($world),
        $this->lifestealWorlds, true);
    }
    public function addLifestealWorld(string $world)
    {
        $this->lifestealWorlds[] = strtolower($world);
        $this->saveLifestealWorlds();
    }
    public function removeLifestealWorld(string $world)
    {
        $this->lifestealWorlds = array_values(array_diff($this->lifestealWorlds,
        [strtolower($world)]));
        $this->saveLifestealWorlds();
    }
    private function saveLifestealWorlds()
    {
        $this->getConfig()->set("lifesteal-worlds",$this->lifestealWorlds);
        $this->getConfig()->save();
    }
    public function refreshWorldPlayers(string $world) 
    {
        $world = strtolower($world);
        foreach ($this->getServer()->getOnlinePlayers()as $player)
            {
            if(strtolower($player->getLevel()->getFolderName()) === $world) 
                {
                $this->applyHearts($player);
            }
        }
    }
    public function applyHearts(Player $player)
    {
        $name = strtolower($player->getName());
        $world = strtolower($player->getLevel()->getFolderName());
        if(!$this->isLifestealWorld($world)) 
            {
            $player->setMaxHealth(20);
            $player->setHealth(20);
            return;
        }
        if($this->zoneManager->containsPlayer($player))
            {
            $player->setMaxHealth(20);
            $player->setHealth(20);
            return;
        }
        if(!$this->heartManager->hasEnoughToEnter($name)) 
            {
            $player->sendMessage("§l§8[§cLife§fSteal§8]§r §cYou have no hearts left!");
            $this->forcedDeaths[$name] = true;
            $player->setMaxHealth(1);
            $player->setHealth(0);
            if($player->isAlive())
                {
                $player->kill();
            }
            return;
        }
        $stored = $this->heartManager->get($name);
        $player->setMaxHealth($stored);
        $player->setHealth($stored);
    }
    public function consumeForcedDeath(string $name):bool{
        $name = strtolower($name);
        if(isset(
            $this->forcedDeaths[$name])
            )
            {
            unset($this->forcedDeaths[$name]);
            return true;
        }
        return false;
    }
}
