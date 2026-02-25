import React, { useState } from 'react';
import api from '../api/axios';
import { Sword, Zap, Wand2, Loader2, ArrowLeft } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { useGameStore } from '../store/gameStore';
import { useAuthStore } from '../store/authStore';

const classes = [
    {
        name: 'Воин',
        icon: Sword,
        description: 'Мастер ближнего боя. Высокая выживаемость и физический урон. Основной стат: Сила.',
        color: 'text-red-500',
        bg: 'bg-red-500/10'
    },
    {
        name: 'Лучник',
        icon: Zap,
        description: 'Быстрый и точный. Высокий шанс крита и уклонения. Основной стат: Ловкость.',
        color: 'text-green-500',
        bg: 'bg-green-500/10'
    },
    {
        name: 'Маг',
        icon: Wand2,
        description: 'Повелитель стихий. Огромный магический урон, но хрупкое здоровье. Основной стат: Интеллект.',
        color: 'text-blue-500',
        bg: 'bg-blue-500/10'
    }
];

const CharacterCreationPage: React.FC = () => {
    const [name, setName] = useState('');
    const [selectedClass, setSelectedClass] = useState('Воин');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const navigate = useNavigate();
    const setCurrentCharacter = useGameStore(state => state.setCurrentCharacter);
    const { user } = useAuthStore();

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const response = await api.post('/characters', {
                name,
                class: selectedClass,
                user_id: user?.id
            });
            // Laravel возвращает { success: true, data: { ... } }
            setCurrentCharacter(response.data.data);
            navigate('/dashboard');
        } catch (err: any) {
            setError(err.response?.data?.message || 'Ошибка при создании персонажа. Возможно, имя уже занято.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="max-w-4xl mx-auto">
            <button
                onClick={() => navigate('/characters')}
                className="flex items-center gap-2 text-slate-500 hover:text-slate-300 mb-8 transition-colors"
            >
                <ArrowLeft className="w-4 h-4" />
                Вернуться к выбору
            </button>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <div className="lg:col-span-2">
                    <h2 className="text-3xl font-bold text-slate-100 mb-2">Создание героя</h2>
                    <p className="text-slate-400 mb-8 text-lg">Каким будет твой путь в Victory?</p>

                    <form onSubmit={handleSubmit} className="space-y-8">
                        <div className="space-y-4">
                            <label className="text-sm font-bold text-slate-300 uppercase tracking-wider">Имя героя</label>
                            <input
                                type="text"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                className="w-full bg-slate-900 border border-slate-800 rounded-2xl py-4 px-6 text-xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 transition-all"
                                placeholder="Введите имя..."
                                required
                                maxLength={20}
                            />
                        </div>

                        <div className="space-y-4">
                            <label className="text-sm font-bold text-slate-300 uppercase tracking-wider">Выберите класс</label>
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                {classes.map((cls) => (
                                    <div
                                        key={cls.name}
                                        onClick={() => setSelectedClass(cls.name)}
                                        className={`p-6 rounded-2xl border-2 transition-all cursor-pointer flex flex-col items-center text-center gap-4 ${selectedClass === cls.name
                                            ? 'border-amber-500 bg-amber-500/5 shadow-lg shadow-amber-900/10'
                                            : 'border-slate-800 bg-slate-900 hover:border-slate-700'
                                            }`}
                                    >
                                        <div className={`w-12 h-12 rounded-xl flex items-center justify-center ${cls.bg}`}>
                                            <cls.icon className={`w-6 h-6 ${cls.color}`} />
                                        </div>
                                        <div>
                                            <h3 className="font-bold text-slate-100">{cls.name}</h3>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {error && (
                            <div className="bg-red-900/20 border border-red-500/50 text-red-500 px-6 py-4 rounded-xl text-sm">
                                {error}
                            </div>
                        )}

                        <button
                            type="submit"
                            disabled={loading || !name}
                            className="w-full bg-amber-600 hover:bg-amber-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-5 rounded-2xl shadow-xl shadow-amber-900/20 transition-all flex items-center justify-center gap-2"
                        >
                            {loading ? (
                                <Loader2 className="w-6 h-6 animate-spin" />
                            ) : (
                                'Создать и начать игру'
                            )}
                        </button>
                    </form>
                </div>

                <div className="hidden lg:block">
                    {/* Классовая справка */}
                    <div className="bg-slate-900 border border-slate-800 rounded-3xl p-8 sticky top-24">
                        {classes.find(c => c.name === selectedClass) ? (
                            <>
                                <div className="flex items-center gap-4 mb-6">
                                    <div className={`w-16 h-16 rounded-2xl flex items-center justify-center ${classes.find(c => c.name === selectedClass)?.bg}`}>
                                        {React.createElement(classes.find(c => c.name === selectedClass)!.icon, { className: `w-8 h-8 ${classes.find(c => c.name === selectedClass)?.color}` })}
                                    </div>
                                    <h3 className="text-2xl font-bold text-slate-100">{selectedClass}</h3>
                                </div>
                                <p className="text-slate-400 leading-relaxed mb-6">
                                    {classes.find(c => c.name === selectedClass)?.description}
                                </p>
                                <div className="space-y-4 border-t border-slate-800 pt-6">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-slate-500">Модификаторы статов</span>
                                        <div className="text-right">
                                            {selectedClass === 'Воин' && <span className="text-green-500 font-bold">+10% Сила / <span className="text-red-500">-10% Интеллект</span></span>}
                                            {selectedClass === 'Лучник' && <span className="text-green-500 font-bold">+10% Ловкость / <span className="text-red-500">-10% Сила</span></span>}
                                            {selectedClass === 'Маг' && <span className="text-green-500 font-bold">+10% Интеллект / <span className="text-red-500">-10% Стойкость</span></span>}
                                        </div>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-slate-500">Сложность</span>
                                        <span className="text-slate-200 font-bold">Низкая</span>
                                    </div>
                                </div>
                            </>
                        ) : null}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default CharacterCreationPage;
