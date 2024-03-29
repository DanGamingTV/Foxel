<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>


use pocketmine\entity\Skin;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use function count;

class PlayerListPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_LIST_PACKET;

	/** @var int $protocol */
	public $protocol = ProtocolInfo::CURRENT_PROTOCOL;

	public const TYPE_ADD = 0;
	public const TYPE_REMOVE = 1;

	/** @var PlayerListEntry[] */
	public $entries = [];
	/** @var int */
	public $type;

	public function clean(){
		$this->entries = [];
		return parent::clean();
	}

	protected function decodePayload(){
		$this->type = $this->getByte();
		$count = $this->getUnsignedVarInt();
		for($i = 0; $i < $count; ++$i){
			$entry = new PlayerListEntry();
            $entry->uuid = $this->getUUID();
			if($this->type === self::TYPE_ADD){
				$entry->entityUniqueId = $this->getEntityUniqueId();
				$entry->username = $this->getString();
				// 1.12 skin
				$entry->xboxUserId = $this->getString();
				$entry->platformChatId = $this->getString();


				$this->getLInt();
				$entry->skin = $this->getSkin();
				$this->getBool();
				$this->getBool();
			}

			$this->entries[$i] = $entry;
		}
	}

    protected function encodePayload(){
        $this->putByte($this->type);
        $this->putUnsignedVarInt(count($this->entries));
        foreach($this->entries as $entry){
            $this->putUUID($entry->uuid);

            if($this->type === self::TYPE_ADD) {
                $this->putEntityUniqueId($entry->entityUniqueId);
                $this->putString($entry->username);

                if($this->protocol <= ProtocolInfo::PROTOCOL_1_12) {
                    $this->putString($entry->skin->getSkinId());
                    $this->putString($entry->skin->getSkinData()->data);
                    $this->putString($entry->skin->getCapeData()->data);
                    $this->putString($entry->skin->getSkinResourcePatch());
                    $this->putString($entry->skin->getGeometryData());
                }

                $this->putString($entry->xboxUserId);
                $this->putString($entry->platformChatId);

                if($this->protocol >= ProtocolInfo::PROTOCOL_1_13) {
                    $this->putLInt(-1);
                    $this->putSkin($entry->skin);
                    $this->putBool(false);
                    $this->putBool(false);
                }
            }
        }
    }

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerList($this);
	}
}
