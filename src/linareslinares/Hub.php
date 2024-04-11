<?php

namespace linareslinares;

use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\world\World;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat as TF;
use pocketmine\utils\Config;
use pocketmine\entity\effect\Effect;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\player\GameMode;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\world\Position;
use pocketmine\world\particle\EndermanTeleportParticle;

class Hub extends PluginBase implements Listener {

    public function onEnable() : void {
        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        if (!empty($config->get("Lobby-name"))) {
            $this->getServer()->getWorldManager()->loadWorld($config->get("Lobby-name"));
        } else {
            $this->getServer()->getLogger()->warning("Aun no esta fijado el mapa Lobby en los archivos..");
        }
    }

    public function onPlayerLogin(PlayerLoginEvent $event){
        $player = $event->getPlayer();
		$event->getPlayer()->teleport($this->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
	}

    public function respawn(PlayerRespawnEvent $ev): void {
		$player = $ev->getPlayer();
		$world = Server::getInstance()->getWorldManager()->getDefaultWorld();

		$ev->setRespawnPosition($world->getSpawnLocation());

	}

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool {
        $config = new Config($this->getDataFolder() . "config.yml", Config::YAML);

        switch($cmd->getName()){
            case "hub":
                if($sender instanceof Player){
					if ($sender->hasPermission("simple.hub")){
                        $entity = $sender;
                        $pos = $entity->getPosition();
                        $clearInventory = $config->get("clear-inventory", false);
                        $title = $config->get("titles", false);

                        if ($clearInventory === true) {
                            $sender->getInventory()->clearAll();
                            $sender->getArmorInventory()->clearAll();
                            $sender->getEffects()->clear();
                        }

                        if ($title === true) {
                            $sender->sendTitle(TF::BOLD . TF::YELLOW . $config->get("Title"), TF::BOLD. TF::RED . $config->get("Sub-Title"));
                        }

                        $sender->setAllowFlight(false);
				        $sender->setFlying(false);
				        $sender->setScale("1.0");
                        $sender->setHealth(20);
                        $sender->getHungerManager()->setFood(20);
				        $sender->setGamemode(GameMode::SURVIVAL());
                        $world = $this->getServer()->getWorldManager()->getWorldByName($this->getConfig()->get("Lobby-name"));
                        $sender->teleport($world->getSafeSpawn());
                        $world->addParticle($pos, new EndermanTeleportParticle());
                        $world->addParticle($pos, new EndermanTeleportParticle());
                        $world->addParticle($pos->add(1, 0, 0), new EndermanTeleportParticle());
                        $world->addParticle($pos->add(0, 1, 0), new EndermanTeleportParticle());
                        $world->addParticle($pos->add(0, 0, 1), new EndermanTeleportParticle());
                        $this->BroadSound($sender, "mob.endermen.portal", 500, 1);

					}

				}

				return false;
			break;
        }

        if($cmd->getName() === "sethub" && isset($args[0], $args[1])){
            $config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
            $mapName = $args[1];
            if(!isset($args[0])){
                $sender->sendMessage(TE::YELLOW. "Usa /sethub set <map>");
                return false;
            }

            if($sender->hasPermission("set.hub")){
                if($args[0] === "set"){
                    $config->set("Lobby-name", $mapName);
                }else{
                    $sender->sendMessage(TE::YELLOW. "Usa /sethub set <map>");
                }
                $config->save();
            }
        }
        return false;
    }

    public static function PlaySound(Player $player, string $sound, int $volume, float $pitch){
		$packet = new PlaySoundPacket();
		$packet->x = $player->getPosition()->getX();
		$packet->y = $player->getPosition()->getY();
		$packet->z = $player->getPosition()->getZ();
		$packet->soundName = $sound;
		$packet->volume = $volume;
		$packet->pitch = $pitch;
		$player->getNetworkSession()->sendDataPacket($packet);
	}

    public static function BroadSound(Player $player, string $soundName, int $volume, float $pitch){
        $packet = new PlaySoundPacket();
        $packet->soundName = $soundName;
        $position = $player->getPosition();
        $packet->x = $position->getX();
        $packet->y = $position->getY();
        $packet->z = $position->getZ();
        $packet->volume = $volume;
        $packet->pitch = $pitch;
        $world = $position->getWorld();
        NetworkBroadcastUtils::broadcastPackets($world->getPlayers(), [$packet]);
    }

}
