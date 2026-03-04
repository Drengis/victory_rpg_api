export interface User {
    id: number;
    name: string;
    email: string;
}

export interface Character {
    id: number;
    user_id: number;
    name: string;
    class: 'Воин' | 'Лучник' | 'Маг';
    level: number;
    experience: number;
    gold: number;
    stat_points: number;
    items?: CharacterItem[];
    strength: number;
    agility: number;
    constitution: number;
    intelligence: number;
    luck: number;
    strength_added: number;
    agility_added: number;
    constitution_added: number;
    intelligence_added: number;
    luck_added: number;
    next_level_xp?: number;
    xp_percentage?: number;
    stats?: CharacterStats;
    dynamic_stats?: CharacterDynamicStats;
    dungeon_depth: number;
    max_dungeon_depth: number;
    calculated?: any; // Для полных расчетных данных с бэка
}

export interface Item {
    id: number;
    name: string;
    description?: string;
    type: 'weapon' | 'head' | 'chest' | 'hands' | 'legs' | 'feet' | 'neck' | 'ring' | 'belt' | 'trinket' | 'material' | 'junk' | 'consumable';
    required_class?: string;
    quality: number;
    base_price: number;
    min_damage?: number;
    max_damage?: number;
    armor?: number;
    strength?: number;
    agility?: number;
    constitution?: number;
    intelligence?: number;
    luck?: number;
    display_stats?: string[];
}

export interface Shop {
    id: number;
    name: string;
    description: string;
    items?: Item[];
}

export interface ShopItem extends Item {
    pivot: {
        price_override: number | null;
        ilevel: number;
    };
}

export interface CharacterItem {
    id: number;
    character_id: number;
    item_id: number;
    ilevel: number;
    quality: number | null;
    slot: string | null;
    is_equipped: boolean;
    quantity: number;
    item: Item;
}

export interface CharacterStats {
    max_hp: number;
    hp_regen: number;
    max_mp: number;
    mp_regen: number;
    physical_damage_bonus: number;
    magical_damage_bonus: number;
    accuracy: number;
    evasion: number;
    crit_chance: number;
    min_damage: number;
    max_damage: number;
    armor: number;
}

export interface CharacterDynamicStats {
    current_hp: number;
    current_mp: number;
    is_in_combat: boolean;
    barrier_hp: number;
    temp_armor?: number;
    temp_armor_duration?: number;
    temp_evasion?: number;
    temp_evasion_duration?: number;
    enemies_defeated_at_depth: number;
}

export interface Enemy {
    id: number;
    name: string;
    level: number;
    min_damage: number;
    max_damage: number;
}

export interface Combat {
    id: number;
    status: 'active' | 'won' | 'lost' | 'fled';
    current_turn: 'player' | 'enemies';
    turn_number: number;
    gold_reward: number;
    experience_reward: number;
    loot_reward: Record<string, number> | null;
    created_at: string;
    updated_at: string;
    participants?: CombatParticipant[];
    character?: Character;
}

export interface CombatParticipant {
    id: number;
    enemy_id: number;
    current_hp: number;
    max_hp?: number;
    max_mp?: number;
    level: number;
    position: number;
    enemy: Enemy;
}

export interface ClassAbility {
    id: number;
    class: string;
    level_required: number;
    ability_name: string;
    ability_type: 'attack' | 'defense' | 'buff' | 'utility' | 'passive';
    mp_cost: number;
    gold_cost: number;
    max_uses_per_combat: number | null;
    cooldown_turns: number;
    duration: number;
    effect_type: string;
    effect_formula: string;
    description: string;
    is_unlocked?: boolean;
}
