<?php

namespace Security;

use pocketmine\plugin\Plugin;
use pocketmine\scheduler\PluginTask;

class ResetPackets extends PluginTask{
    public $plugin;

    public function __construct(Plugin $owner) {
        $this->plugin = $owner;
        parent::__construct($owner);
    }

    public function onRun(int $currentTick) {
        foreach ($this->plugin->packets as $count){
            $key = key($this->plugin->packets);
            if($count >= 150){
                if($this->plugin->getServer()->getPlayer($key)){
                    $this->plugin->getServer()->getPlayer($key)->kick("§4Surpassed packet limit!", false);
                }
            }
            next($this->plugin->packets);
        }
        $this->plugin->packets = [];
    }
}