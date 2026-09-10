<?php
namespace LifeSteal\Manager;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
class HeartManager
{
    const POINTS_PER_HEART = 2;
    const MIN_POINTS_TO_ENTER = 2;
    private $hearts;
    public function __construct(PluginBase $plugin)
    {
        $this->hearts = new Config($plugin->getDataFolder()."hearts.yml",Config::YAML);
    }
    public function ensurePlayer(string $name, int $default = 20)
    {
        if(!$this->hearts->exists($name))
            {
            $this->hearts->set($name,$default);
            $this->hearts->save();
        }
    }
    public function get(string $name,int $default = 20):int{
        return (int) $this->hearts->get($name, $default);
    }
    public function getAll():array{
        return $this->hearts->getAll();
    }
    public function set(string $name,int $value)
    {
        $this->hearts->set($name, max(0, $value));
        $this->hearts->save();
    }
    public function add(string $name,int $amount):int{
        $new = $this->get($name) + $amount;
        $this->set($name,$new);
        return $new;
    }
    public function remove(string $name,int $amount,int $minimum = 0):int{
        $new = max($this->get($name) - $amount, $minimum);
        $this->set($name, $new);
        return $new;
    }
    public function hasEnoughToEnter(string $name):bool{
        return $this->get($name) >= self::MIN_POINTS_TO_ENTER;
    }
    public static function pointsToHearts(int $points):int{
        return intdiv($points,self::POINTS_PER_HEART);
    }
    public function getHearts(string $name,int $default = 20):int{
        return self::pointsToHearts($this->get($name, $default));
    }
    public function makeHeartItem(int $count = 1):Item{
        $item = Item::get(ItemIds::DYE,1,$count);
        $item->setCustomName("§c§lHEART");
        return $item;
    }
    public function isHeartItem(Item $item):bool{
        return $item->getId() === ItemIds::DYE && $item->getCustomName() === "§c§lHEART";
    }
}
