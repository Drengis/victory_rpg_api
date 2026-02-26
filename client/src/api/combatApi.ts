import axios from './axios';

export const startCombat = async (characterId: number, enemyIds?: number[]) => {
    const response = await axios.post('/combat/start', {
        character_id: characterId,
        enemy_ids: enemyIds || null
    });
    return response.data;
};

export const goDeeper = async (characterId: number) => {
    const response = await axios.post('/combat/go-deeper', {
        character_id: characterId
    });
    return response.data;
};

export const changeDepth = async (characterId: number, depth: number) => {
    const response = await axios.post('/combat/change-depth', {
        character_id: characterId,
        depth: depth
    });
    return response.data;
};

export const getActiveCombat = async (characterId: number) => {
    const response = await axios.get(`/combat/active/${characterId}`);
    return response.data;
};

export const useAbility = async (combatId: number, abilityId: number, targetId?: number) => {
    const response = await axios.post(`/combat/${combatId}/ability`, {
        ability_id: abilityId,
        target_id: targetId
    });
    return response.data;
};

export const getAllAbilities = async (characterId: number) => {
    const response = await axios.get(`/characters/${characterId}/all-abilities`);
    return response.data;
};

export const unlockAbility = async (characterId: number, abilityId: number) => {
    const response = await axios.post(`/characters/${characterId}/unlock-ability`, {
        ability_id: abilityId
    });
    return response.data;
};
