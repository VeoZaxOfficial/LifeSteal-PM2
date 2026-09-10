<?php
namespace LifeSteal\Manager;
class ProtectionRules
{
    const BLOCKED_TOOLS = [272,273,274,275];
    const BLOCKED_ITEMS = [259];
    const RESTRICTED_ITEM_TARGETS = [325 => [8,10]];
    public static function isBlockedInteraction(int $itemId):bool{
        return
        in_array($itemId,self::BLOCKED_ITEMS,true) ||
        in_array($itemId,self::BLOCKED_TOOLS,true) ||
        isset(self::RESTRICTED_ITEM_TARGETS[$itemId]);
    }
}
