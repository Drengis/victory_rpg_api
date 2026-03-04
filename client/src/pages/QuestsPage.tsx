import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import {
    ScrollText,
    ChevronLeft,
    CheckCircle2,
    Circle,
    Clock,
    Trophy,
    Coins,
    Zap,
    Target,
    Map,
    ShieldAlert
} from 'lucide-react';
import api from '../api/axios';
import { useGameStore } from '../store/gameStore';

interface QuestReward {
    gold?: number;
    xp?: number;
    stat_points?: number;
    items?: number[];
}

interface Quest {
    id: number;
    name: string;
    description: string;
    type: string;
    target_value: number;
    pivot: {
        current_value: number;
        status: 'available' | 'active' | 'ready' | 'completed';
    };
    rewards: QuestReward;
}

const QuestsPage: React.FC = () => {
    const navigate = useNavigate();
    const { currentCharacter } = useGameStore();
    const [quests, setQuests] = useState<Quest[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!currentCharacter) {
            navigate('/characters');
            return;
        }
        fetchQuests();
    }, [currentCharacter]);

    const fetchQuests = async () => {
        try {
            setLoading(true);
            const response = await api.get(`/quests`, {
                params: { character_id: currentCharacter?.id }
            });
            if (response.data.success) {
                setQuests(response.data.data);
            }
        } catch (err: any) {
            setError(err.response?.data?.message || 'Ошибка при загрузке квестов');
        } finally {
            setLoading(false);
        }
    };

    const handleAccept = async (questId: number) => {
        try {
            const response = await api.post(`/quests/${questId}/accept`, {
                character_id: currentCharacter?.id
            });
            if (response.data.success) {
                fetchQuests();
            }
        } catch (err: any) {
            alert(err.response?.data?.message || 'Ошибка при принятии квеста');
        }
    };

    const handleClaim = async (questId: number) => {
        try {
            const response = await api.post(`/quests/${questId}/claim`, {
                character_id: currentCharacter?.id
            });
            if (response.data.success) {
                // Можно добавить анимацию получения награды
                fetchQuests();
            }
        } catch (err: any) {
            alert(err.response?.data?.message || 'Ошибка при получении награды');
        }
    };

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'completed': return <CheckCircle2 className="w-6 h-6 text-green-400" />;
            case 'ready': return <Trophy className="w-6 h-6 text-yellow-400 animate-bounce" />;
            case 'active': return <Clock className="w-6 h-6 text-blue-400" />;
            default: return <Circle className="w-6 h-6 text-gray-500" />;
        }
    };

    const getStatusText = (status: string) => {
        switch (status) {
            case 'completed': return 'Завершено';
            case 'ready': return 'Готов к сдаче';
            case 'active': return 'В процессе';
            default: return 'Доступен';
        }
    };

    const getTypeIcon = (type: string) => {
        switch (type) {
            case 'kills': return <Target className="w-5 h-5" />;
            case 'depth': return <Map className="w-5 h-5" />;
            case 'gold': return <Coins className="w-5 h-5" />;
            case 'level': return <Zap className="w-5 h-5" />;
            default: return <ScrollText className="w-5 h-5" />;
        }
    };

    if (loading && quests.length === 0) {
        return (
            <div className="min-h-screen bg-slate-950 flex items-center justify-center">
                <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-amber-500"></div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100 p-4 pb-20">
            {/* Header */}
            <div className="max-w-4xl mx-auto mb-8 flex items-center justify-between">
                <button
                    onClick={() => navigate('/dashboard')}
                    className="flex items-center gap-2 text-slate-400 hover:text-white transition-colors"
                >
                    <ChevronLeft className="w-5 h-5" />
                    <span>Вернуться в хаб</span>
                </button>
                <div className="flex items-center gap-3">
                    <ScrollText className="w-8 h-8 text-amber-500" />
                    <h1 className="text-3xl font-bold bg-gradient-to-r from-amber-400 to-orange-500 bg-clip-text text-transparent italic">
                        ЗАДАНИЯ
                    </h1>
                </div>
                <div className="w-10" /> {/* Spacer */}
            </div>

            <div className="max-w-4xl mx-auto space-y-4">
                {error && (
                    <div className="bg-red-500/20 border border-red-500/50 p-4 rounded-xl flex items-center gap-3 text-red-200">
                        <ShieldAlert className="w-6 h-6" />
                        <p>{error}</p>
                    </div>
                )}

                {quests.length === 0 && !loading && (
                    <div className="text-center py-20 bg-slate-900/50 border border-slate-800 rounded-3xl backdrop-blur-sm">
                        <ScrollText className="w-16 h-16 text-slate-700 mx-auto mb-4" />
                        <p className="text-slate-400 text-lg">Нет доступных заданий.</p>
                    </div>
                )}

                {quests.map((quest) => {
                    const isCompleted = quest.pivot.status === 'completed';
                    const isReady = quest.pivot.status === 'ready';
                    const isActive = quest.pivot.status === 'active';
                    const isAvailable = quest.pivot.status === 'available';

                    const progress = Math.min(100, (quest.pivot.current_value / quest.target_value) * 100);

                    return (
                        <div
                            key={quest.id}
                            className={`relative overflow-hidden group transition-all duration-300 rounded-3xl border ${isCompleted
                                ? 'bg-slate-900/40 border-slate-800'
                                : isReady
                                    ? 'bg-amber-900/20 border-amber-500/50 shadow-lg shadow-amber-900/20'
                                    : 'bg-slate-900/80 border-slate-700/50 hover:border-slate-600'
                                }`}
                        >
                            <div className="p-6">
                                <div className="flex items-start justify-between gap-4">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-2 mb-1">
                                            <span className="p-1.5 rounded-lg bg-slate-800 text-slate-400">
                                                {getTypeIcon(quest.type)}
                                            </span>
                                            <h3 className={`text-xl font-bold ${isCompleted ? 'text-slate-500 line-through' : 'text-slate-100'}`}>
                                                {quest.name}
                                            </h3>
                                        </div>
                                        <p className={`text-sm mb-4 ${isCompleted ? 'text-slate-600' : 'text-slate-400'}`}>
                                            {quest.description}
                                        </p>

                                        {/* Progress Bar */}
                                        {!isCompleted && (
                                            <div className="space-y-2 mb-4">
                                                <div className="flex justify-between text-xs font-medium text-slate-500">
                                                    <span>Условие: {quest.type === 'kills' ? 'Убить' : quest.type === 'depth' ? 'Достичь глубины' : quest.type === 'gold' ? 'Собрать золото' : 'Уровень'}</span>
                                                    <span className={isReady ? 'text-amber-400' : 'text-slate-300'}>
                                                        {quest.pivot.current_value} / {quest.target_value}
                                                    </span>
                                                </div>
                                                <div className="h-1.5 bg-slate-800 rounded-full overflow-hidden">
                                                    <div
                                                        className={`h-full transition-all duration-500 rounded-full ${isReady ? 'bg-amber-500' : 'bg-blue-500'}`}
                                                        style={{ width: `${progress}%` }}
                                                    />
                                                </div>
                                            </div>
                                        )}

                                        {/* Rewards */}
                                        <div className="flex flex-wrap gap-4 mt-2">
                                            {quest.rewards.gold && (
                                                <div className="flex items-center gap-1.5 text-yellow-500 text-sm font-bold bg-yellow-500/10 px-3 py-1 rounded-full border border-yellow-500/20">
                                                    <Coins className="w-4 h-4" />
                                                    <span>+{quest.rewards.gold}</span>
                                                </div>
                                            )}
                                            {quest.rewards.xp && (
                                                <div className="flex items-center gap-1.5 text-blue-400 text-sm font-bold bg-blue-500/10 px-3 py-1 rounded-full border border-blue-500/20">
                                                    <Zap className="w-4 h-4" />
                                                    <span>+{quest.rewards.xp} XP</span>
                                                </div>
                                            )}
                                            {quest.rewards.stat_points && (
                                                <div className="flex items-center gap-1.5 text-purple-400 text-sm font-bold bg-purple-500/10 px-3 py-1 rounded-full border border-purple-500/20">
                                                    <ShieldAlert className="w-4 h-4" />
                                                    <span>+{quest.rewards.stat_points} Очко</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    <div className="flex flex-col items-end gap-4 min-w-[120px]">
                                        <div className="flex items-center gap-2">
                                            <span className={`text-xs font-bold uppercase tracking-wider ${isCompleted ? 'text-green-500' : isReady ? 'text-amber-500' : isActive ? 'text-blue-500' : 'text-slate-500'
                                                }`}>
                                                {getStatusText(quest.pivot.status)}
                                            </span>
                                            {getStatusIcon(quest.pivot.status)}
                                        </div>

                                        {isAvailable && (
                                            <button
                                                onClick={() => handleAccept(quest.id)}
                                                className="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold transition-all border border-slate-700 active:scale-95"
                                            >
                                                Принять
                                            </button>
                                        )}

                                        {isReady && (
                                            <button
                                                onClick={() => handleClaim(quest.id)}
                                                className="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 text-white font-bold transition-all shadow-lg shadow-amber-900/20 active:scale-95"
                                            >
                                                Награда
                                            </button>
                                        )}

                                        {isActive && !isReady && (
                                            <div className="text-slate-500 text-xs italic text-center w-full">
                                                Выполняйте условия...
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Decorative accent for active/ready quests */}
                            {!isCompleted && (
                                <div className={`absolute left-0 top-0 bottom-0 w-1 ${isReady ? 'bg-amber-500' : isActive ? 'bg-blue-500' : 'bg-slate-700'}`} />
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
};

export default QuestsPage;
