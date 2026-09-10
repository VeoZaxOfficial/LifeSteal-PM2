<?php
namespace LifeSteal;
use pocketmine\scheduler\PluginTask;
use pocketmine\Player;
class ApplyHeartTask extends PluginTask
{
    private $plugin;
    private $player;
    public function __construct(Main $plugin,Player $player)
    {
        parent::__construct($plugin);
        $this->plugin = $plugin;
        $this->player = $player;
    }
    public function onRun($tick)
    {
        if($this->player->isOnline())
            {
            $this->plugin->applyHearts($this->player);
        }
    }
}
