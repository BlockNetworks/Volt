<?php
namespace volt\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use volt\api\Subscription;
use volt\Volt;

class VoltCommand extends Command implements PluginIdentifiableCommand{
    private $main;
    public function __construct(Volt $main){
        parent::__construct("volt", "Control volt.", "/volt [stuff]", ["http"]);
        $this->main = $main;
    }
    public function execute(CommandSender $sender, string $label, array $args){
        if($sender->hasPermission("volt.command")) {
            if (isset($args[0])) {
                if($sender->hasPermission("volt.command.{$args[0]}")) {
                    switch ($args[0]) {
                        case 'api':
                            if(!isset($args[1])) $args[1] = "list";
                            if($sender->hasPermission("volt.command.api.{$args[1]}")) {
                                switch ($args[1]) {
                                    case 'list':
                                        $sender->sendMessage("Plugins using the Volt API:");
                                        if(count($this->getPlugin()->getMonitoredDataStore()->getIterator()) === 0){
                                            $sender->sendMessage("Looks like you don't yet have any plugins :(\n You can view a list of them at https://github.com/Falkirks/Volt");
                                        }
                                        else {
                                            foreach ($this->getPlugin()->getMonitoredDataStore()->getIterator() as $name => $plugin) {
                                                $sender->sendMessage("- $name");
                                            }
                                        }
                                        break;
                                    case "block":
                                        $sender->sendMessage("API blocking will be ready in the next major release.");
                                        break;
                                    case 'getsub':
                                        foreach($this->getPlugin()->getServer()->getPluginManager()->getPlugins() as $plugin){
                                            $sender->sendMessage("- " . $plugin->getName() . " => " . Subscription::switchLevelFormat(Subscription::getLevel($plugin)));
                                        }
                                        break;
                                    case 'stats':
                                        $sender->sendMessage("| " . TextFormat::BLUE . "Plugin" . TextFormat::RESET . " | Reads | Writes |");
                                        foreach($this->getPlugin()->getMonitoredDataStore()->getIterator() as $name => $data){
                                            $sender->sendMessage("| " . TextFormat::BLUE . $name . TextFormat::RESET . " | " . count($data["reads"]) . " | " . count($data["writes"]) . " |");
                                        }
                                        break;
                                    default:
                                        $sender->sendMessage("The API feature {$args[1]} doesn't exist.");
                                        break;
                                }
                            }
                            else{
                                $sender->sendMessage("You don't have permission to use {$args[1]} on the api.");
                            }
                            break;
                        default:
                            $sender->sendMessage("The action {$args[0]} doesn't exist.");
                            break;
                    }
                }
                else{
                    $sender->sendMessage("You don't have permission to use the volt action {$args[0]}");
                }
            }
            else {
                $sender->sendMessage("Usage: /volt <action>");
            }
        }
        else{
            $sender->sendMessage("This server is running Volt.");
        }
    }

    /**
	 * @return Plugin|Volt
	 */
    public function getPlugin() : Plugin{
        return $this->main;
    }
}
