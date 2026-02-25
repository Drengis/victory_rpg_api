import React, { useState, useEffect } from 'react';
import { useGameStore } from '../store/gameStore';
import api from '../api/axios';
import { Navigate, useNavigate } from 'react-router-dom';
import StatBar from '../components/StatBar';
import { Heart, Droplets, Shield, Target, Coins, Skull, Package, Search, Loader2, Plus, Sparkles } from 'lucide-react';
import { formatNumber } from '../lib/utils';

const DashboardPage: React.FC = () => {
    const { currentCharacter, setCurrentCharacter } = useGameStore();
    const navigate = useNavigate();
    const [showDetails, setShowDetails] = useState(false);
    const [syncing, setSyncing] = useState(false);
    const [distributing, setDistributing] = useState<string | null>(null);

    useEffect(() => {
        const syncData = async () => {
            if (!currentCharacter) return;
            setSyncing(true);
            try {
                const response = await api.get(`/characters/${currentCharacter.id}`);
                if (response.data.data) {
                    setCurrentCharacter(response.data.data);
                }
            } catch (err) {
                console.error("Failed to sync character data", err);
            } finally {
                setSyncing(false);
            }
        };

        syncData();
    }, []);

    if (!currentCharacter) {
        return <Navigate to="/characters" />;
    }

    const char = currentCharacter;

    // Автоматический возврат в бой, если персонаж в нём застрял
    if (char.dynamic_stats?.is_in_combat) {
        return <Navigate to="/combat" />;
    }

    // Используем расчетные статы (с учетом всех бонусов), сохраненные статы или заглушки
    const stats = char.calculated || char.stats || {
        max_hp: char.constitution * 10,
        max_mp: char.intelligence * 10,
        min_damage: 1,
        max_damage: 2,
        armor: 0,
        accuracy: 0,
        evasion: 0,
        crit_chance: 0,
        hp_regen: 0,
        mp_regen: 0
    };

    const dynamic = char.dynamic_stats || {
        current_hp: stats.max_hp,
        current_mp: stats.max_mp
    };

    const statPoints = char.stat_points || 0;

    const handleDistributeStat = async (stat: string) => {
        if (statPoints <= 0 || distributing) return;
        setDistributing(stat);
        try {
            const response = await api.post(`/characters/${char.id}/distribute-stat`, { stat });
            if (response.data.data) {
                setCurrentCharacter(response.data.data);
            }
        } catch (err: any) {
            alert(err.response?.data?.message || 'Ошибка распределения очков');
        } finally {
            setDistributing(null);
        }
    };

    return (
        <div className="max-w-6xl mx-auto space-y-8">
            {/* Stat Points Banner */}
            {statPoints > 0 && (
                <div className="bg-amber-900/30 border border-amber-700/50 rounded-2xl p-4 flex items-center gap-4 animate-pulse">
                    <div className="w-10 h-10 bg-amber-600 rounded-xl flex items-center justify-center shadow-lg shadow-amber-900/40">
                        <Sparkles className="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p className="text-amber-200 font-bold text-sm">У вас есть свободные очки характеристик!</p>
                        <p className="text-amber-400/70 text-xs">Доступно: <span className="font-bold text-amber-300">{statPoints}</span> очков. Распределите их в блоке «Характеристики» ниже.</p>
                    </div>
                </div>
            )}

            {/* Header / Summary */}
            <div className="bg-slate-900 border border-slate-800 rounded-3xl p-8 flex flex-col md:flex-row items-center gap-8 shadow-2xl relative overflow-hidden">
                <div className="absolute top-0 right-0 p-4 opacity-5">
                    <Shield className="w-64 h-64 -mr-20 -mt-20" />
                </div>

                <div className="w-24 h-24 bg-gradient-to-br from-slate-800 to-slate-900 rounded-3xl flex items-center justify-center border border-slate-700 shadow-lg text-4xl">
                    {char.class === 'Воин' ? '⚔️' : char.class === 'Маг' ? '🔮' : '🏹'}
                </div>

                <div className="flex-1 text-center md:text-left space-y-2">
                    <div className="flex items-center gap-2 justify-center md:justify-start">
                        <h2 className="text-3xl font-bold text-slate-100">{char.name}</h2>
                        {syncing && <Loader2 className="w-4 h-4 text-amber-500 animate-spin" />}
                    </div>
                    <div className="flex items-center gap-3 justify-center md:justify-start text-sm">
                        <span className="text-slate-500">{char.class}</span>
                        <span className="text-slate-700">•</span>
                        <span className="text-amber-500 font-bold">Уровень {char.level}</span>
                    </div>

                    {/* XP Bar */}
                    <div className="mt-3 w-full max-w-xs mx-auto md:mx-0">
                        <div className="flex justify-between text-[10px] text-slate-400 font-bold uppercase mb-1">
                            <span>Опыт</span>
                            <span>{formatNumber(char.experience)} / {formatNumber(char.next_level_xp || 0)}</span>
                        </div>
                        <div className="h-1.5 w-full bg-slate-950 rounded-full overflow-hidden border border-slate-800">
                            <div
                                className="h-full bg-amber-500 transition-all duration-700 shadow-[0_0_10px_rgba(245,158,11,0.3)]"
                                style={{ width: `${char.xp_percentage || 0}%` }}
                            />
                        </div>
                    </div>
                </div>
                <div className="flex gap-4">
                    <div className="bg-slate-950 px-4 py-2 rounded-xl border border-slate-800 flex items-center gap-2">
                        <Coins className="w-4 h-4 text-amber-500" />
                        <span className="font-mono font-bold text-slate-100">{formatNumber(char.gold)}</span>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                <StatBar
                    label="Здоровье"
                    current={Math.round(dynamic.current_hp)}
                    max={stats.max_hp}
                    color="red"
                    icon={<Heart className="w-3 h-3" />}
                />
                <StatBar
                    label="Мана"
                    current={Math.round(dynamic.current_mp)}
                    max={stats.max_mp}
                    color="blue"
                    icon={<Droplets className="w-3 h-3" />}
                />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Column: Actions */}
                <div className="lg:col-span-2 space-y-6">
                    <h3 className="text-xl font-bold text-slate-300 px-1">Где приключения?</h3>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <button
                            onClick={() => navigate('/combat')}
                            className="group p-8 bg-amber-600 hover:bg-amber-500 rounded-3xl text-left transition-all shadow-xl shadow-amber-900/20 flex items-center gap-6"
                        >
                            <div className="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center backdrop-blur-sm group-hover:scale-110 transition-transform">
                                <Search className="w-8 h-8 text-white" />
                            </div>
                            <div>
                                <h4 className="text-2xl font-bold text-white mb-1">Искать врага</h4>
                                <p className="text-amber-100 text-sm opacity-80">Отправиться в лес на охоту</p>
                            </div>
                        </button>

                        <button
                            onClick={() => navigate('/inventory')}
                            className="group p-8 bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded-3xl text-left transition-all flex items-center gap-6"
                        >
                            <div className="w-16 h-16 bg-slate-800 rounded-2xl flex items-center justify-center group-hover:bg-slate-700 transition-colors">
                                <Package className="w-8 h-8 text-slate-400 group-hover:text-amber-500" />
                            </div>
                            <div>
                                <h4 className="text-2xl font-bold text-slate-100 mb-1">Инвентарь</h4>
                                <p className="text-slate-500 text-sm">Вещи, статы и снаряжение</p>
                            </div>
                        </button>
                    </div>

                    <div className="bg-slate-900 border border-slate-800 rounded-3xl p-8">
                        <h4 className="text-lg font-bold text-slate-200 mb-6 flex items-center gap-2">
                            <Skull className="w-5 h-5 text-slate-500" /> Журнал событий
                        </h4>
                        <div className="space-y-4">
                            <div className="p-4 bg-slate-950/50 rounded-xl border-l-2 border-amber-500 text-sm text-slate-400">
                                <span className="text-slate-500 mr-2">12:30</span>
                                Вы прибыли в город Victory. Вокруг суматоха и запах приключений.
                            </div>
                            <div className="p-4 bg-slate-950/50 rounded-xl border-l-2 border-slate-700 text-sm text-slate-400">
                                <span className="text-slate-500 mr-2">12:35</span>
                                Старейшина кивнул вам. Теперь вы полноправный участник гильдии.
                            </div>
                        </div>
                    </div>
                </div>

                {/* Right Column: Stats & Gear */}
                <div className="space-y-6">
                    <h3 className="text-xl font-bold text-slate-300 px-1 flex items-center gap-2">
                        Характеристики
                        {statPoints > 0 && (
                            <span className="text-xs bg-amber-600 text-white px-2 py-0.5 rounded-full font-bold animate-bounce">
                                +{statPoints}
                            </span>
                        )}
                    </h3>
                    <div className="bg-slate-900 border border-slate-800 rounded-3xl p-6 space-y-6 shadow-xl relative overflow-hidden">
                        <div className="space-y-4">
                            <div className="flex justify-between items-center group">
                                <span className="text-slate-400 flex items-center gap-2 group-hover:text-slate-300 transition-colors">
                                    <Target className="w-4 h-4" /> Урон
                                </span>
                                <span className="font-bold text-slate-100">{stats.min_damage} - {stats.max_damage}</span>
                            </div>
                            <div className="flex justify-between items-center group">
                                <span className="text-slate-400 flex items-center gap-2 group-hover:text-slate-300 transition-colors">
                                    <Shield className="w-4 h-4" /> Защита (Броня)
                                </span>
                                <span className="font-bold text-slate-100">{stats.armor}</span>
                            </div>
                        </div>

                        {/* Detailed Stats Expandable */}
                        <div className={`space-y-3 pt-4 border-t border-slate-800 overflow-hidden transition-all duration-500 ${showDetails ? 'max-h-96 opacity-100' : 'max-h-0 opacity-0 mb-[-1.5rem]'}`}>
                            <div className="flex justify-between items-center text-sm">
                                <span className="text-slate-500 uppercase text-[10px] font-bold">Меткость</span>
                                <span className="text-slate-300 font-mono">+{formatNumber(stats.accuracy)}%</span>
                            </div>
                            <div className="flex justify-between items-center text-sm">
                                <span className="text-slate-500 uppercase text-[10px] font-bold">Уклонение</span>
                                <span className="text-slate-300 font-mono">+{formatNumber(stats.evasion)}%</span>
                            </div>
                            <div className="flex justify-between items-center text-sm">
                                <span className="text-slate-500 uppercase text-[10px] font-bold">Крит. шанс</span>
                                <span className="text-slate-300 font-mono">+{formatNumber(stats.crit_chance)}%</span>
                            </div>
                            <div className="flex justify-between items-center text-sm">
                                <span className="text-slate-500 uppercase text-[10px] font-bold">Реген HP</span>
                                <span className="text-slate-300 font-mono">+{formatNumber(stats.hp_regen)}/ход</span>
                            </div>
                            <div className="flex justify-between items-center text-sm">
                                <span className="text-slate-500 uppercase text-[10px] font-bold">Реген MP</span>
                                <span className="text-slate-300 font-mono">+{formatNumber(stats.mp_regen)}/ход</span>
                            </div>
                        </div>

                        <button
                            onClick={() => setShowDetails(!showDetails)}
                            className="w-full py-2 bg-slate-950 hover:bg-slate-800 text-slate-500 hover:text-amber-500 text-[10px] font-bold uppercase tracking-widest rounded-xl transition-all border border-slate-800"
                        >
                            {showDetails ? 'Свернуть' : 'Развернуть'}
                        </button>

                        <div className="pt-6 border-t border-slate-800 grid grid-cols-2 md:grid-cols-3 gap-4">
                            {[
                                { label: 'Сила', key: 'strength', color: 'text-amber-500', bgHover: 'hover:border-amber-700' },
                                { label: 'Ловкость', key: 'agility', color: 'text-green-500', bgHover: 'hover:border-green-700' },
                                { label: 'Стойкость', key: 'constitution', color: 'text-red-500', bgHover: 'hover:border-red-700' },
                                { label: 'Интеллект', key: 'intelligence', color: 'text-blue-500', bgHover: 'hover:border-blue-700' },
                                { label: 'Удача', key: 'luck', color: 'text-yellow-500', bgHover: 'hover:border-yellow-700' },
                            ].map((s) => {
                                // Используем итоговое значение из расчетных стат (stats)
                                // и базовое из модели персонажа (char) для показа разницы
                                const modifiedValue = (stats as any)[s.key];
                                const baseValue = (char as any)[s.key] || 0;
                                const addedValue = (char as any)[`${s.key}_added`] || 0;
                                const diff = modifiedValue !== undefined ? modifiedValue - (baseValue + addedValue) : 0;
                                const displayValue = modifiedValue !== undefined ? modifiedValue : baseValue;
                                const isDistributing = distributing === s.key;

                                return (
                                    <div key={s.key} className={`bg-slate-950 p-3 rounded-xl border border-slate-800 text-center relative group/stat transition-all ${statPoints > 0 ? s.bgHover : ''}`}>
                                        <div className={`text-[10px] uppercase font-bold text-slate-500 mb-1`}>{s.label}</div>
                                        <div className="text-xl font-bold text-slate-100 flex items-center justify-center gap-1">
                                            {displayValue}
                                            {diff !== 0 && (
                                                <span className={`text-[10px] ${diff > 0 ? 'text-green-500' : 'text-red-500'}`}>
                                                    {diff > 0 ? '↑' : '↓'}
                                                </span>
                                            )}
                                        </div>
                                        {addedValue > 0 && (
                                            <div className="text-[9px] text-slate-600 mt-0.5">
                                                база {baseValue} + <span className={s.color}>{addedValue}</span>
                                            </div>
                                        )}
                                        {diff !== 0 && (
                                            <div className="absolute -top-2 -right-2 bg-slate-800 text-[8px] px-1.5 py-0.5 rounded border border-slate-700 opacity-0 group-hover/stat:opacity-100 transition-opacity">
                                                {diff > 0 ? '+' : ''}{diff} (экип/класс)
                                            </div>
                                        )}
                                        {statPoints > 0 && (
                                            <button
                                                onClick={() => handleDistributeStat(s.key)}
                                                disabled={isDistributing}
                                                className={`absolute -top-2 -left-2 w-6 h-6 bg-amber-600 hover:bg-amber-500 text-white rounded-full flex items-center justify-center text-xs font-bold shadow-lg shadow-amber-900/40 transition-all hover:scale-110 ${isDistributing ? 'animate-spin' : 'animate-bounce'}`}
                                            >
                                                {isDistributing ? <Loader2 className="w-3 h-3" /> : <Plus className="w-3 h-3" />}
                                            </button>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default DashboardPage;
