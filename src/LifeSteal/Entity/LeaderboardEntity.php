<?php
namespace LifeSteal\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\Player;
use LifeSteal\Main;

class LeaderboardEntity extends Human
{
    private $times = 0;
    public function onUpdate($currentTick) 
    {
        $has = parent::onUpdate($currentTick);
        if($this->closed)
            {
            return false;
        }
        if($this->times < 20)
            {
            $this->times++;
            return false;
        }
        $plugin = Main::getInstance();
        if(is_null($plugin)) 
            {
            return false;
        }
        $this->setDataProperty(2,Entity::DATA_TYPE_STRING,$plugin->getLeaderboardManager()->heartsLeaderboard()."");
        $this->times = 0;
        if(!$has) 
            {
            $has = true;
        }
        return $has;
    }
    public function spawnTo(Player $player) 
    {
        if($player !== $this and !isset($this->hasSpawned[$player->getLoaderId()]))
            {
            $this->hasSpawned[$player->getLoaderId()] = $player;
            $uuid = $this->getUniqueId();
            $entityId = $this->getId();
            $pk = new AddPlayerPacket();
            $pk->uuid = $uuid;
            $pk->username = "";
            $pk->eid = $entityId;
            $pk->x = $this->x;
            $pk->y = $this->y;
            $pk->z = $this->z;
            $pk->yaw = $this->yaw;
            $pk->pitch = $this->pitch;
            $pk->item = Item::get(Item::AIR);
            $pk->metadata = [
                2 => [4, $this->getDataProperty(2)],
                Entity::DATA_FLAGS => [Entity::DATA_TYPE_BYTE, 1 << Entity::DATA_FLAG_INVISIBLE],
                3 => [0, $this->getDataProperty(3)],
                15 => [0, 1],
                23 => [7, -1],
                24 => [0, 0]
            ];
            $player->dataPacket($pk);
            $this->inventory->sendArmorContents($player);
        }
    }
}
