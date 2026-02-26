import React from 'react';
import type { Item } from '../types/game';
import { Sword, Shield, Package, Sparkles } from 'lucide-react';

interface ItemCardProps {
    item: Item;
    hideActions?: boolean;
}

const ItemCard: React.FC<ItemCardProps> = ({ item, hideActions = false }) => {
    const getQualityColor = (quality: number) => {
        switch (quality) {
            case 2: return 'text-green-500 border-green-500/30 bg-green-500/5';
            case 3: return 'text-blue-500 border-blue-500/30 bg-blue-500/5';
            case 4: return 'text-purple-500 border-purple-500/30 bg-purple-500/5';
            case 5: return 'text-orange-500 border-orange-500/30 bg-orange-500/5';
            default: return 'text-slate-400 border-slate-700/50 bg-slate-800/20';
        }
    };

    const getTypeIcon = (type: string) => {
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
        <div className={`p-4 rounded-xl border-2 transition-all flex flex-col gap-3 ${getQualityColor(item.quality)}`}>
            <div className="flex items-start justify-between gap-3">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-lg bg-slate-900 flex items-center justify-center border border-current opacity-60">
                        {getTypeIcon(item.type)}
                    </div>
                    <div>
                        <h4 className="font-bold text-slate-100 uppercase tracking-tight leading-tight">{item.name}</h4>
                        <span className="text-[10px] font-bold uppercase opacity-60">{item.type}</span>
                    </div>
                </div>
            </div>

            {/* Bonuses */}
            {item.display_stats && item.display_stats.length > 0 && (
                <div className="space-y-1.5 py-2 border-t border-current border-dotted opacity-80">
                    {item.display_stats.map((stat, idx) => (
                        <div key={idx} className="flex items-center gap-2 text-xs font-medium text-slate-300">
                            <Sparkles className="w-3 h-3 text-current opacity-40" />
                            {stat}
                        </div>
                    ))}
                </div>
            )}

            {!hideActions && (
                <div className="mt-auto">
                    {/* Сюда можно добавить кнопки действия, если это необходимо для инвентаря */}
                </div>
            )}
        </div>
    );
};

export default ItemCard;
