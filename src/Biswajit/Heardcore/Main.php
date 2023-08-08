<?php

declare(strict_types=1);

namespace Biswajit\Heardcore;

use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\Listener;
use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;
use pocketmine\command\Command;
use Symfony\Component\Filesystem\Path;
use pocketmine\command\CommandSender;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use davidglitch04\libEco\libEco;
use pocketmine\plugin\PluginLoader;
use pocketmine\Server;
use pocketmine\plugin\PluginDescription;
use pocketmine\plugin\ResourceProvider;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\resourcepacks\ZippedResourcePack;

class Main extends PluginBase implements Listener {
	
	private $playerDeaths = [];
	
	private $config;
	
	private $plugin;
	
  private $protectedPlayers = [];
    
    public function __construct(PluginLoader $loader, Server $server, PluginDescription $description, string $dataFolder, string $file, ResourceProvider $resourceProvider) {
        parent::__construct($loader, $server, $description, $dataFolder, $file, $resourceProvider);
    }
    
    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    
        $this->saveResource("config.yml");
        
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, array());
        
        $version = $this->getDescription()->getVersion();
        $configVer = $this->getConfig()->get("version");
        
        $this->saveResource("Heardcore.mcpack");
		$rpManager = $this->getServer()->getResourcePackManager();
		$rpManager->setResourceStack(array_merge($rpManager->getResourceStack(), [new ZippedResourcePack(Path::join($this->getDataFolder(), "Heardcore.mcpack"))]));
		(new \ReflectionProperty($rpManager, "serverForceResources"))->setValue($rpManager, true);

        if(version_compare($version, $configVer, "<>")) {
            $this->getLogger()->warning("Plugin version does not match config version. Disabling plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
    }
 public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $this->protectedPlayers[$player->getName()] = time() + $this->config->get("Protected-Time");
        $player->sendMessage("§l§cYour Are Protected For " . $this->config->get("Protected-Time") . " Seconds");
    }

  public function onCommand(CommandSender $sender, Command $cmd, string $label,array $args): bool{
		switch($cmd->getName()){
			case "revive":
				  if(!$sender->hasPermission("Heardcore.cmd.revive")) {
				    $sender->sendMessage("You Don't Have Permission To Use This Command");
				  } else {
				    $this->revive($sender);
				  }
				}
				return true;
		}
		
   public function revive(Player $player) {
					$form = new SimpleForm(function (Player $player, $data){
						$result = $data;
						if($result === null){
							return true;
						}
						switch($result){
						  case 0:
						  $amount = $this->config->get("Revive-Amount"); {
				          libEco::reduceMoney($player, $amount, function (bool $success) use ($player) : void {
                          if ($success) {
				 		  $this->playerrevive($player);
						  } else {
						    $player->sendMessage("§l§cError! §r§cYou Don't Have Enough Money :<");
                          }
                   });
             }
        }
    });
		  $form->setTitle("§l§bREVIVE A PLAYER ?");
          $form->setContent("§l§eYOU CAN REVIVE YOUR FRIEND WHO DEATH AND BAN FROM SERVER\n\n§dREVIVR PRICE§f " . $this->config->get("Revive-Amount"));
          $form->addButton("§r§l§aPURCHASE\n§r§l§c»» §r§6Tap To Purchase", 1, "https://cdn-icons-png.flaticon.com/512/1168/1168610.png");
          $form->sendToPlayer($player);
          return $form;
		}
		
    public function playerrevive(Player $player) {
    $form = new CustomForm(function(Player $player, $data) {
        if(empty($data[0])) { // If the input is empty
            $player->sendMessage("Please enter a player name.");
            return;
        }

        $input = $data[0]; // Get the value of the input field
        $ban_list = $player->getServer()->getNameBans();

        if (!$ban_list->isBanned($input)) { // If player isn't banned
            $player->sendMessage("The player is still alive!");
            return;
        }
        $player->getServer()->getNameBans()->remove($input);
        $player->sendMessage("Successful Revived $inpute:<");
    });
    $form->setTitle("Revive A Player");
    $form->addInput("Enter Player Name", "Enter Player Name");

    $player->sendForm($form);
    }
     
public function onDamage(EntityDamageEvent $event) {
    $entity = $event->getEntity();
    if ($entity instanceof Player) {
        if (isset($this->protectedPlayers[$entity->getName()])) {
            $currentTime = time();
            $protectionEndTime = $this->protectedPlayers[$entity->getName()];
            if ($currentTime < $protectionEndTime) {
                $event->cancel(true);
            } else {
                unset($this->protectedPlayers[$entity->getName()]);
            }
        }
    }
}
   public function onPlayerDeath(PlayerDeathEvent $event) {
       $player = $event->getPlayer();
       $playerName = $player->getName();


    if (!isset($this->playerDeaths[$playerName])) {
        $this->playerDeaths[$playerName] = 1;
    } else {
        $this->playerDeaths[$playerName]++;
    }
    
    if ($this->playerDeaths[$playerName] >= $this->getConfig()->get("Max-Life")) {
        $this->clearPlayerData($player);
        if($player->kick('§l§cYou Die And Ban From Server Until Someone Revive You')){
				$player->getServer()->getNameBans()->addBan($player->getName(), '§l§cYou Die And Ban Until Someone Revive You');
        }
    }
}

    private function clearPlayerData(Player $player) {
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getCursorInventory()->clearAll();
    }
}
