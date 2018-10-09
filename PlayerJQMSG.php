<?php
/**
 * @name PlayerJQMSG
 * @author alvin0319
 * @main alvin0319\PlayerJQMSG
 * @version 1.0.0
 * @api 4.0.0
 */
namespace alvin0319;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\utils\Config;
use pocketmine\command\{
Command, CommandSender, PluginCommand
};
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\{
ModalFormRequestPacket, ModalFormResponsePacket
};
use pocketmine\Player;

//한글깨짐방지
class PlayerJQMSG extends PluginBase implements Listener{
	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		@mkdir($this->getDataFolder());
		$this->config = new Config($this->getDataFolder() . "PlayerDB.yml", Config::YAML);
		$this->db = $this->config->getAll();
		$this->settings = new Config($this->getDataFolder() . "Settings.yml", Config::YAML, [
		"prefix" => "§b§l[ §f서버§b ] §r"
		]);
		$this->set = $this->settings->getAll();
		$cmd = new PluginCommand("닉네임등록", $this);
		$cmd->setDescription(".");
		$this->getServer()->getCommandMap()->register("닉네임등록", $cmd);
	}
	public function onJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer();
		$name = strtolower($player->getName());
		$event->setJoinMessage(false);
		if (isset($this->db[$name])) {
			$a = explode(":", $this->db[$name]);
			$msg = str_replace(["(닉네임)"], [$name], $a[0]);
			$this->joinMsg($msg);
		}
	}
	public function onQuit(PlayerQuitEvent $event) {
		$player = $event->getPlayer();
		$name = strtolower($player->getName());
		$event->setQuitMessage(false);
		if (isset($this->db[$name])) {
			$a = explode(":", $this->db[$name]);
			$msg = str_replace(["(닉네임)"], [$name], $a[1]);
			$this->quitMsg($msg);
		}
	}
	public function joinMsg($info) {
		$this->getServer()->broadcastMessage($this->set["prefix"] . $info);
	}
	public function quitMsg($info) {
		$this->getServer()->broadcastMessage($this->set["prefix"] . $info);
	}
	public function sendUI(Player $player, $code, $data) {
		$pk = new ModalFormRequestPacket();
		$pk->formId = $code;
		$pk->formData = $data;
		$player->dataPacket($pk);
	}
	public function onDataPacket(DataPacketReceiveEvent $event) {
		$packet = $event->getPacket();
		$player = $event->getPlayer();
		if ($packet instanceof ModalFormResponsePacket) {
			$id = $packet->formId;
			$data = json_decode($packet->formData, true);
			if ($id === 3123) {
				if (! isset($data[0]) or ! isset($data[1]) or ! isset($data[2])) {
					$player->sendMessage($this->set["prefix"] . "모든 칸을 정확히 입력해주세요");
					return;
				}
				$this->db[strtolower($data[0])] = $data[1] . ":" . $data[2];
				$this->save();
				$player->sendMessage($this->set["prefix"] . "저장되었습니다");
			}
		}
	}
	public function MainData() {
		$encode = [
		"type" => "custom_form",
		"title" => "JQMSG",
		"content" => [
		[
		"type" => "input",
		"text" => "닉네임을 입력해주세요",
		],
		[
		"type" => "input",
		"text" => "접속시 나올 말을 입력해주세요\n(닉네임) 으로 플레이어의 닉네임을 표시할수 있습니다",
		],
		[
		"type" => "input",
		"text" => "퇴장시 나올 말을 입력해주세요"
		]
		]
		];
		return json_encode($encode);
	}
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if ($command->getName() === "닉네임등록") {
			if (! $sender->isOp()) {
				return true;
			} else {
				$this->sendUI($sender, 3123, $this->Maindata());
			}
		}
		return true;
	}
	public function save() {
		$this->config->setAll($this->db);
		$this->config->save();
	}
}