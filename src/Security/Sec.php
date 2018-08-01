<?php

namespace Security;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Sec extends PluginBase {
    public $prefix = "§7[§bSecurity§7]";

    public $file = "/home/Datenbank/security.yml";

    public $antispam = [];

    public $packets = [];

    public function onEnable() {
        if (!file_exists($this->file)) {
            $this->initFile();
        }

        $this->getServer()->getPluginManager()->registerEvents(new SecListener($this), $this);

        $this->getServer()->getScheduler()->scheduleRepeatingTask(new KickTask($this), 20 * 10);

        $this->getServer()->getScheduler()->scheduleRepeatingTask(new ResetPackets($this), 20);

        $this->getLogger()->info($this->prefix." §floaded!");
    }

    public function initFile() {
        $file = new Config($this->file);
        $file->set("bans", []);
        $file->set("mutes", []);
        $file->set("words", [
            "fuck",
            "pimmel",
            "ez",
            "noob",
            "ltp",
            "learn to play",
            "fick",
            "ficken"
        ]);
        $file->set("blockedcommands", [
            "help",
            "kill",
            "me"
        ]);

        $file->save();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if($command == "sban"){
            if(!$sender->hasPermission("security.ban")){
                $sender->sendMessage($this->prefix." §fNo permission!");
                return false;
            }
            if(empty($args[0]) or empty($args[1])){
                $sender->sendMessage($this->prefix." §f/sban -player- -time- [-reason-]");
                return false;
            }

            if($this->isBanned($args[0])){
                $sender->sendMessage($this->prefix." §fPlayer is already banned!");
                return false;
            }

            if(empty($args[2])){
                $reason = "Undetermined";
            }else{
                $c = 0;

                $reason = "";

                foreach ($args as $arg){
                    if($c >= 2){
                        if($c == 2){
                            $reason = $arg;
                        }else{
                            $reason = $reason." ".$arg;
                        }
                    }
                    $c++;
                }
            }

            $this->addBan($args[0], $args[1], $reason);

            $sender->sendMessage($this->prefix." §fBanned ".strtolower($args[0])."!");
        }elseif($command == "unban"){
            if(!$sender->hasPermission("security.unban")){
                $sender->sendMessage($this->prefix." §fNo permission!");
                return false;
            }

            if(empty($args[0])){
                $sender->sendMessage($this->prefix." §f/unban -player-");
                return false;
            }

            if($this->isBanned($args[0])){
                $this->rmBan($args[0]);
                $sender->sendMessage($this->prefix." §fUnbanned ".strtolower($args[0])."!");
            }else{
                $sender->sendMessage($this->prefix." §f".strtolower($args[0])." isn't banned!");
            }
        }elseif($command == "mute"){
            if(!$sender->hasPermission("security.mute")){
                $sender->sendMessage($this->prefix." §fNo permission!");
                return false;
            }
            if(empty($args[0]) or empty($args[1])){
                $sender->sendMessage($this->prefix." §f/mute -player- -time- [-reason-]");
                return false;
            }

            if($this->isMuted($args[0])){
                $sender->sendMessage($this->prefix." §fPlayer is already muted!");
                return false;
            }

            if(empty($args[2])){
                $reason = "Undetermined";
            }else{
                $c = 0;

                $reason = "";

                foreach ($args as $arg){
                    if($c >= 2){
                        if($c == 2){
                            $reason = $arg;
                        }else{
                            $reason = $reason." ".$arg;
                        }
                    }
                    $c++;
                }
            }

            $this->addMute($args[0], $args[1], $reason);

            $sender->sendMessage($this->prefix." §fMuted ".strtolower($args[0])."!");
        }elseif($command == "unmute"){
            if(!$sender->hasPermission("security.unmute")){
                $sender->sendMessage($this->prefix." §fNo permission!");
                return false;
            }

            if(empty($args[0])){
                $sender->sendMessage($this->prefix." §f/unmute -player-");
                return false;
            }

            if($this->isMuted($args[0])){
                $this->rmMute($args[0]);
                $sender->sendMessage($this->prefix." §fUnmuted ".strtolower($args[0])."!");
            }else{
                $sender->sendMessage($this->prefix." §f".strtolower($args[0])." isn't muted!");
            }
        }
        return true;
    }

    public function addBan(string $player, string  $add, string $reason) {
        if ($add == "forever") {
            $now = $add;
        } else {
            $now = new \DateTime("now");
            $now->add(new \DateInterval("P".$add));
        }

        $file = new Config($this->file);
        $bans = $file->get("bans");

        array_push($bans, [
            "player" => strtolower($player),
            "until" => $now->format('Y-m-d H:i:s'),
            "reason" => $reason
        ]);

        $file->set("bans", $bans);
        $file->save();

        if($this->getServer()->getPlayer($player)){
            $p = $this->getServer()->getPlayer($player);

            if(strtolower($p->getName()) === strtolower($player)){
                $p->transfer("atomicmc.tk", 19132);
            }
        }
    }

    public function rmBan(string $player) {
        $file = new Config($this->file);
        $bans = $file->get("bans");

        foreach ($bans as $ban) {
            if ($ban["player"] === strtolower($player)) {
                $key = array_search($ban, $bans);
                unset($bans[$key]);

                $file->set("bans", $bans);
                $file->save();
            }
        }
    }

    public function isBanned(string $player) {
        $file = new Config($this->file);
        $bans = $file->get("bans");

        foreach ($bans as $ban) {
            if ($ban["player"] === strtolower($player)) {
                if($ban["until"] === "forever"){
                    return $ban;
                }
                $until = new \DateTime($ban["until"]);
                $now = new \DateTime("now");

                if ($until > $now) {
                    $arr = [
                        "until" => $until = $ban["until"],
                        "reason" => $ban["reason"]
                    ];
                    return $arr;
                } else {
                    $this->rmBan($player);
                    return false;
                }
            }
        }
    }

    public function addMute(string $player, string  $add, string $reason) {
        if ($add == "forever") {
            $now = $add;
        } else {
            $now = new \DateTime("now");
            $now->add(new \DateInterval("P".$add));
        }

        $file = new Config($this->file);
        $mutes = $file->get("mutes");

        array_push($mutes, [
            "player" => strtolower($player),
            "until" => $now->format('Y-m-d H:i:s'),
            "reason" => $reason
        ]);

        $file->set("mutes", $mutes);
        $file->save();
    }

    public function rmMute(string $player) {
        $file = new Config($this->file);
        $mutes = $file->get("mutes");

        foreach ($mutes as $mute) {
            if ($mute["player"] === strtolower($player)) {
                $key = array_search($mute, $mutes);
                unset($mutes[$key]);

                $file->set("mutes", $mutes);
                $file->save();
            }
        }
    }

    public function isMuted(string $player) {
        $file = new Config($this->file);
        $mutes = $file->get("mutes");

        foreach ($mutes as $mute) {
            if ($mute["player"] === strtolower($player)) {
                if($mute["until"] === "forever"){
                    return $mute;
                }
                $until = new \DateTime($mute["until"]);
                $now = new \DateTime("now");

                if ($until > $now) {
                    $arr = [
                        "until" => $until->format('Y-m-d H:i:s'),
                        "reason" => $mute["reason"]
                    ];
                    return $arr;
                } else {
                    $this->rmMute($player);
                    return false;
                }
            }
        }
        return false;
    }
}