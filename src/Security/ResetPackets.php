<?php

namespace Security;

use pocketmine\plugin\Plugin;
use pocketmine\scheduler\Task;

class ResetPackets extends Task{
    public $plugin;

    public function __construct(Plugin $owner) {
        $this->plugin = $owner
    }

    public function onRun(int $currentTick) {
        foreach ($this->plugin->packets as $count){
            $key = key($this->plugin->packets);
            if($count >= 150){
                if($this->plugin->getPlayer($key)){
                    $this->plugin->getPlayer($key)->kick("§4Surpassed packet limit!", false);
                }
            }
            next($this->plugin->packets);
        }
        $this->plugin->packets = [];
    }
}
