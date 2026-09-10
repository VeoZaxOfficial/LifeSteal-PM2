<?php
namespace LifeSteal\Manager;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
class ZoneManager
{
    private $config;
    private $zone = [];
    private $pos1 = null;
    private $pos2 = null;
    public function __construct(PluginBase $plugin)
    {
        $this->config = new Config($plugin->getDataFolder()."border.yml",Config::YAML);
        $this->zone = $this->config->getAll();
    }
    public function setPos1(Position $pos)
    {
        $this->pos1 = $pos;
    }
    public function setPos2(Position $pos) 
    {
        $this->pos2 = $pos;
    }
    public function hasPositions():bool{
        return $this->pos1 !== null && $this->pos2 !== null;
    }
    public function save():bool{
        if(!$this->hasPositions())
            {
            return
            false;
        }
        $this->zone = ["world" => $this->pos1->getLevel()->getFolderName(),"minX" => min($this->pos1->x, $this->pos2->x),"maxX" => max($this->pos1->x, $this->pos2->x),"minY" => min($this->pos1->y, $this->pos2->y),"maxY" => max($this->pos1->y, $this->pos2->y),"minZ" => min($this->pos1->z, $this->pos2->z),"maxZ" => max($this->pos1->z, $this->pos2->z),];
        $this->config->setAll($this->zone);
        $this->config->save();
        return
        true;
    }
    public function remove()
    {
        $this->zone = [];
        $this->config->setAll($this->zone);
        $this->config->save();
    }
    public function isDefined():bool{
        if(empty($this->zone))
            {
            return false;
        }
        foreach(["world","minX","maxX","minY","maxY","minZ","maxZ"] as $key)
            {
            if(!isset($this->zone[$key]))
                {
                return false;
            }
        }
        return true;
    }
    public function getInfo():array{
        return $this->zone;
    }
    public function contains($x, $y, $z, string $world):bool{
        if(!$this->isDefined()) 
            {
            return false;
        }
        if(strtolower($world) !== strtolower($this->zone["world"])) 
            {
            return false;
        }
        return $x >= $this->zone["minX"] && $x <= $this->zone["maxX"]
            && $y >= $this->zone["minY"] && $y <= $this->zone["maxY"]
            && $z >= $this->zone["minZ"] && $z <= $this->zone["maxZ"];
    }
    public function containsPlayer($player):bool{
        return $this->contains($player->x, $player->y, $player->z, $player->getLevel()->getFolderName());
    }
    public function containsBlock($block):bool{
        return $this->contains($block->getX(), $block->getY(), $block->getZ(), $block->getLevel()->getFolderName());
    }
}
