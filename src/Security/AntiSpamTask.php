<?php

namespace Security;

use pocketmine\scheduler\Task;

class AntiSpamTask extends Task {
    public $plugin;
    public $name;

    public function __construct(Sec $owner, string $name) {
        $this->plugin = $owner;
        $this->name = $name;
    }

    public function onRun(int $currentTick) {
        $key = array_search($this->name, $this->plugin->antispam);
        unset($this->plugin->antispam[$key]);
    }
}