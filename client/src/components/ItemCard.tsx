import React from 'react';
import type { Item } from '../types/game';
import { Sword, Shield, Package, Sparkles, XOctagon, Lock } from 'lucide-react';

interface ItemCardProps {
    item: Item;
    ilevel?: number;
    quality?: number;
    hideActions?: boolean;
    playerClass?: string;
}

const qualityNames: Record<number, string> = {
    1: 'Обычный',
    2: 'Необычный',
    3: 'Редкий',
    4: 'Эпический',
    5: 'Легендарный',
};

const ItemCard: React.FC<ItemCardProps> = ({ item, ilevel, quality, hideActions = false, playerClass }) => {
    const effectiveQuality = quality ?? item.quality;
    const isWrongClass = item.required_class && playerClass && item.required_class.toLowerCase() !== playerClass.toLowerCase();

    const getCardStyles = (q: number) => {
        const base = "p-4 rounded-xl border-2 transition-all flex flex-col gap-3 relative overflow-hidden";

        // КРАСНАЯ ОБВОДКА для неподходящего класса
        if (isWrongClass) {
            return `${base} border-red-600 bg-red-950/20 shadow-[0_0_15px_rgba(220,38,38,0.15)]`;
        }

        // Стандартные цвета по качеству
        switch (q) {
            case 2: return `${base} text-green-500 border-green-500/30 bg-green-500/5`;
            case 3: return `${base} text-blue-500 border-blue-500/30 bg-blue-500/5`;
            case 4: return `${base} text-purple-500 border-purple-500/30 bg-purple-500/5`;
            case 5: return `${base} text-orange-500 border-orange-500/30 bg-orange-500/5`;
            default: return `${base} text-slate-400 border-slate-700/50 bg-slate-800/20`;
        }
    };

    const getTypeIcon = (type: string) => {
        if (isWrongClass) return <Lock className="w-5 h-5 text-red-500" />;

        switch (type) {
            case 'weapon': return <Sword className="w-5 h-5" />;
            case 'head':
            case 'chest':
            case 'hands':
            case 'legs':
            case 'feet': return <Shield className="w-5 h-5" />;
            default: return <Package className="w-5 h-5" />;
        }
    };

    return (
        <div className={getCardStyles(effectiveQuality)}>
            {/* Основная инфа */}
            <div className="flex items-start justify-between gap-3">
                <div className="flex items-center gap-3">
                    <div className={`w-10 h-10 rounded-lg flex items-center justify-center border transition-colors ${isWrongClass ? 'bg-red-900/20 border-red-600' : 'bg-slate-900 border-current opacity-60'
                        }`}>
                        {getTypeIcon(item.type)}
                    </div>

                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <h4 className={`font-bold uppercase tracking-tight leading-tight ${isWrongClass ? 'text-red-400' : 'text-slate-100'
                                }`}>
                                {item.name}
                            </h4>

                            <div className="flex gap-1 items-center">
                                {ilevel && (
                                    <span className="text-[10px] bg-slate-700 text-slate-300 px-1.5 py-0.5 rounded font-mono border border-slate-600">
                                        Lvl {ilevel}
                                    </span>
                                )}

                                {effectiveQuality > 1 && (
                                    <span className={`text-[10px] px-1.5 py-0.5 rounded font-bold uppercase border ${
                                        effectiveQuality === 2 ? 'bg-green-900/50 border-green-500 text-green-400' :
                                        effectiveQuality === 3 ? 'bg-blue-900/50 border-blue-500 text-blue-400' :
                                        effectiveQuality === 4 ? 'bg-purple-900/50 border-purple-500 text-purple-400' :
                                        'bg-orange-900/50 border-orange-500 text-orange-400'
                                    }`}>
                                        {qualityNames[effectiveQuality] || 'Обычный'}
                                    </span>
                                )}

                                {item.required_class && (
                                    <span className={`text-[10px] px-1.5 py-0.5 rounded font-bold uppercase border ${isWrongClass
                                            ? 'bg-red-600 border-red-400 text-white'
                                            : 'bg-slate-800 border-slate-600 text-slate-400'
                                        }`}>
                                        {item.required_class}
                                    </span>
                                )}
                            </div>
                        </div>
                        <span className="text-[10px] font-bold uppercase opacity-50">{item.type}</span>
                    </div>
                </div>
            </div>

            {/* Список статов */}
            <div className={`space-y-1.5 py-2 border-t border-dotted transition-colors ${isWrongClass ? 'border-red-900' : 'border-current'
                }`}>
                {item.display_stats && item.display_stats.length > 0 ? (
                    item.display_stats.map((stat, idx) => (
                        <div key={idx} className="flex items-center gap-2 text-xs font-medium text-slate-300">
                            <Sparkles className={`w-3 h-3 opacity-40 ${isWrongClass ? 'text-red-500' : 'text-current'}`} />
                            {stat}
                        </div>
                    ))
                ) : (
                    <div className="text-[10px] italic opacity-40 text-slate-500">Нет доп. характеристик</div>
                )}

                {/* Плашка ошибки */}
                {isWrongClass && (
                    <div className="mt-2 flex items-center gap-1.5 text-red-500 font-bold text-[10px] uppercase bg-red-950/40 p-1.5 rounded border border-red-900/50">
                        <XOctagon className="w-3 h-3" />
                        Неподходящий класс
                    </div>
                )}
            </div>

            {/* Кнопка действия */}
            {!hideActions && (
                <div className="mt-auto pt-2">
                    <button
                        disabled={!!isWrongClass}
                        className={`w-full py-2 rounded-lg text-[11px] font-black uppercase transition-all ${isWrongClass
                                ? 'bg-red-950/20 text-red-900 border border-red-900/50 cursor-not-allowed opacity-50'
                                : 'bg-slate-100 text-slate-900 hover:bg-white active:scale-95'
                            }`}
                    >
                        {isWrongClass ? 'Заблокировано' : 'Экипировать'}
                    </button>
                </div>
            )}
        </div>
    );
};

export default ItemCard;