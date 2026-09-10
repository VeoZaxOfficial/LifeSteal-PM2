<?php
namespace LifeSteal\Manager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use LifeSteal\Manager\HeartManager;
class LeaderboardManager
{
    private $heartManager;
    private $messages;
    public function __construct(PluginBase $plugin, HeartManager $heartManager)
    {
        $this->heartManager = $heartManager;
        $this->messages = new Config($plugin->getDataFolder()."leaderboard.yml",Config::YAML);
    }
    public function center(string $msg):string{
        $lines = explode("\n",$msg);
        $count = count($lines);
        if($count <= 0)
            {
            return $msg;
        }
        $longest = 0;
        foreach ($lines as $line) 
            {
            $len = strlen($line);
            if($len > $longest) 
                {
                $longest = $len;
            }
        }
        $out = "";
        foreach($lines as $line)
            {
            $pad = str_repeat(" ", (int) ceil(($longest - strlen($line)) / 2));
            $out .= $pad . $line . $pad . "\n";
        }
        return $out;
    }
    public function heartsLeaderboard():string{
        $top = $this->heartManager->getAll();
        arsort($top);
        $template = $this->messages->get("hearts_led", "§8  -§c Top Hearts §8-   \n{top_list}");
        $entryTemplate = $this->messages->get("hearts_led2", "§8│ §b#{counts} §f{name} §8» §c{hearts}");
        $topList = "";
        $i = 0;
        foreach($top as $name => $points)
            {
            $i++;
            $heartsCount = HeartManager::pointsToHearts((int) $points);
            $entry = str_replace(["{counts}", "{name}", "{hearts}"],[$i, $name, $heartsCount],$entryTemplate);
            $topList .= $this->center($entry);
            if($i >= 10)
                {
                break;
            }
        }
        $msg = str_replace(["{top_list}", "{top_count}"],[$topList, $i],$template);
        return $this->center($msg);
    }
}
