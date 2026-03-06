import React, { useState, useEffect } from 'react';
import { useGameStore } from '../store/gameStore';
import api from '../api/axios';
import { Navigate, useNavigate } from 'react-router-dom';
import StatBar from '../components/StatBar';
import { Heart, Droplets, Shield, Target, Coins, Skull, Package, Search, Loader2, Plus, Sparkles, Store, ArrowRight, ArrowLeft, Star, ScrollText, Trophy, Timer } from 'lucide-react';
import { formatNumber } from '../lib/utils';
import { goDeeper, startCombat, changeDepth } from '../api/combatApi';

const DetailStat: React.FC<{ label: string; value: string | number }> = ({ label, value }) => (
    <div className="flex justify-between items-center text-sm">
        <span className="text-slate-500 uppercase text-[10px] font-bold">{label}</span>
        <span className="text-slate-300 font-mono">{value}</span>
    </div>
);

const DashboardPage: React.FC = () => {
    const { currentCharacter, setCurrentCharacter } = useGameStore();
    const navigate = useNavigate();
    const [showDetails, setShowDetails] = useState(false);
    const [syncing, setSyncing] = useState(false);
    const [searching, setSearching] = useState(false);
    const [distributing, setDistributing] = useState<string | null>(null);
    const [questSummary, setQuestSummary] = useState<{ available: number, ready: number }>({ available: 0, ready: 0 });

    useEffect(() => {
        const syncData = async () => {
            if (!currentCharacter) return;
            // setSyncing(true); // Don't show loading spinner for background sync
            try {
                const response = await api.get(`/characters/${currentCharacter.id}`);
                if (response.data.data) {
                    setCurrentCharacter(response.data.data);
                }

                // Синхронизируем квесты
                const questsRes = await api.get('/quests', { params: { character_id: currentCharacter.id } });
                if (questsRes.data.success) {
                    const quests = questsRes.data.data;
                    const available = quests.filter((q: any) => q.pivot.status === 'available').length;
                    const ready = quests.filter((q: any) => q.pivot.status === 'ready').length;
                    setQuestSummary({ available, ready });
                }
            } catch (err) {
                console.error("Failed to sync character data", err);
            } finally {
                setSyncing(false);
            }
        };

        syncData();

        // Добавляем опрос сервера каждые 5 секунд для обновления регена
        const interval = setInterval(syncData, 5000);
        return () => clearInterval(interval);
    }, [currentCharacter?.id]);

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
        current_mp: stats.max_mp,
        enemies_defeated_at_depth: 0
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

    const handleSearchEnemy = async () => {
        if (!currentCharacter || searching) return;
        setSearching(true);
        try {
            await startCombat(currentCharacter.id);
            navigate('/combat');
        } catch (err: any) {
            alert(err.response?.data?.message || 'Не удалось найти противника');
        } finally {
            setSearching(false);
        }
    };

    const handleGoDeeper = async () => {
        if (!currentCharacter || dynamic.enemies_defeated_at_depth < 3 || syncing) return;
        setSyncing(true);
        try {
            const response = await goDeeper(currentCharacter.id);
            if (response.success) {
                // Синхронизируем данные персонажа
                const charRes = await api.get(`/characters/${char.id}`);
                setCurrentCharacter(charRes.data.data);
            }
        } catch (err: any) {
            alert(err.response?.data?.message || 'Ошибка перехода');
        } finally {
            setSyncing(false);
        }
    };

    const handleDepthChange = async (newDepth: number) => {
        if (!currentCharacter || syncing || newDepth < 1 || newDepth > (char.max_dungeon_depth || 1)) return;
        setSyncing(true);
        try {
            const response = await changeDepth(currentCharacter.id, newDepth);
            if (response.success) {
                const charRes = await api.get(`/characters/${char.id}`);
                setCurrentCharacter(charRes.data.data);
            }
        } catch (err: any) {
            alert(err.response?.data?.message || 'Ошибка смены глубины');
        } finally {
            setSyncing(false);
        }
    };

    const formatTime = (totalSeconds: number) => {
        if (totalSeconds <= 0) return "0с";

        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;

        if (minutes > 0) {
            return `${minutes}м ${seconds}с`;
        }
        return `${seconds}с`;
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

            {/* Quest Notifications */}
            {questSummary.ready > 0 && (
                <div
                    onClick={() => navigate('/quests')}
                    className="bg-green-900/30 border border-green-700/50 rounded-2xl p-4 flex items-center gap-4 cursor-pointer hover:bg-green-900/40 transition-all shadow-lg shadow-green-900/20"
                >
                    <div className="w-10 h-10 bg-green-600 rounded-xl flex items-center justify-center shadow-lg shadow-green-900/40">
                        <Trophy className="w-5 h-5 text-white animate-bounce" />
                    </div>
                    <div>
                        <p className="text-green-200 font-bold text-sm">Задание выполнено!</p>
                        <p className="text-green-400/70 text-xs">У вас есть <span className="font-bold text-green-300">{questSummary.ready}</span> готовых заданий. Заберите свою награду!</p>
                    </div>
                    <ArrowRight className="w-5 h-5 text-green-500 ml-auto" />
                </div>
            )}

            {questSummary.available > 0 && (
                <div
                    onClick={() => navigate('/quests')}
                    className="bg-blue-900/30 border border-blue-700/50 rounded-2xl p-4 flex items-center gap-4 cursor-pointer hover:bg-blue-900/40 transition-all shadow-lg shadow-blue-900/20"
                >
                    <div className="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-900/40">
                        <ScrollText className="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p className="text-blue-200 font-bold text-sm">Новые поручения</p>
                        <p className="text-blue-400/70 text-xs">Доступно <span className="font-bold text-blue-300">{questSummary.available}</span> новых заданий в журнале.</p>
                    </div>
                    <ArrowRight className="w-5 h-5 text-blue-500 ml-auto" />
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

            {/* Dungeon Progression */}
            <div className="bg-slate-900 border border-slate-800 rounded-3xl p-6 relative overflow-hidden">
                <div className="flex flex-col md:flex-row items-center justify-between gap-6 relative z-10">
                    <div className="flex items-center gap-4">
                        <div className="w-12 h-12 bg-red-950/30 rounded-xl flex items-center justify-center border border-red-900/50">
                            <Skull className="w-6 h-6 text-red-500" />
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <button
                                    onClick={() => handleDepthChange(char.dungeon_depth - 1)}
                                    disabled={char.dungeon_depth <= 1 || syncing}
                                    className="p-1 hover:bg-slate-800 rounded-lg text-slate-500 disabled:opacity-30 transition-colors"
                                >
                                    <ArrowLeft className="w-4 h-4" />
                                </button>
                                <h4 className="text-lg font-bold text-slate-100 italic tracking-tight">Глубина {char.dungeon_depth}</h4>
                                <button
                                    onClick={() => handleDepthChange(char.dungeon_depth + 1)}
                                    disabled={char.dungeon_depth >= (char.max_dungeon_depth || 1) || syncing}
                                    className="p-1 hover:bg-slate-800 rounded-lg text-slate-500 disabled:opacity-30 transition-colors"
                                >
                                    <ArrowRight className="w-4 h-4" />
                                </button>
                            </div>
                            <p className="text-[10px] text-slate-500 uppercase font-bold">Максимальная открытая: {char.max_dungeon_depth || 1}</p>
                        </div>
                    </div>

                    <div className="flex-1 max-w-md w-full">
                        <div className="flex justify-between text-[10px] text-slate-500 font-bold uppercase mb-1">
                            <span>Прогресс уровня</span>
                            <span>{dynamic.enemies_defeated_at_depth} / 3 побед</span>
                        </div>
                        <div className="h-2 w-full bg-slate-950 rounded-full border border-slate-800 p-0.5">
                            <div
                                className="h-full bg-red-600 rounded-full transition-all duration-500 shadow-[0_0_10px_rgba(220,38,38,0.4)]"
                                style={{ width: `${Math.min(100, (dynamic.enemies_defeated_at_depth / 3) * 100)}%` }}
                            />
                        </div>
                    </div>

                    <button
                        onClick={handleGoDeeper}
                        disabled={dynamic.enemies_defeated_at_depth < 3 || syncing}
                        className={`px-8 py-3 rounded-2xl font-bold transition-all flex items-center gap-2 ${dynamic.enemies_defeated_at_depth >= 3
                            ? 'bg-red-600 hover:bg-red-500 text-white shadow-lg shadow-red-900/40 animate-pulse'
                            : 'bg-slate-800 text-slate-600 cursor-not-allowed border border-slate-700'
                            }`}
                    >
                        {syncing ? <Loader2 className="w-4 h-4 animate-spin" /> : <ArrowRight className="w-4 h-4" />}
                        Спуститься глубже
                    </button>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                {/* Блок Здоровья */}
                <div className="flex flex-col gap-1">
                    <StatBar
                        label="Здоровье"
                        current={Math.round(dynamic.current_hp)}
                        max={stats.max_hp}
                        color="red"
                        icon={<Heart className="w-3 h-3" />}
                    />
                    {dynamic.current_hp < stats.max_hp && stats.hp_regen > 0 && (
                        <div className="text-[10px] text-slate-500 font-medium text-right flex items-center justify-end gap-1">
                            <Timer className="w-2.5 h-2.5 text-slate-400" />
                            До полного: {formatTime(Math.ceil((stats.max_hp - dynamic.current_hp) / (stats.hp_regen / 60)))}
                        </div>
                    )}
                </div>

                {/* Блок Маны */}
                <div className="flex flex-col gap-1">
                    <StatBar
                        label="Мана"
                        current={Math.round(dynamic.current_mp)}
                        max={stats.max_mp}
                        color="blue"
                        icon={<Droplets className="w-3 h-3" />}
                    />
                    {dynamic.current_mp < stats.max_mp && stats.mp_regen > 0 && (
                        <div className="text-[10px] text-slate-500 font-medium text-right flex items-center justify-end gap-1">
                            <Timer className="w-2.5 h-2.5 text-slate-400" />
                            До полного: {formatTime(Math.ceil((stats.max_mp - dynamic.current_mp) / (stats.mp_regen / 60)))}
                        </div>
                    )}
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Column: Actions */}
                <div className="lg:col-span-2 space-y-6">
                    <h3 className="text-xl font-bold text-slate-300 px-1">Где приключения?</h3>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <button
                            onClick={handleSearchEnemy}
                            disabled={searching}
                            className="group p-8 bg-amber-600 hover:bg-amber-500 rounded-3xl text-left transition-all shadow-xl shadow-amber-900/20 flex items-center gap-6"
                        >
                            <div className="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center backdrop-blur-sm group-hover:scale-110 transition-transform">
                                {searching ? <Loader2 className="w-8 h-8 text-white animate-spin" /> : <Search className="w-8 h-8 text-white" />}
                            </div>
                            <div>
                                <h4 className="text-2xl font-bold text-white mb-1">Искать врага</h4>
                                <p className="text-amber-100 text-sm opacity-80">Случайная битва на глубине {char.dungeon_depth}</p>
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

                        <button
                            onClick={() => navigate('/shops')}
                            className="group p-8 bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded-3xl text-left transition-all flex items-center gap-6"
                        >
                            <div className="w-16 h-16 bg-slate-800 rounded-2xl flex items-center justify-center group-hover:bg-slate-700 transition-colors">
                                <Store className="w-8 h-8 text-slate-400 group-hover:text-amber-500" />
                            </div>
                            <div>
                                <h4 className="text-2xl font-bold text-slate-100 mb-1">Магазин</h4>
                                <p className="text-slate-500 text-sm">Торговые лавки города</p>
                            </div>
                        </button>

                        <button
                            onClick={() => navigate('/abilities')}
                            className="group p-8 bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded-3xl text-left transition-all flex items-center gap-6"
                        >
                            <div className="w-16 h-16 bg-slate-800 rounded-2xl flex items-center justify-center group-hover:bg-slate-700 transition-colors">
                                <Star className="w-8 h-8 text-slate-400 group-hover:text-amber-500" />
                            </div>
                            <div>
                                <h4 className="text-2xl font-bold text-slate-100 mb-1">Навыки</h4>
                                <p className="text-slate-500 text-sm">Способности класса</p>
                            </div>
                        </button>

                        <button
                            onClick={() => navigate('/quests')}
                            className="group p-8 bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded-3xl text-left transition-all flex items-center gap-6"
                        >
                            <div className="w-16 h-16 bg-slate-800 rounded-2xl flex items-center justify-center group-hover:bg-slate-700 transition-colors">
                                <ScrollText className="w-8 h-8 text-slate-400 group-hover:text-amber-500" />
                            </div>
                            <div>
                                <h4 className="text-2xl font-bold text-slate-100 mb-1">Задания</h4>
                                <p className="text-slate-500 text-sm">Квесты и награды</p>
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
                                    <Target className="w-4 h-4" /> {char.class === 'Маг' ? 'Маг. урон' : 'Урон'}
                                </span>
                                <span className="font-bold text-slate-100">{Math.round(stats.min_damage)} - {Math.round(stats.max_damage)}</span>
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
                            <DetailStat label="Меткость" value={`+${formatNumber(stats.accuracy)}%`} />
                            <DetailStat label="Уклонение" value={`+${formatNumber(stats.evasion)}%`} />
                            <DetailStat label="Крит. шанс" value={`${formatNumber(stats.crit_chance)}%`} />
                            <DetailStat label="Реген HP" value={`+${formatNumber(stats.hp_regen)}/ход`} />
                            <DetailStat label="Реген MP" value={`+${formatNumber(stats.mp_regen)}/ход`} />
                            <DetailStat label="Физ. урон" value={`+${formatNumber(stats.physical_damage_bonus)}%`} />
                            <DetailStat label="Маг. урон" value={`+${formatNumber(stats.magical_damage_bonus)}%`} />
                        </div>

                        <button
                            onClick={() => setShowDetails(!showDetails)}
                            className="w-full py-4 mt-2 bg-slate-900/50 hover:bg-slate-800 text-slate-400 hover:text-amber-500 text-xs font-bold uppercase tracking-widest rounded-xl transition-all border border-slate-700/50 flex items-center justify-center cursor-pointer shadow-sm hover:shadow-md"
                        >
                            {showDetails ? 'Свернуть характеристики' : 'Развернуть характеристики'}
                        </button>

                        <div className="pt-6 border-t border-slate-800 flex flex-col gap-3">
                            {[
                                { label: 'Сила', key: 'strength', color: 'text-amber-500', bgHover: 'hover:border-amber-700', desc: 'Увеличивает физ. урон и силу атаки' },
                                { label: 'Ловкость', key: 'agility', color: 'text-green-500', bgHover: 'hover:border-green-700', desc: 'Меткость, уклонение и урон лучника' },
                                { label: 'Стойкость', key: 'constitution', color: 'text-red-500', bgHover: 'hover:border-red-700', desc: 'Макс. здоровье и его регенерация' },
                                { label: 'Интеллект', key: 'intelligence', color: 'text-blue-500', bgHover: 'hover:border-blue-700', desc: 'Маг. урон, запас маны и её реген' },
                                { label: 'Удача', key: 'luck', color: 'text-yellow-500', bgHover: 'hover:border-yellow-700', desc: 'Крит. шанс и удача при поиске лута' },
                            ].map((s) => {
                                // Итоговое значение от сервера
                                const finalValue = (stats as any)[s.key] || 0;
                                // Базовое значение из БД (обычно 5)
                                const baseValue = (char as any)[s.key] || 0;
                                // Влитые очки характеристик (от уровня)
                                const levelPoints = (char as any)[`${s.key}_added`] || 0;

                                // Определяем модификатор класса (чтобы понять, рисовать ли стрелочку)
                                let classMod = 0; // 0 = нет бонуса, 10 = +10%, -10 = -10%
                                const charClass = char.class?.toLowerCase();
                                if (charClass === 'воин') {
                                    if (s.key === 'strength') classMod = 10;
                                    if (s.key === 'intelligence') classMod = -10;
                                } else if (charClass === 'лучник') {
                                    if (s.key === 'agility') classMod = 10;
                                    if (s.key === 'strength') classMod = -10;
                                } else if (charClass === 'маг') {
                                    if (s.key === 'intelligence') classMod = 10;
                                    if (s.key === 'constitution') classMod = -10;
                                }

                                // Расчитываем часть от экипировки
                                // Формула на сервере: (base + added) * (1 + mod/100) + equip
                                const valueWithClass = (baseValue + levelPoints) * (1 + classMod / 100);
                                const equipBonus = Math.round(finalValue - valueWithClass);

                                const isDistributing = distributing === s.key;

                                return (
                                    <div key={s.key} className={`bg-slate-950 p-4 rounded-2xl border border-slate-800 flex items-center justify-between relative group/stat transition-all ${statPoints > 0 ? s.bgHover : ''}`}>
                                        <div className="flex flex-col gap-0.5">
                                            <div className="flex items-center gap-3">
                                                <span className={`text-[10px] uppercase font-black tracking-wider ${s.color}`}>{s.label}</span>
                                                <div className="text-xl font-bold text-slate-100 flex items-center gap-1.5">
                                                    {Math.round(finalValue)}
                                                    {classMod !== 0 && (
                                                        <span className={`text-xs ${classMod > 0 ? 'text-green-500' : 'text-red-500'}`}>
                                                            {classMod > 0 ? '↑' : '↓'}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="text-[11px] text-slate-500 leading-tight">
                                                {s.desc}
                                            </div>
                                            <div className="flex gap-3 mt-1.5">
                                                <div className="text-[9px] text-slate-600">
                                                    Очки: <span className="text-slate-400">+{levelPoints}</span>
                                                </div>
                                                {equipBonus !== 0 && (
                                                    <div className="text-[9px] text-slate-600">
                                                        Экип: <span className="text-green-600">+{equipBonus}</span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>

                                        {statPoints > 0 && (
                                            <button
                                                onClick={() => handleDistributeStat(s.key)}
                                                disabled={isDistributing}
                                                className={`w-8 h-8 bg-amber-600 hover:bg-amber-500 text-white rounded-full flex items-center justify-center text-xs font-bold shadow-lg shadow-amber-900/40 transition-all hover:scale-110 active:scale-95 ${isDistributing ? 'animate-spin' : 'animate-bounce'}`}
                                            >
                                                {isDistributing ? <Loader2 className="w-4 h-4" /> : <Plus className="w-3 h-3" />}
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
