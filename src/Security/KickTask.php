<?php

namespace Security;

use pocketmine\plugin\Plugin;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;

class KickTask extends Task{
    public $plugin;

    public function __construct(Plugin $owner) {
        $this->plugin = $owner;
    }

    public function onRun(int $currentTick) {
        $file = new Config($this->plugin->file);

        $bans = $file->get("bans");

        foreach ($bans as $ban){
            if($this->plugin->getServer()->getPlayer($ban["player"])){
                $p = $this->plugin->getPlayer($ban["player"]);

                if(strtolower($p->getName()) === $ban["player"]){
                    if($this->plugin->isBanned($ban["player"])){
                        $p->transfer("0.0.0.0", 19132);
                    }
                }
            }
        }
    }
}
