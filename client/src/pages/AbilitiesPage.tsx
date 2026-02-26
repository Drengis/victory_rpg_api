import React, { useState, useEffect } from 'react';
import { useGameStore } from '../store/gameStore';
import { getAllAbilities, unlockAbility } from '../api/combatApi';
import type { ClassAbility } from '../types/game';
import { Star, Lock, Unlock, Zap, Flame, Shield, Droplets, Coins, Loader2, Info, Timer } from 'lucide-react';

const AbilitiesPage: React.FC = () => {
    const { currentCharacter, setCurrentCharacter } = useGameStore();
    const [abilities, setAbilities] = useState<ClassAbility[]>([]);
    const [loading, setLoading] = useState(true);
    const [unlockingId, setUnlockingId] = useState<number | null>(null);
    const [message, setMessage] = useState<{ text: string, type: 'success' | 'error' } | null>(null);

    const loadAbilities = async () => {
        if (!currentCharacter) return;
        try {
            const response = await getAllAbilities(currentCharacter.id);
            setAbilities(response.data);
        } catch (err) {
            console.error("Failed to load abilities", err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadAbilities();
    }, [currentCharacter?.id]);

    const handleUnlock = async (ability: ClassAbility) => {
        if (!currentCharacter || unlockingId) return;

        if (currentCharacter.gold < ability.gold_cost) {
            setMessage({ text: 'Недостаточно золота!', type: 'error' });
            return;
        }

        if (currentCharacter.level < ability.level_required) {
            setMessage({ text: `Требуется уровень ${ability.level_required}!`, type: 'error' });
            return;
        }

        setUnlockingId(ability.id);
        setMessage(null);

        try {
            const response = await unlockAbility(currentCharacter.id, ability.id);
            if (response.success) {
                setMessage({ text: response.message, type: 'success' });
                if (response.data.character) {
                    setCurrentCharacter(response.data.character);
                }
                // Обновляем список локально
                setAbilities(prev => prev.map(a =>
                    a.id === ability.id ? { ...a, is_unlocked: true } : a
                ));
            }
        } catch (err: any) {
            setMessage({ text: err.response?.data?.message || 'Ошибка при изучении навыка', type: 'error' });
        } finally {
            setUnlockingId(null);
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center min-h-[50vh]">
                <Loader2 className="w-10 h-10 text-amber-500 animate-spin" />
            </div>
        );
    }

    return (
        <div className="max-w-4xl mx-auto space-y-8 pb-20">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-3xl font-black text-white tracking-tight flex items-center gap-3">
                        <Star className="text-amber-500" /> УМЕНИЯ И НАВЫКИ
                    </h2>
                    <p className="text-slate-400 text-sm">Изучайте новые способности, чтобы стать сильнее в бою</p>
                </div>
                <div className="bg-slate-900/80 border border-slate-800 px-6 py-3 rounded-2xl flex items-center gap-3">
                    <Coins className="text-yellow-500 w-5 h-5" />
                    <span className="text-xl font-bold text-white">{currentCharacter?.gold}</span>
                </div>
            </div>

            {message && (
                <div className={`p-4 rounded-2xl border ${message.type === 'success'
                    ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-500'
                    : 'bg-red-500/10 border-red-500/20 text-red-500'
                    } font-bold text-sm tracking-wide animate-in fade-in slide-in-from-top-4`}>
                    {message.text}
                </div>
            )}

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {abilities.map((ability) => (
                    <div
                        key={ability.id}
                        className={`group relative bg-slate-900 border transition-all rounded-3xl p-6 ${ability.is_unlocked
                            ? 'border-slate-800 hover:border-slate-700'
                            : 'border-slate-800/50 opacity-90'
                            }`}
                    >
                        <div className="flex justify-between items-start mb-4">
                            <div className="flex items-center gap-4">
                                <div className={`w-14 h-14 rounded-2xl flex items-center justify-center border-2 ${ability.is_unlocked ? 'bg-slate-800 border-amber-500 shadow-lg shadow-amber-900/20' : 'bg-slate-950 border-slate-800'
                                    }`}>
                                    {ability.ability_type === 'attack' ? (
                                        <Flame className={`w-7 h-7 ${ability.is_unlocked ? 'text-red-500' : 'text-slate-700'}`} />
                                    ) : (
                                        <Shield className={`w-7 h-7 ${ability.is_unlocked ? 'text-cyan-500' : 'text-slate-700'}`} />
                                    )}
                                </div>
                                <div>
                                    <h4 className={`font-bold text-lg ${ability.is_unlocked ? 'text-white' : 'text-slate-500'}`}>
                                        {ability.ability_name}
                                    </h4>
                                    <div className="flex items-center gap-3 mt-1 text-[10px] font-bold uppercase tracking-wider">
                                        <span className="flex items-center gap-1 text-blue-500">
                                            <Droplets className="w-3 h-3" /> {ability.mp_cost} MP
                                        </span>
                                        {ability.duration > 1 && (
                                            <span className="flex items-center gap-1 text-slate-500">
                                                <Timer className="w-3 h-3" /> {ability.duration} ходов
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </div>
                            {ability.is_unlocked ? (
                                <div className="bg-emerald-500/10 text-emerald-500 p-2 rounded-xl border border-emerald-500/20">
                                    <Unlock className="w-4 h-4" />
                                </div>
                            ) : (
                                <div className="bg-slate-950 text-slate-700 p-2 rounded-xl border border-slate-800">
                                    <Lock className="w-4 h-4" />
                                </div>
                            )}
                        </div>

                        <p className={`text-sm mb-6 ${ability.is_unlocked ? 'text-slate-400' : 'text-slate-600'}`}>
                            {ability.description}
                        </p>

                        {!ability.is_unlocked && (
                            <div className="flex items-center gap-4 pt-4 border-t border-slate-800/50">
                                <div className="flex-1">
                                    <div className="flex items-center gap-2 mb-1">
                                        <Coins className="w-3 h-3 text-yellow-500" />
                                        <span className={`text-xs font-bold ${currentCharacter!.gold >= ability.gold_cost ? 'text-white' : 'text-red-500'}`}>
                                            {ability.gold_cost} Золота
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Zap className="w-3 h-3 text-amber-500" />
                                        <span className={`text-xs font-bold ${currentCharacter!.level >= ability.level_required ? 'text-slate-400' : 'text-red-500'}`}>
                                            Уровень {ability.level_required}
                                        </span>
                                    </div>
                                </div>
                                <button
                                    onClick={() => handleUnlock(ability)}
                                    disabled={unlockingId !== null || currentCharacter!.gold < ability.gold_cost || currentCharacter!.level < ability.level_required}
                                    className="px-6 py-3 bg-amber-600 hover:bg-amber-500 disabled:opacity-30 disabled:cursor-not-allowed rounded-xl text-white text-xs font-bold uppercase tracking-widest transition-all active:scale-95 shadow-lg shadow-amber-900/20"
                                >
                                    {unlockingId === ability.id ? <Loader2 className="w-4 h-4 animate-spin mx-auto" /> : 'Изучить'}
                                </button>
                            </div>
                        )}

                        {ability.is_unlocked && (
                            <div className="text-[10px] text-emerald-500/60 font-medium uppercase tracking-[0.2em] flex items-center gap-2">
                                <div className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                                Навык изучен и доступен в бою
                            </div>
                        )}
                    </div>
                ))}
            </div>

            {/* Helpful Note */}
            <div className="bg-amber-500/5 border border-amber-500/10 p-6 rounded-3xl flex items-start gap-4">
                <Info className="w-6 h-6 text-amber-500 mt-0.5" />
                <div className="space-y-1">
                    <h5 className="text-amber-500 font-bold text-sm uppercase tracking-wide">Подсказка</h5>
                    <p className="text-slate-400 text-sm leading-relaxed">
                        Некоторые навыки имеют длительность более одного хода. Защитные стойки воина и лучника теперь действуют 2 хода,
                        что позволяет атаковать на следующем ходу, сохраняя бонус к защите!
                    </p>
                </div>
            </div>
        </div>
    );
};

export default AbilitiesPage;
