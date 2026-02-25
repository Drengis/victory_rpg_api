import { create } from 'zustand';
import type { Character } from '../types/game';

interface GameState {
    currentCharacter: Character | null;
    setCurrentCharacter: (character: Character | null) => void;
    updateCharacterStats: (dynamic: Partial<Character['dynamic_stats']>) => void;
    updateCharacterData: (data: Partial<Character>) => void;
}

export const useGameStore = create<GameState>((set) => ({
    currentCharacter: null,
    setCurrentCharacter: (character) => set({ currentCharacter: character }),
    updateCharacterStats: (dynamic) => set((state) => {
        if (!state.currentCharacter || !state.currentCharacter.dynamic_stats) return state;
        return {
            currentCharacter: {
                ...state.currentCharacter,
                dynamic_stats: {
                    ...state.currentCharacter.dynamic_stats,
                    ...dynamic
                }
            }
        };
    }),
    updateCharacterData: (data) => set((state) => {
        if (!state.currentCharacter) return state;
        return {
            currentCharacter: {
                ...state.currentCharacter,
                ...data
            }
        };
    }),
}));
