import React, { useState, useEffect } from 'react';
import { useGameStore } from '../store/gameStore';
import api from '../api/axios';
import { useNavigate } from 'react-router-dom';
import type { Combat, ClassAbility } from '../types/game';
import StatBar from '../components/StatBar';
import { Sword, Shield, Heart, Droplets, Loader2, Skull, ScrollText, Timer, Star, Flame, Zap, Target, Sparkles } from 'lucide-react';
import { getAllAbilities, useAbility as useAbilityApi } from '../api/combatApi';

const CombatPage: React.FC = () => {
    const { currentCharacter, setCurrentCharacter } = useGameStore();
    const [combat, setCombat] = useState<Combat | null>(null);
    const [loading, setLoading] = useState(false);
    const [logs, setLogs] = useState<string[]>([]);
    const [turnLoading, setTurnLoading] = useState(false);
    const [abilities, setAbilities] = useState<ClassAbility[]>([]);
    const [selectedAbility, setSelectedAbility] = useState<ClassAbility | null>(null);
    const navigate = useNavigate();

    useEffect(() => {
        const loadAbilities = async () => {
            if (currentCharacter) {
                try {
                    const response = await getAllAbilities(currentCharacter.id);
                    // В бою показываем только разблокированные
                    setAbilities(response.data.filter((a: ClassAbility) => a.is_unlocked));
                } catch (err) {
                    console.error("Failed to load abilities", err);
                }
            }
        };

        const checkActiveCombat = async () => {
            if (currentCharacter?.dynamic_stats?.is_in_combat && !combat) {
                setLoading(true);
                try {
                    const response = await api.get(`/combat/active/${currentCharacter.id}`);
                    if (response.data.data) {
                        setCombat(response.data.data);
                        setLogs(['Бой возобновлен!']);
                    }
                } catch (err) {
                    console.error("Failed to fetch active combat", err);
                } finally {
                    setLoading(false);
                }
            }
        };

        checkActiveCombat();
        loadAbilities();

        // Редирект только если персонаж не в бою и нет данных о текущем/завершенном бое
        if (!currentCharacter?.dynamic_stats?.is_in_combat && !loading && !combat) {
            navigate('/dashboard');
        }
    }, [currentCharacter?.dynamic_stats?.is_in_combat, combat]);

    const handleAttack = async (targetId: number) => {
        if (!combat || combat.current_turn !== 'player' || turnLoading || combat.status !== 'active') return;

        setTurnLoading(true);
        try {
            const response = await api.post(`/combat/${combat.id}/attack`, {
                target_id: targetId
            });
            const result = response.data.data;

            if (result.logs && Array.isArray(result.logs)) {
                setLogs(prev => [...result.logs.reverse(), ...prev]);
            } else if (result.log) {
                setLogs(prev => [result.log, ...prev]);
            }
            if (result.combat) {
                setCombat(result.combat);
                if (result.combat.character) setCurrentCharacter(result.combat.character);
            }
        } catch (err: any) {
            setLogs(prev => [`❌ Ошибка: ${err.response?.data?.message || err.message}`, ...prev]);
        } finally {
            setTurnLoading(false);
        }
    };

    const handleUseAbility = async (ability: ClassAbility, targetId?: number) => {
        if (!combat || combat.current_turn !== 'player' || turnLoading || combat.status !== 'active') return;

        if (ability.ability_type === 'attack' && !targetId) {
            setSelectedAbility(ability);
            setLogs(prev => [`🎯 Выберите цель для способности "${ability.ability_name}"`, ...prev]);
            return;
        }

        setTurnLoading(true);
        setSelectedAbility(null);
        try {
            const response = await useAbilityApi(combat.id, ability.id, targetId);
            const result = response.data;

            if (result.logs) {
                setLogs(prev => [...result.logs.reverse(), ...prev]);
            }
            if (result.combat) {
                setCombat(result.combat);
                if (result.combat.character) setCurrentCharacter(result.combat.character);
            }
        } catch (err: any) {
            setLogs(prev => [`❌ Ошибка: ${err.response?.data?.message || err.message}`, ...prev]);
        } finally {
            setTurnLoading(false);
        }
    };


    const handleFlee = async () => {
        if (!combat || combat.current_turn !== 'player' || turnLoading || combat.status !== 'active') return;

        setTurnLoading(true);
        try {
            const response = await api.post(`/combat/${combat.id}/flee`);
            const result = response.data.data;

            if (result.logs) {
                setLogs(prev => [...result.logs.reverse(), ...prev]);
            }
            if (result.combat) {
                setCombat(result.combat);
                if (result.combat.character) setCurrentCharacter(result.combat.character);
            }
        } catch (err: any) {
            setLogs(prev => [`❌ Ошибка: ${err.response?.data?.message || err.message}`, ...prev]);
        } finally {
            setTurnLoading(false);
        }
    };

    if (!combat) {
        return (
            <div className="flex flex-col items-center justify-center min-h-[50vh] space-y-4">
                <Loader2 className="w-12 h-12 text-amber-500 animate-spin" />
                <p className="text-slate-400 font-bold uppercase tracking-widest animate-pulse">Поиск противника...</p>
            </div>
        );
    }

    // Используем данные персонажа из состояния боя, так как они обновляются в реальном времени
    const combatChar = combat.character || currentCharacter || ({} as any);
    const playerStats = combatChar.calculated || combatChar.stats || (currentCharacter?.stats || {});
    const playerDynamic = combatChar.dynamic_stats || (currentCharacter?.dynamic_stats || {});
    const activeEnemy = combat.participants?.[0];

    return (
        <div className="max-w-5xl mx-auto space-y-8 pb-20">
            {/* Battle Arena */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-12 items-center py-12 relative">
                <div className="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 hidden md:block">
                    <div className="w-20 h-20 bg-amber-500/10 rounded-full flex items-center justify-center border border-amber-500/20 blur-sm animate-pulse" />
                    <Sword className="w-12 h-12 text-amber-500/40 absolute left-4 top-4" />
                </div>

                {/* Player Card */}
                <div className="space-y-6">
                    <div className="flex items-center gap-4">
                        <div className="w-20 h-20 bg-slate-800 rounded-2xl flex items-center justify-center border-2 border-amber-500 shadow-lg shadow-amber-900/20">
                            <span className="text-3xl">{currentCharacter!.class === 'Воин' ? '⚔️' : currentCharacter!.class === 'Маг' ? '🔮' : '🏹'}</span>
                        </div>
                        <div>
                            <h3 className="text-2xl font-bold text-slate-100">{currentCharacter!.name}</h3>
                            <p className="text-amber-500 text-xs font-bold uppercase tracking-widest">{currentCharacter!.class}</p>
                        </div>
                    </div>
                    <div className="bg-slate-900 border border-slate-800 p-6 rounded-3xl space-y-4">
                        <StatBar label="HP" current={Math.round(playerDynamic?.current_hp || 0)} max={playerStats?.max_hp || 100} color="red" icon={<Heart className="w-3 h-3" />} />
                        <StatBar label="MP" current={Math.round(playerDynamic?.current_mp || 0)} max={playerStats?.max_mp || 50} color="blue" icon={<Droplets className="w-3 h-3" />} />
                    </div>

                    <div className="flex flex-col gap-4">
                        <button
                            disabled={combat.current_turn !== 'player' || turnLoading || combat.status !== 'active' || !!selectedAbility}
                            onClick={() => activeEnemy && handleAttack(activeEnemy.id as number)}
                            className="bg-amber-600 hover:bg-amber-500 disabled:opacity-30 disabled:cursor-not-allowed py-6 rounded-2xl font-bold text-slate-100 uppercase tracking-widest transition-all shadow-lg shadow-amber-900/20 flex items-center justify-center gap-3 active:scale-95 group"
                        >
                            {turnLoading && !selectedAbility ? <Loader2 className="w-6 h-6 animate-spin" /> : <Sword className="w-6 h-6 transition-transform group-hover:-translate-y-1" />}
                            <span>Атаковать</span>
                        </button>

                        <button
                            disabled={combat.current_turn !== 'player' || turnLoading || combat.status !== 'active'}
                            onClick={handleFlee}
                            className="bg-slate-900 border border-slate-800 hover:bg-slate-800 disabled:opacity-30 py-4 rounded-2xl font-bold text-slate-400 uppercase tracking-widest transition-all text-xs"
                        >
                            Сбежать
                        </button>

                        {/* Battle Stats Display - Player */}
                        <div className="bg-slate-900/40 border border-slate-800 p-3 rounded-2xl space-y-2 text-[10px] font-bold uppercase tracking-tighter">
                            <div className="grid grid-cols-2 gap-2">
                                <div className="px-2 py-1 bg-slate-800/50 rounded-lg">
                                    <div className="flex items-center gap-1 text-slate-500 mb-1">
                                        <Sword className="w-3 h-3" /> Урон
                                    </div>
                                    <div className="text-slate-200">
                                        {playerStats?.min_damage || 1} - {playerStats?.max_damage || 2}
                                    </div>
                                </div>
                                <div className="px-2 py-1 bg-slate-800/50 rounded-lg">
                                    <div className="flex items-center gap-1 text-slate-500 mb-1">
                                        <Shield className="w-3 h-3" /> Броня
                                    </div>
                                    <div className="text-slate-200">{playerStats?.armor || 0}</div>
                                </div>
                                <div className="px-2 py-1 bg-slate-800/50 rounded-lg">
                                    <div className="flex items-center gap-1 text-slate-500 mb-1">
                                        <Target className="w-3 h-3" /> Меткость
                                    </div>
                                    <div className="text-amber-500">+{(playerStats?.accuracy || 0).toFixed(0)}%</div>
                                </div>
                                <div className="px-2 py-1 bg-slate-800/50 rounded-lg">
                                    <div className="flex items-center gap-1 text-slate-500 mb-1">
                                        <Sparkles className="w-3 h-3" /> Уклонение
                                    </div>
                                    <div className="text-green-500">+{(playerStats?.evasion || 0).toFixed(0)}%</div>
                                </div>
                                <div className="px-2 py-1 bg-slate-800/50 rounded-lg">
                                    <div className="flex items-center gap-1 text-slate-500 mb-1">
                                        <Flame className="w-3 h-3" /> Крит
                                    </div>
                                    <div className="text-red-500">{(playerStats?.crit_chance || 0).toFixed(1)}%</div>
                                </div>
                                <div className="px-2 py-1 bg-slate-800/50 rounded-lg">
                                    <div className="flex items-center gap-1 text-slate-500 mb-1">
                                        <Heart className="w-3 h-3" /> Реген HP
                                    </div>
                                    <div className="text-red-400">+{(playerStats?.hp_regen || 0).toFixed(1)}/ход</div>
                                </div>
                            </div>
                            {/* Hit chances */}
                            <div className="flex gap-2 pt-2 border-t border-slate-800">
                                <div className="flex-1 px-2 py-1 bg-slate-800/30 rounded-lg">
                                    <div className="text-[8px] text-slate-500 mb-0.5">Ваш шанс попадания</div>
                                    <div className="text-amber-500">{Math.max(5, Math.min(95, 80 + (playerStats?.accuracy || 0) - ((activeEnemy as any)?.enemy_stats?.evasion || 0))).toFixed(0)}%</div>
                                </div>
                                <div className="flex-1 px-2 py-1 bg-slate-800/30 rounded-lg">
                                    <div className="text-[8px] text-slate-500 mb-0.5">Враг попадает в вас</div>
                                    <div className="text-red-400">{Math.max(5, Math.min(95, 80 + ((activeEnemy as any)?.enemy_stats?.accuracy || 0) - (playerStats?.evasion || 0))).toFixed(0)}%</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Abilities Section */}
                    <div className="bg-slate-900/50 border border-slate-800 p-4 rounded-3xl">
                        <h4 className="text-slate-500 text-[10px] font-bold uppercase tracking-widest mb-3 flex items-center gap-2">
                            <Star className="w-3 h-3" /> Способности
                        </h4>
                        <div className="grid grid-cols-2 gap-2">
                            {abilities.map((ability) => {
                                const isSelected = selectedAbility?.id === ability.id;
                                const canAfford = (playerDynamic?.current_mp ?? 0) >= ability.mp_cost;
                                return (
                                    <button
                                        key={ability.id}
                                        disabled={combat.current_turn !== 'player' || turnLoading || !canAfford || combat.status !== 'active'}
                                        onClick={() => isSelected ? setSelectedAbility(null) : handleUseAbility(ability)}
                                        className={`p-3 rounded-xl border transition-all text-left relative group ${isSelected
                                            ? 'bg-amber-500/20 border-amber-500 shadow-lg shadow-amber-500/10'
                                            : 'bg-slate-800/50 border-slate-700 hover:border-slate-500'
                                            } disabled:opacity-30`}
                                        title={ability.description}
                                    >
                                        <div className="flex justify-between items-start mb-1">
                                            <span className={`text-[10px] font-bold truncate ${isSelected ? 'text-amber-400' : 'text-slate-200'}`}>
                                                {ability.ability_name}
                                            </span>
                                            {ability.ability_type === 'attack' ? <Flame className="w-3 h-3 text-red-500" /> : <Shield className="w-3 h-3 text-cyan-500" />}
                                        </div>
                                        <div className="flex justify-between items-center">
                                            <div className="flex items-center gap-1">
                                                <Droplets className="w-2 h-2 text-blue-500" />
                                                <span className="text-[10px] text-blue-500 font-bold">{ability.mp_cost}</span>
                                            </div>
                                            {ability.duration > 1 && (
                                                <div className="flex items-center gap-1">
                                                    <Timer className="w-2 h-2 text-slate-500" />
                                                    <span className="text-[10px] text-slate-500 font-bold">{ability.duration}т</span>
                                                </div>
                                            )}
                                        </div>

                                        {/* Hover Tooltip - Simplified for now */}
                                        <div className="absolute bottom-full left-0 mb-2 w-48 p-2 bg-slate-900 border border-slate-700 rounded-lg text-[10px] text-slate-300 hidden group-hover:block z-50 pointer-events-none">
                                            {ability.description}
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                        {selectedAbility && (
                            <div className="mt-4 flex items-center justify-between bg-amber-500/10 border border-amber-500/20 rounded-xl p-2 px-3">
                                <span className="text-[10px] text-amber-500 font-bold uppercase flex items-center gap-2">
                                    <Zap className="w-3 h-3 animate-pulse" /> Выберите цель
                                </span>
                                <button
                                    onClick={() => setSelectedAbility(null)}
                                    className="text-[10px] text-slate-500 hover:text-white underline font-bold"
                                >
                                    Отмена
                                </button>
                            </div>
                        )}
                    </div>
                </div>

                {/* Enemy Card */}
                {activeEnemy ? (
                    <div
                        className={`space-y-6 text-right md:text-left transition-all ${selectedAbility ? 'scale-105 ring-2 ring-amber-500 ring-offset-8 ring-offset-slate-950 rounded-3xl cursor-crosshair' : ''}`}
                        onClick={() => selectedAbility && handleUseAbility(selectedAbility, activeEnemy!.id as number)}
                    >
                        <div className="flex flex-row-reverse md:flex-row items-center gap-4">
                            <div className="w-20 h-20 bg-slate-800 rounded-2xl flex items-center justify-center border-2 border-red-500 shadow-lg shadow-red-900/20">
                                <Skull className="w-10 h-10 text-red-500" />
                            </div>
                            <div>
                                <h3 className="text-2xl font-bold text-slate-100">{activeEnemy.enemy.name}</h3>
                                <p className="text-red-500 text-xs font-bold uppercase tracking-widest">Уровень {activeEnemy.level}</p>
                            </div>
                        </div>
                        <div className="bg-slate-900 border border-slate-800 p-6 rounded-3xl space-y-4">
                            <StatBar label="HP" current={activeEnemy.current_hp} max={activeEnemy.max_hp || 1} color="red" icon={<Heart className="w-3 h-3" />} />
                            <div className="h-3 bg-transparent hidden md:block" /> {/* Spacer */}
                        </div>

                        {/* Enemy Stats */}
                        <div className="bg-slate-900/40 border border-red-500/20 p-3 rounded-2xl space-y-2 text-[10px] font-bold uppercase tracking-tighter">
                            <div className="grid grid-cols-2 gap-2">
                                <div className="px-2 py-1 bg-slate-800/50 rounded-lg">
                                    <div className="flex items-center gap-1 text-slate-500 mb-1">
                                        <Sword className="w-3 h-3" /> Урон
                                    </div>
                                    <div className="text-slate-200">
                                        {(activeEnemy as any).enemy_stats?.min_damage || 1} - {(activeEnemy as any).enemy_stats?.max_damage || 2}
                                    </div>
                                </div>
                                <div className="px-2 py-1 bg-slate-800/50 rounded-lg">
                                    <div className="flex items-center gap-1 text-slate-500 mb-1">
                                        <Shield className="w-3 h-3" /> Броня
                                    </div>
                                    <div className="text-slate-200">{(activeEnemy as any).enemy_stats?.armor || 0}</div>
                                </div>
                                <div className="px-2 py-1 bg-slate-800/50 rounded-lg">
                                    <div className="flex items-center gap-1 text-slate-500 mb-1">
                                        <Target className="w-3 h-3" /> Меткость
                                    </div>
                                    <div className="text-red-400">+{((activeEnemy as any).enemy_stats?.accuracy || 0).toFixed(0)}%</div>
                                </div>
                                <div className="px-2 py-1 bg-slate-800/50 rounded-lg">
                                    <div className="flex items-center gap-1 text-slate-500 mb-1">
                                        <Sparkles className="w-3 h-3" /> Уклонение
                                    </div>
                                    <div className="text-green-500">+{((activeEnemy as any).enemy_stats?.evasion || 0).toFixed(0)}%</div>
                                </div>
                                <div className="px-2 py-1 bg-slate-800/50 rounded-lg">
                                    <div className="flex items-center gap-1 text-slate-500 mb-1">
                                        <Flame className="w-3 h-3" /> Крит
                                    </div>
                                    <div className="text-red-500">{((activeEnemy as any).enemy_stats?.crit_chance || 0).toFixed(1)}%</div>
                                </div>
                                <div className="px-2 py-1 bg-slate-800/50 rounded-lg">
                                    <div className="flex items-center gap-1 text-slate-500 mb-1">
                                        <Heart className="w-3 h-3" /> Реген HP
                                    </div>
                                    <div className="text-red-400">+{((activeEnemy as any).enemy_stats?.hp_regen || 0).toFixed(1)}/ход</div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-red-500/5 border border-red-500/10 p-4 rounded-2xl flex items-center gap-4 justify-end md:justify-start">
                            <Shield className="w-5 h-5 text-red-500/50" />
                            <span className="text-slate-400 text-sm italic">Монстр готовится к атаке...</span>
                        </div>
                    </div>
                ) : (
                    <div className="flex flex-col items-center justify-center p-12 bg-slate-900/50 rounded-3xl border-2 border-dashed border-slate-800">
                        <Skull className="w-16 h-16 text-slate-800 mb-4" />
                        <p className="text-slate-500 font-bold uppercase tracking-widest">Противник повержен</p>
                    </div>
                )}
            </div>

            {/* Combat Log */}
            <div className="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 backdrop-blur-sm max-w-2xl mx-auto shadow-2xl">
                <div className="flex items-center justify-between mb-4 border-b border-slate-800 pb-4">
                    <h4 className="flex items-center gap-2 text-slate-400 font-bold uppercase tracking-widest text-xs">
                        <ScrollText className="w-4 h-4" /> Журнал боя
                    </h4>
                    <div className="flex items-center gap-2 text-slate-500 text-xs italic">
                        <Timer className="w-3 h-3" /> Раунд {combat?.turn_number || 1}
                    </div>
                </div>
                <div className="h-48 overflow-y-auto space-y-3 pr-4 custom-scrollbar">
                    {logs.map((log, i) => (
                        <div key={i} className={`text-sm ${i === 0 ? 'text-slate-100 font-medium' : 'text-slate-500'}`}>
                            {log}
                        </div>
                    ))}
                </div>
            </div>

            {/* Battle Overlay (Victory/Defeat) */}
            {combat.status !== 'active' && (
                <div className="fixed inset-0 bg-black/80 backdrop-blur-md z-[100] flex items-center justify-center p-4">
                    <div className="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-10 text-center shadow-[0_0_50px_rgba(0,0,0,0.5)]">
                        {combat.status === 'won' ? (
                            <>
                                <div className="w-24 h-24 bg-amber-500/20 rounded-full flex items-center justify-center mx-auto mb-6 border border-amber-500/50 shadow-[0_0_30px_rgba(251,191,36,0.2)]">
                                    <Star className="w-12 h-12 text-amber-500 fill-amber-500" />
                                </div>
                                <h2 className="text-4xl font-black text-white mb-2 tracking-tight">ПОБЕДА!</h2>
                                <p className="text-slate-400 mb-6 text-sm">Вы одолели врага и получили награду:</p>

                                <div className="bg-slate-950/50 border border-slate-800 rounded-2xl p-6 mb-8 text-left space-y-3">
                                    <div className="flex justify-between items-center">
                                        <span className="text-slate-500 text-xs font-bold uppercase">Опыт</span>
                                        <span className="text-amber-500 font-bold">+{combat.experience_reward} XP</span>
                                    </div>
                                    <div className="flex justify-between items-center">
                                        <span className="text-slate-500 text-xs font-bold uppercase">Золото</span>
                                        <span className="text-yellow-500 font-bold">+{combat.gold_reward} G</span>
                                    </div>

                                    {combat.loot_reward && Object.keys(combat.loot_reward).length > 0 && (
                                        <div className="pt-3 border-t border-slate-800">
                                            <span className="text-slate-500 text-xs font-bold uppercase block mb-2">Добыча:</span>
                                            <div className="space-y-2">
                                                {Object.entries(combat.loot_reward).map(([name, qty]) => (
                                                    <div key={name} className="flex justify-between items-center text-sm">
                                                        <span className="text-slate-200">{name}</span>
                                                        <span className="text-slate-400">x{qty}</span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </>
                        ) : (
                            <>
                                <div className="w-24 h-24 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-6 border border-red-500/50 shadow-[0_0_30px_rgba(239,68,68,0.2)]">
                                    <Skull className="w-12 h-12 text-red-500" />
                                </div>
                                <h2 className="text-4xl font-black text-white mb-2 tracking-tight">ПОРАЖЕНИЕ</h2>
                                <p className="text-slate-400 mb-8">Ваш путь прервался в этом бою. Нужно подлечиться и набраться сил...</p>
                            </>
                        )}

                        <button
                            onClick={() => navigate('/dashboard')}
                            className={`w-full py-4 rounded-2xl font-bold uppercase tracking-widest transition-all ${combat.status as string === 'won'
                                ? 'bg-amber-600 hover:bg-amber-500 text-white shadow-lg shadow-amber-900/20'
                                : 'bg-slate-700 hover:bg-slate-600 text-slate-200'
                                }`}
                        >
                            Вернуться в замок
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
};

export default CombatPage;
