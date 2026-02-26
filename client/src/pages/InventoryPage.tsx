import React, { useState, useEffect } from 'react';
import { useGameStore } from '../store/gameStore';
import api from '../api/axios';
import { Navigate, useNavigate } from 'react-router-dom';
import type { CharacterItem } from '../types/game';
import {
    Package, Sword, Shield, Trash2, ArrowUpCircle,
    Loader2, Info, Coins, Sparkles
} from 'lucide-react';
import { formatNumber } from '../lib/utils';
import { sellItem } from '../api/shopApi';

const InventoryPage: React.FC = () => {
    // ... (внутри компонента)
    const handleSell = async (itemId: number) => {
        if (!window.confirm('Вы уверены, что хотите продать этот предмет?')) return;

        setActionLoading(itemId);
        try {
            await sellItem(itemId);
            // Обновляем данные персонажа и инвентарь
            const charRes = await api.get(`/characters/${currentCharacter.id}`);
            updateCharacterData(charRes.data.data);
            await fetchInventory();
        } catch (err: any) {
            alert(err.response?.data?.message || 'Ошибка продажи');
        } finally {
            setActionLoading(null);
        }
    };
    const { currentCharacter, updateCharacterData } = useGameStore();
    const [items, setItems] = useState<CharacterItem[]>([]);
    const [loading, setLoading] = useState(true);
    const [actionLoading, setActionLoading] = useState<number | null>(null);
    const navigate = useNavigate();

    const fetchInventory = async () => {
        if (!currentCharacter) return;
        try {
            const response = await api.get(`/inventory/${currentCharacter.id}`);
            setItems(response.data.data);
        } catch (err) {
            console.error("Failed to fetch inventory", err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchInventory();
    }, [currentCharacter?.id]);

    if (!currentCharacter) return <Navigate to="/characters" />;

    const handleEquip = async (itemId: number, slot: string) => {
        setActionLoading(itemId);
        try {
            await api.post('/inventory/equip', {
                character_item_id: itemId,
                slot: slot
            });
            // Обновляем данные персонажа и инвентарь
            const charRes = await api.get(`/characters/${currentCharacter.id}`);
            updateCharacterData(charRes.data.data);
            await fetchInventory();
        } catch (err: any) {
            alert(err.response?.data?.message || 'Ошибка экипировки');
        } finally {
            setActionLoading(null);
        }
    };

    const handleUnequip = async (itemId: number) => {
        setActionLoading(itemId);
        try {
            await api.post('/inventory/unequip', {
                character_item_id: itemId
            });
            const charRes = await api.get(`/characters/${currentCharacter.id}`);
            updateCharacterData(charRes.data.data);
            await fetchInventory();
        } catch (err: any) {
            alert(err.response?.data?.message || 'Ошибка');
        } finally {
            setActionLoading(null);
        }
    };

    const equipped = items.filter(i => i.is_equipped);
    const backpack = items.filter(i => !i.is_equipped);

    const slots = [
        { id: 'head', name: 'Голова', icon: <Package className="w-5 h-5" /> },
        { id: 'neck', name: 'Шея', icon: <ArrowUpCircle className="w-5 h-5" /> },
        { id: 'chest', name: 'Торс', icon: <Shield className="w-5 h-5" /> },
        { id: 'weapon', name: 'Оружие', icon: <Sword className="w-5 h-5" /> },
        { id: 'hands', name: 'Руки', icon: <Package className="w-5 h-5" /> },
        { id: 'ring', name: 'Кольцо', icon: <Package className="w-5 h-5" /> },
        { id: 'belt', name: 'Пояс', icon: <Package className="w-5 h-5" /> },
        { id: 'legs', name: 'Ноги', icon: <Package className="w-5 h-5" /> },
        { id: 'feet', name: 'Ступни', icon: <Package className="w-5 h-5" /> },
        { id: 'trinket', name: 'Аксессуар', icon: <Package className="w-5 h-5" /> },
    ];

    const getEquippedInSlot = (slotId: string) => {
        return equipped.find(i => i.slot === slotId);
    };

    return (
        <div className="max-w-6xl mx-auto space-y-8 pb-20">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-3xl font-bold text-slate-100">Инвентарь</h2>
                    <p className="text-slate-500">Управление снаряжением и предметами</p>
                </div>
                <button
                    onClick={() => navigate('/dashboard')}
                    className="px-6 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl transition-all flex items-center gap-2"
                >
                    Назад в хаб
                </button>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                {/* Left: Equipment Slots */}
                <div className="lg:col-span-5 space-y-6">
                    <div className="bg-slate-900 border border-slate-800 rounded-3xl p-8 relative overflow-hidden">
                        <div className="absolute inset-0 opacity-5 pointer-events-none">
                            <div className="w-full h-full bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-amber-500/20 via-transparent to-transparent" />
                        </div>

                        <h3 className="text-lg font-bold text-slate-300 mb-8 flex items-center gap-2 relative z-10">
                            <Shield className="w-5 h-5 text-amber-500" /> Экипировка
                        </h3>

                        <div className="grid grid-cols-2 gap-4 relative z-10">
                            {slots.map(slot => {
                                const item = getEquippedInSlot(slot.id);
                                return (
                                    <div key={slot.id} className="group relative">
                                        <div className={`p-4 rounded-2xl border-2 transition-all flex items-center gap-4 ${item ? 'bg-slate-800 border-amber-500/50 shadow-lg shadow-amber-900/10' : 'bg-slate-950/50 border-dashed border-slate-800'
                                            }`}>
                                            <div className={`w-12 h-12 rounded-xl flex items-center justify-center ${item ? 'bg-amber-500/10 text-amber-500' : 'bg-slate-900 text-slate-700'
                                                }`}>
                                                {slot.icon}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-[10px] uppercase font-bold text-slate-600 truncate">{slot.name}</p>
                                                <p className={`text-sm font-bold truncate ${item ? 'text-slate-100' : 'text-slate-800'}`}>
                                                    {item ? item.item.name : 'Пусто'}
                                                </p>
                                                {item && item.item.display_stats && (
                                                    <div className="flex flex-wrap gap-x-2 gap-y-0.5 mt-1">
                                                        {item.item.display_stats.slice(0, 2).map((s, i) => (
                                                            <span key={i} className="text-[8px] text-amber-500/60 font-medium">
                                                                {s}
                                                            </span>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                            {item && (
                                                <button
                                                    onClick={() => handleUnequip(item.id)}
                                                    disabled={actionLoading !== null}
                                                    className="opacity-0 group-hover:opacity-100 p-2 hover:text-red-500 transition-all"
                                                >
                                                    {actionLoading === item.id ? <Loader2 className="w-4 h-4 animate-spin" /> : <Trash2 className="w-4 h-4" />}
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {/* Right: Backpack */}
                <div className="lg:col-span-7 space-y-6">
                    <div className="bg-slate-900 border border-slate-800 rounded-3xl p-8 min-h-[600px] flex flex-col">
                        <div className="flex items-center justify-between mb-8">
                            <h3 className="text-lg font-bold text-slate-300 flex items-center gap-2">
                                <Package className="w-5 h-5 text-slate-500" /> Рюкзак
                            </h3>
                            <div className="text-slate-500 text-sm font-bold uppercase tracking-tighter">
                                {backpack.length} / 40 слотов
                            </div>
                        </div>

                        {loading ? (
                            <div className="flex-1 flex items-center justify-center">
                                <Loader2 className="w-12 h-12 text-amber-500 animate-spin" />
                            </div>
                        ) : backpack.length === 0 ? (
                            <div className="flex-1 flex flex-col items-center justify-center opacity-20">
                                <Package className="w-20 h-20 mb-4" />
                                <p className="font-bold uppercase tracking-widest">Пусто</p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4 overflow-y-auto max-h-[600px] pr-2 custom-scrollbar">
                                {backpack.map(item => (
                                    <div key={item.id} className="group bg-slate-800/50 border border-slate-800 rounded-2xl p-4 hover:border-amber-500/50 transition-all relative">
                                        <div className="w-full aspect-square bg-slate-900 rounded-xl mb-3 flex items-center justify-center relative">
                                            {item.item.type === 'weapon' ? <Sword className="w-8 h-8 text-slate-700" /> : <Package className="w-8 h-8 text-slate-700" />}
                                            {item.quantity > 1 && (
                                                <span className="absolute bottom-1 right-2 text-[10px] font-bold bg-slate-800 text-slate-300 px-1.5 rounded-md border border-slate-700">
                                                    x{item.quantity}
                                                </span>
                                            )}
                                        </div>
                                        <h4 className="text-xs font-bold text-slate-200 truncate mb-1" title={item.item.name}>{item.item.name}</h4>

                                        {/* Item Stats */}
                                        {item.item.display_stats && item.item.display_stats.length > 0 && (
                                            <div className="mt-2 space-y-0.5">
                                                {item.item.display_stats.map((stat, idx) => (
                                                    <div key={idx} className="flex items-center gap-1 text-[9px] font-medium text-amber-500/80">
                                                        <Sparkles className="w-2.5 h-2.5 opacity-40" />
                                                        {stat}
                                                    </div>
                                                ))}
                                            </div>
                                        )}

                                        <div className="flex items-center justify-between mt-auto pt-2">
                                            <span className="text-[10px] font-bold text-slate-500 uppercase">{item.item.type}</span>
                                            {item.item.base_price > 0 && (
                                                <div className="flex items-center gap-1 text-[10px] font-bold text-amber-600">
                                                    <Coins className="w-3 h-3" />
                                                    {Math.floor(item.item.base_price * 0.5)}
                                                </div>
                                            )}
                                        </div>

                                        {/* Action Overlay */}
                                        <div className="absolute inset-0 bg-slate-900/90 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center p-4 gap-2">
                                            {['material', 'junk'].includes(item.item.type) ? (
                                                <p className="text-[10px] text-slate-500 lowercase">Используется для крафта</p>
                                            ) : (
                                                <button
                                                    onClick={() => handleEquip(item.id, item.item.type)}
                                                    disabled={actionLoading !== null}
                                                    className="w-full py-2 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold rounded-lg transition-all"
                                                >
                                                    {actionLoading === item.id ? <Loader2 className="w-4 h-4 animate-spin mx-auto" /> : 'Надеть'}
                                                </button>
                                            )}

                                            <button
                                                onClick={() => handleSell(item.id)}
                                                disabled={actionLoading !== null}
                                                className="w-full py-2 bg-red-900/30 hover:bg-red-900/50 text-red-400 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-2"
                                            >
                                                {actionLoading === item.id ? <Loader2 className="w-3 h-3 animate-spin" /> : <Coins className="w-3 h-3" />}
                                                Продать
                                            </button>

                                            <button className="w-full py-2 bg-slate-800 hover:bg-slate-700 text-slate-400 text-xs font-bold rounded-lg transition-all flex items-center justify-center gap-1">
                                                <Info className="w-3 h-3" /> Инфо
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default InventoryPage;
