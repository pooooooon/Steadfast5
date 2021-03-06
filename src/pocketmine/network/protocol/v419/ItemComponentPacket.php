<?php

namespace pocketmine\network\protocol\v419;

use pocketmine\network\protocol\PEPacket;
use pocketmine\network\protocol\Info331;

class ItemComponentPacket extends PEPacket {

	const NETWORK_ID = Info331::ITEM_COMPONENT_PACKET;
	const PACKET_NAME = "ITEM_COMPONENT_PACKET";

	public $items = [];

	public function decode($playerProtocol) {

	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putVarInt(0); // send empty array
	}

}
