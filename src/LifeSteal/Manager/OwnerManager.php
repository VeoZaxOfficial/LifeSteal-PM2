<?php
namespace LifeSteal\Manager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
class OwnerManager
{
    private $config;
    private $owners = [];
    public function __construct(PluginBase $plugin)
    {
        $this->config = new Config($plugin->getDataFolder()."owners.yml",Config::YAML);
        if(!$this->config->exists("owners"))
            {
            $seed = array_map("strtolower",$plugin->getConfig()->get("owners", []));
            $this->config->set("owners",array_values(array_unique($seed)));
            $this->config->save();
        }
        $this->owners = array_map("strtolower", $this->config->get("owners", []));
    }
    public function getOwners():array{
        return $this->owners;
    }
    public function isOwner(string $name):bool{
        return in_array(strtolower($name), $this->owners, true);
    }
    public function addOwner(string $name):bool{
        $name = strtolower($name);
        if($this->isOwner($name)){
            return false;
        }
        $this->owners[] = $name;
        $this->save();
        return true;
    }
    public function removeOwner(string $name):bool{
        $name = strtolower($name);
        if(!$this->isOwner($name))
            {
            return false;
        }
        $this->owners = array_values(array_diff($this->owners,[$name]));
        $this->save();
        return true;
    }
    private function save()
    {
        $this->config->set("owners",$this->owners);
        $this->config->save();
    }
}
