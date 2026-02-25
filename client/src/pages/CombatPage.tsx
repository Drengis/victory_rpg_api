import React, { useState, useEffect } from 'react';
import { useGameStore } from '../store/gameStore';
import api from '../api/axios';
import { Navigate, useNavigate } from 'react-router-dom';
import type { Combat } from '../types/game';
import StatBar from '../components/StatBar';
import { Sword, Shield, Heart, Droplets, Loader2, Skull, ScrollText, Timer, Star } from 'lucide-react';

const CombatPage: React.FC = () => {
    const { currentCharacter } = useGameStore();
    const [combat, setCombat] = useState<Combat | null>(null);
    const [loading, setLoading] = useState(false);
    const [enemies, setEnemies] = useState<any[]>([]);
    const [logs, setLogs] = useState<string[]>([]);
    const [turnLoading, setTurnLoading] = useState(false);
    const navigate = useNavigate();

    useEffect(() => {
        const checkActiveCombat = async () => {
            if (currentCharacter?.dynamic_stats?.is_in_combat) {
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

        const fetchEnemies = async () => {
            try {
                const response = await api.get('/enemies?paginate=false');
                setEnemies(response.data.data || []);
            } catch (err) {
                console.error("Failed to fetch enemies");
            }
        };

        checkActiveCombat();
        fetchEnemies();
    }, []);

    if (!currentCharacter) return <Navigate to="/characters" />;

    const startCombat = async (enemyId: number) => {
        setLoading(true);
        try {
            const response = await api.post('/combat/start', {
                character_id: currentCharacter.id,
                enemy_ids: [enemyId]
            });
            const combatData = response.data.data;
            setCombat(combatData);

            // Если враги ходили первыми — показать их действия в логе
            const initialLogs: string[] = ['⚔️ Бой начался!'];
            if (combatData.character?.dynamic_stats?.last_combat_log) {
                const enemyLogs = combatData.character.dynamic_stats.last_combat_log.split('\n').filter(Boolean);
                initialLogs.push(...enemyLogs);
            }
            setLogs(initialLogs);
        } catch (err: any) {
            alert(err.response?.data?.message || 'Ошибка начала боя');
        } finally {
            setLoading(false);
        }
    };

    const handleAttack = async (targetId: number) => {
        if (!combat || combat.current_turn !== 'player' || turnLoading) return;

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
            if (result.status !== 'active') {
                // Больше не делаем авто-редирект, чтобы игрок мог посмотреть лут
                // setTimeout(() => navigate('/dashboard'), 3000);
            }

            // Обновляем состояние (это упрощенно, в идеале бэк должен отдавать всё сразу)
            // Здесь я просто перезагружу состояние боя если оно активно
            if (result.status === 'active') {
                const refreshed = await api.get(`/combat/${combat.id}`);
                setCombat(refreshed.data.data);
            } else {
                // Если бой завершен (победа/поражение), запрашиваем финальное состояние с наградами
                const finalState = await api.get(`/combat/${combat.id}`);
                setCombat(finalState.data.data);
            }

        } catch (err: any) {
            alert(err.message);
        } finally {
            setTurnLoading(false);
        }
    };

    const handleDefense = async () => {
        if (!combat || combat.current_turn !== 'player' || turnLoading || combat.status !== 'active') return;

        setTurnLoading(true);
        try {
            const response = await api.post(`/combat/${combat.id}/defense`);
            const result = response.data.data;

            if (result.logs && Array.isArray(result.logs)) {
                setLogs(prev => [...result.logs.reverse(), ...prev]);
            }

            // Запрашиваем состояние боя
            const refreshed = await api.get(`/combat/${combat.id}`);
            setCombat(refreshed.data.data);
        } catch (err: any) {
            alert(err.response?.data?.message || err.message);
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

            if (result.logs && Array.isArray(result.logs)) {
                setLogs(prev => [...result.logs.reverse(), ...prev]);
            }

            if (result.status !== 'active') {
                const finalState = await api.get(`/combat/${combat.id}`);
                setCombat(finalState.data.data);
            } else {
                const refreshed = await api.get(`/combat/${combat.id}`);
                setCombat(refreshed.data.data);
            }
        } catch (err: any) {
            alert(err.response?.data?.message || err.message);
        } finally {
            setTurnLoading(false);
        }
    };

    if (!combat) {
        return (
            <div className="max-w-2xl mx-auto py-12">
                <h2 className="text-3xl font-bold text-slate-100 mb-8 text-center">Выберите противника</h2>
                {loading ? (
                    <div className="flex justify-center py-12"><Loader2 className="w-12 h-12 text-amber-500 animate-spin" /></div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {enemies.map(enemy => (
                            <div key={enemy.id} className="bg-slate-900 border border-slate-800 p-6 rounded-3xl hover:border-amber-500/50 transition-all overflow-hidden relative group">
                                <div className="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition-opacity">
                                    <Skull className="w-32 h-32" />
                                </div>
                                <h3 className="text-xl font-bold text-slate-100 mb-1">{enemy.name}</h3>
                                <p className="text-slate-500 text-sm mb-4 italic">Уровень {enemy.level}</p>
                                <button
                                    onClick={() => startCombat(enemy.id)}
                                    className="w-full py-3 bg-slate-800 hover:bg-amber-600 text-slate-100 font-bold rounded-xl transition-all"
                                >
                                    Атаковать
                                </button>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        );
    }

    // Используем данные персонажа из состояния боя, так как они обновляются в реальном времени
    const combatChar = combat?.character || currentCharacter;
    const playerStats = combatChar.calculated || combatChar.stats || currentCharacter.stats;
    const playerDynamic = combatChar.dynamic_stats || currentCharacter.dynamic_stats;
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
                            <span className="text-3xl">{currentCharacter.class === 'Воин' ? '⚔️' : currentCharacter.class === 'Маг' ? '🔮' : '🏹'}</span>
                        </div>
                        <div>
                            <h3 className="text-2xl font-bold text-slate-100">{currentCharacter.name}</h3>
                            <p className="text-amber-500 text-xs font-bold uppercase tracking-widest">{currentCharacter.class}</p>
                        </div>
                    </div>
                    <div className="bg-slate-900 border border-slate-800 p-6 rounded-3xl space-y-4">
                        <StatBar label="HP" current={Math.round(playerDynamic?.current_hp || 0)} max={playerStats?.max_hp || 100} color="red" icon={<Heart className="w-3 h-3" />} />
                        <StatBar label="MP" current={Math.round(playerDynamic?.current_mp || 0)} max={playerStats?.max_mp || 50} color="blue" icon={<Droplets className="w-3 h-3" />} />
                    </div>

                    <div className="grid grid-cols-3 gap-2">
                        {['Атака', 'Защита', 'Побег'].map((act, i) => (
                            <button
                                key={act}
                                disabled={combat.current_turn !== 'player' || turnLoading || combat.status !== 'active'}
                                onClick={() => {
                                    if (i === 0 && activeEnemy) handleAttack(activeEnemy.id);
                                    if (i === 1) handleDefense();
                                    if (i === 2) handleFlee();
                                }}
                                className={`py-4 rounded-2xl font-bold text-xs uppercase tracking-widest transition-all ${i === 0 ? 'bg-amber-600 hover:bg-amber-500 text-white shadow-lg shadow-amber-900/20' : 'bg-slate-800 hover:bg-slate-700 text-slate-400'
                                    } disabled:opacity-30 disabled:cursor-not-allowed`}
                            >
                                {turnLoading && i === 0 ? <Loader2 className="w-4 h-4 animate-spin mx-auto" /> : act}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Enemy Card */}
                {activeEnemy ? (
                    <div className="space-y-6 text-right md:text-left">
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
                            <StatBar label="HP" current={activeEnemy.current_hp} max={activeEnemy.max_hp || 100} color="red" icon={<Heart className="w-3 h-3" />} />
                            <div className="h-3 bg-transparent hidden md:block" /> {/* Spacer */}
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
