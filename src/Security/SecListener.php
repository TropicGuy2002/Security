<?php

namespace Security;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\utils\Config;

class SecListener implements Listener {
    public $plugin;

    public function __construct(Sec $plugin) {
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();

        $name = $player->getName();

        $ban = $this->plugin->isBanned($name);

        if ($ban) {
            $text = "§4You are banned until §c" . $ban["until"] . "§e\nRequest unban in our Discord! §fhttps://WorldPlanet.ml\n§eReason: §f" . $ban["reason"];

            $player->close($text, $text, true);
        }
    }

    public function onChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();

        $name = strtolower($player->getName());

        if ($this->plugin->isMuted($name)) {
            $mute = $this->plugin->isMuted($name);
            $player->sendMessage($this->plugin->prefix . " §fYou are muted until " . $mute["until"] . "! Reason: " . $mute["reason"]);
            $event->setCancelled(true);
        } else {
            if (in_array($player->getName(), $this->plugin->antispam)) {
                $event->setCancelled(true);
                $player->sendMessage($this->plugin->prefix . " §fPlease wait a few seconds!");
            }

            $msg = $event->getMessage();

            $file = new Config($this->plugin->file);

            $words = $file->get("words");

            if (strpos($msg, " ") !== false) {
                $strs = explode(" ", $msg);

                $strs = array_map("strtolower", $strs);

                foreach ($strs as $str) {
                    if (in_array($str, $words)) {
                        $this->plugin->addMute($name, "1D", "Automatic mute caused by following message: " . $msg);
                        $mute = $this->plugin->isMuted($name);
                        $player->sendMessage($this->plugin->prefix . " §fYou are muted until " . $mute["until"] . "! Reason: " . $mute["reason"]);
                        $event->setCancelled(true);
                    }
                }
            } else {
                if (in_array(strtolower($msg), $words)) {
                    $this->plugin->addMute($name, "1D", "Automatic mute caused by following message: " . $msg);
                    $mute = $this->plugin->isMuted($name);
                    $player->sendMessage($this->plugin->prefix . " §fYou are muted until " . $mute["until"] . "! Reason: " . $mute["reason"]);
                    $event->setCancelled(true);
                }
            }

            array_push($this->plugin->antispam, $player->getName());
            $this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new AntiSpamTask($this->plugin, $player->getName()), 20 * 3);
        }
    }

    public function onCommandProcess(PlayerCommandPreprocessEvent $event) {
        $msg = $event->getMessage();

        $player = $event->getPlayer();

        if (strpos($msg, " ") !== false) {
            $args = explode(" ", $msg);
            if ($args[0] == "/msg" or $args[0] == "/w" or $args[0] == "/tell") {
                if ($this->plugin->isMuted($player)) {
                    $event->setCancelled(true);
                }
            } elseif ($args[0] == "/version" or $args[0] == "/ver") {
                $event->setCancelled(true);
                $player->sendMessage($this->plugin->prefix . " §fWe use PocketMine-MP! http://pmmp.io");
            } else {
                $cfg = new Config($this->plugin->file);
                $cmds = $cfg->get("blockedcommands");

                foreach ($cmds as $cmd) {
                    if ("/" . $cmd === $args[0]) {
                        $event->setCancelled(true);
                        $player->sendMessage($this->plugin->prefix . " §fCommand is blocked!");
                    }
                }
            }
        } else {
            if ($msg == "/msg" or $msg == "/w" or $msg == "/tell") {
                if ($this->plugin->isMuted($player) !== false) {
                    $event->setCancelled(true);
                }
            } elseif ($msg == "/version" or $msg == "/ver") {
                $event->setCancelled(true);
                $player->sendMessage($this->plugin->prefix . " §fWe use PocketMine-MP! http://pmmp.io");
            } else {
                $cfg = new Config($this->plugin->file);
                $cmds = $cfg->get("blockedcommands");

                foreach ($cmds as $cmd) {
                    if ("/" . $cmd === $msg) {
                        $event->setCancelled(true);
                        $player->sendMessage($this->plugin->prefix . " §fCommand is blocked!");
                    }
                }
            }
        }
    }

    public function onDataPacket(DataPacketReceiveEvent $event) {
        $pk = $event->getPacket();

        $player = $event->getPlayer();
        $name = $player->getName();

        if (!isset($this->plugin->packets[$name])) {
            $this->plugin->packets[$name] = 1;
        } else {
            $this->plugin->packets[$name]++;
        }
    }
}
