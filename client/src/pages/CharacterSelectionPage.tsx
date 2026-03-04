import React, { useEffect, useState } from 'react';
import { useGameStore } from '../store/gameStore';
import api from '../api/axios';
import type { Character } from '../types/game';
import { Plus, User as UserIcon, Loader2 } from 'lucide-react';
import { useNavigate } from 'react-router-dom';

const CharacterSelectionPage: React.FC = () => {
    const [characters, setCharacters] = useState<Character[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const setCurrentCharacter = useGameStore(state => state.setCurrentCharacter);
    const navigate = useNavigate();

    useEffect(() => {
        const fetchCharacters = async () => {
            try {
                const response = await api.get(`/characters?paginate=false&t=${Date.now()}`);
                setCharacters(response.data.data || []);
            } catch (err) {
                setError('Не удалось загрузить список персонажей');
            } finally {
                setLoading(false);
            }
        };

        fetchCharacters();
        const interval = setInterval(fetchCharacters, 5000);
        return () => clearInterval(interval);
    }, []);

    const handleSelect = (char: Character) => {
        setCurrentCharacter(char);
        navigate('/dashboard');
    };

    if (loading) {
        return (
            <div className="flex flex-col items-center justify-center py-20">
                <Loader2 className="w-12 h-12 text-amber-500 animate-spin mb-4" />
                <p className="text-slate-400">Ищем ваших героев в таверне...</p>
            </div>
        );
    }

    return (
        <div className="max-w-4xl mx-auto">
            <div className="flex items-center justify-between mb-8">
                <div>
                    <h2 className="text-3xl font-bold text-slate-100">Ваши герои</h2>
                    <p className="text-slate-400 mt-1">Выберите персонажа для продолжения игры</p>
                </div>
                {characters.length < 3 && (
                    <button
                        onClick={() => navigate('/characters/create')}
                        className="flex items-center gap-2 bg-amber-600 hover:bg-amber-500 text-white px-4 py-2 rounded-lg font-bold transition-all"
                    >
                        <Plus className="w-5 h-5" />
                        Создать нового
                    </button>
                )}
            </div>

            {error && (
                <div className="bg-red-900/20 border border-red-500/50 text-red-400 px-6 py-4 rounded-xl mb-8">
                    {error}
                </div>
            )}

            {characters.length === 0 ? (
                <div className="bg-slate-900/50 border-2 border-dashed border-slate-800 rounded-3xl p-12 text-center">
                    <UserIcon className="w-16 h-16 text-slate-700 mx-auto mb-4" />
                    <h3 className="text-xl font-bold text-slate-300">У вас пока нет героев</h3>
                    <p className="text-slate-500 mt-2 mb-8 max-w-sm mx-auto">
                        Создайте своего первого персонажа, чтобы отправиться в приключение
                    </p>
                    <button
                        onClick={() => navigate('/characters/create')}
                        className="bg-amber-600 hover:bg-amber-500 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-amber-900/20"
                    >
                        Начать создание
                    </button>
                </div>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {characters.map((char) => (
                        <div
                            key={char.id}
                            onClick={() => handleSelect(char)}
                            className="bg-slate-900 border border-slate-800 rounded-2xl p-6 hover:border-amber-500/50 hover:bg-slate-800/50 transition-all cursor-pointer group"
                        >
                            <div className="flex items-center justify-between mb-4">
                                <div className="w-12 h-12 bg-slate-800 rounded-xl flex items-center justify-center group-hover:bg-amber-500/20 transition-colors text-2xl">
                                    {char.class === 'Воин' ? '⚔️' : char.class === 'Маг' ? '🔮' : '🏹'}
                                </div>
                                <div className="text-right">
                                    <div className="text-xs font-bold text-amber-500 uppercase tracking-wider">{char.class}</div>
                                    <div className="text-sm text-slate-400">Уровень {char.level}</div>
                                </div>
                            </div>

                            <h3 className="text-xl font-bold text-slate-100 mb-2">{char.name}</h3>

                            <div className="space-y-3 mb-4">
                                <div className="space-y-1">
                                    <div className="flex justify-between text-[10px] text-slate-400 font-bold uppercase">
                                        <span>HP</span>
                                        <span>{Math.round(char.dynamic_stats?.current_hp || 0)} / {Math.round(char.stats?.max_hp || 0)}</span>
                                    </div>
                                    <div className="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-red-500 transition-all duration-500"
                                            style={{ width: `${((char.dynamic_stats?.current_hp || 0) / (char.stats?.max_hp || 1)) * 100}%` }}
                                        />
                                    </div>
                                </div>
                                <div className="space-y-1">
                                    <div className="flex justify-between text-[10px] text-slate-400 font-bold uppercase">
                                        <span>MP</span>
                                        <span>{Math.round(char.dynamic_stats?.current_mp || 0)} / {Math.round(char.stats?.max_mp || 0)}</span>
                                    </div>
                                    <div className="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-blue-500 transition-all duration-500"
                                            style={{ width: `${((char.dynamic_stats?.current_mp || 0) / (char.stats?.max_mp || 1)) * 100}%` }}
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="grid grid-cols-5 gap-1 py-3 border-y border-slate-800/50">
                                {[
                                    { label: 'Сила', base: 'strength' },
                                    { label: 'Ловк.', base: 'agility' },
                                    { label: 'Стой.', base: 'constitution' },
                                    { label: 'Инт.', base: 'intelligence' },
                                    { label: 'Удача', base: 'luck' },
                                ].map((s, i) => {
                                    const calcValue = (char.stats as any)?.[s.base] || (char as any)[s.base] || 0;
                                    return (
                                        <div key={s.base} className={`text-center ${i > 0 ? 'border-l border-slate-800/50' : ''}`}>
                                            <div className="text-[9px] text-slate-500 uppercase font-bold">{s.label}</div>
                                            <div className="text-xs text-slate-200 font-bold">{calcValue}</div>
                                        </div>
                                    );
                                })}
                            </div>

                            <button className="w-full mt-6 py-2 bg-slate-800 text-slate-300 rounded-lg text-sm font-bold group-hover:bg-amber-600 group-hover:text-white transition-all">
                                Выбрать
                            </button>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};

export default CharacterSelectionPage;
